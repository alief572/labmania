"""Promosi lokal data asli. Jalankan dari WSL; tidak mengimpor, seed, atau menghapus volume."""
import argparse
import datetime
import hashlib
import json
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
HERE = Path(__file__).resolve().parent
CONFIRMATIONS = ('source_capture_confirmed', 'legacy_scope_confirmed', 'documents_confirmed',
                 'business_exceptions_confirmed', 'finance_confirmed', 'roles_permissions_confirmed',
                 'uat_accepted', 'backup_restore_confirmed')


def require(ok, message):
    if not ok:
        raise RuntimeError(message)


def acceptance(data, manifest_hash, defer_finance=False):
    require(data.get('manifest_sha256') == manifest_hash, 'Penerimaan tidak cocok dengan hash manifest.')
    person = data.get('accepted_by', '').strip()
    require(person and not re.search(r'rehearsal|contoh|placeholder|sintetis', person, re.I), 'Identitas penerima nyata wajib diisi.')
    try:
        at = datetime.datetime.fromisoformat(data.get('accepted_at', ''))
        require(at.tzinfo is not None and at <= datetime.datetime.now(datetime.timezone.utc), 'Waktu penerimaan wajib berzona waktu dan tidak di masa depan.')
    except (ValueError, TypeError):
        raise RuntimeError('Waktu penerimaan tidak valid.') from None
    require(bool(data.get('source_capture_evidence', '').strip()), 'Bukti konsistensi capture wajib dirujuk.')
    for field in CONFIRMATIONS:
        if field == 'finance_confirmed' and defer_finance:
            require(data.get('finance_deferred') is True, 'Penundaan Finance belum diterima pemilik.')
            continue
        require(data.get(field) is True, 'Penerimaan belum lengkap: ' + field)


def run(args, allow_failure=False):
    result = subprocess.run(args, capture_output=True, text=True, timeout=180)
    require(result.returncode == 0 or allow_failure, 'Perintah gagal; tidak mencetak environment/SQL: ' + args[0])
    return result.stdout


def report(args, allow_failure=False):
    raw = run(args, allow_failure=allow_failure)
    start = raw.find('{')
    require(start >= 0, 'Laporan JSON tidak ditemukan.')
    return json.loads(raw[start:])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('action', choices=('check', 'promote', 'preview-local'))
    parser.add_argument('--project', required=True)
    parser.add_argument('--env-file', type=Path, required=True)
    parser.add_argument('--manifest', required=True, help='Nama file dalam /snapshot, bukan path host')
    parser.add_argument('--acceptance', type=Path)
    parser.add_argument('--defer-finance', action='store_true', help='Tunda konfigurasi Finance; transaksi/cetak yang belum siap tetap ditolak')
    args = parser.parse_args()
    local_preview = args.action == 'preview-local'
    require(re.fullmatch(r'labmania-realdata-[a-z0-9]+', args.project), 'Project harus rehearsal data asli lokal.')
    require(re.fullmatch(r'[a-zA-Z0-9_-]+\.json', args.manifest), 'Nama manifest tidak valid.')
    env = args.env_file.resolve(strict=True)
    require(env.parent == (ROOT / 'rehearsal').resolve() and env.name.startswith('.env.rehearsal-realdata-'), 'Environment harus khusus rehearsal data asli.')
    compose = ['docker', 'compose', '--env-file', str(env), '-p', args.project,
               '-f', str(ROOT / 'rehearsal/compose.yaml'), '-f', str(ROOT / 'rehearsal/compose.realdata.yaml')]
    cli = compose + ['exec', '-T', '--user', 'www-data', 'app', 'php', 'artisan']
    container = run(compose + ['ps', '-q', 'app']).strip()
    require(bool(container) and '\n' not in container, 'Preview app tunggal harus berjalan terlebih dahulu.')
    verify_args = ['labmania:migrasi-verifikasi', '--manifest=/snapshot/' + args.manifest] + (['--metadata-login-berubah'] if local_preview else [])
    state = report(cli + verify_args)
    require(state.get('verified') is True, 'Data tidak sesuai manifest; rekonsiliasi diperlukan sebelum promosi.')
    manifest_hash = state.get('manifest_sha256')
    require(isinstance(manifest_hash, str) and re.fullmatch('[a-f0-9]{64}', manifest_hash), 'Hash manifest tidak valid.')
    # kesiapan memakai exit nonzero bila konfigurasi tidak lengkap. Jangan lanjut saat gagal.
    ready_args = ['labmania:migrasi-operasi', 'kesiapan'] + (['--tunda-keuangan'] if args.defer_finance else [])
    runtime = report(cli + ready_args, allow_failure=True)
    failed = [key for key, value in runtime.get('checks', {}).items() if value is not True]
    require(runtime.get('runtime_ready') is True, 'Runtime belum siap: ' + ', '.join(failed))
    if local_preview:
        require(str(state.get('snapshot', '')).startswith('REHEARSAL-'), 'Preview lokal hanya untuk manifest snapshot rehearsal.')
        print('PREVIEW LOKAL: snapshot belum disahkan untuk production; capture/upload/UAT tetap perlu penerimaan.')
    else:
        require(args.acceptance is not None, 'Penerimaan pemilik data diperlukan; salin acceptance.example.json ke folder privat.')
        accepted = json.loads(args.acceptance.read_text(encoding='utf-8-sig'))
        acceptance(accepted, manifest_hash, args.defer_finance)
        print('Preflight lulus; pengesahan manusia tetap berasal dari dokumen penerimaan.')
    if args.action == 'check':
        return
    labels = json.loads(run(['docker', 'inspect', '--format', '{{json .Config.Labels}}', 'labmania_web']))
    require(labels.get('com.docker.compose.project') == 'labmania' and labels.get('com.docker.compose.service') == 'web', 'Layanan 8080 bukan labmania/web yang diharapkan.')
    previous = ROOT / 'docker-compose.yml'
    require(previous.is_file(), 'Compose 8080 existing tidak ditemukan.')
    stamp = datetime.datetime.now().strftime('%d-%m-%Y_%H-%M-%S-%f')
    backup = ROOT / '.artifacts/penyelesaian_migrasi_laravel' / ('promosi_' + stamp)
    backup.mkdir(parents=True, exist_ok=False)
    old = previous.read_bytes()
    with (backup / 'compose-sebelumnya.yaml').open('xb') as stream:
        stream.write(old)
    root_env = ROOT / '.env'
    old_env = root_env.read_bytes() if root_env.is_file() else None
    if old_env is not None:
        with (backup / 'environment-sebelumnya.env').open('xb') as stream:
            stream.write(old_env)
    promoted = compose + ['-f', str(HERE / 'compose.realdata-8080.yaml')]
    rendered = run(promoted + ['--profile', 'preview', 'config'])
    require('labmania:uat' not in rendered, 'Compose promosi tidak boleh melakukan bootstrap UAT.')
    # Maintenance menahan writer sebelum pemeriksaan akhir dan backup titik pemulihan.
    run(cli + ['down'])
    switched = False
    try:
        require(report(cli + verify_args).get('verified') is True, 'Data berubah setelah preflight.')
        archive = 'sebelum-promosi-' + stamp + '.zip'
        # CLI root sesuai runbook rehearsal: bind mount privat Windows menolak chmod dari www-data.
        run(compose + ['exec', '-T', '--user', 'root', 'app', 'php', 'artisan', 'labmania:migrasi-operasi', 'backup', '--arsip=' + archive])
        run(['docker', 'stop', 'labmania_web'])
        switched = True
        run(promoted + ['--profile', 'preview', 'up', '-d', '--no-deps', '--no-build', 'app'])
        require(report(promoted + ['exec', '-T', '--user', 'www-data', 'app', 'php', 'artisan'] + ready_args, allow_failure=True).get('runtime_ready') is True, 'Runtime setelah pengalihan belum siap.')
        run(promoted + ['exec', '-T', '--user', 'www-data', 'app', 'php', 'artisan', 'up'])
        previous.write_text(rendered, encoding='utf-8')
        if old_env is not None:
            project_line = ('COMPOSE_PROJECT_NAME=' + args.project).encode('ascii')
            text, count = re.subn(rb'(?m)(^|\A\xef\xbb\xbf)COMPOSE_PROJECT_NAME=[^\r\n]*', lambda match: match[1] + project_line, old_env)
            if count == 0:
                ending = b'\r\n' if b'\r\n' in old_env else b'\n'
                text = text.rstrip(b'\r\n') + ending + project_line + ending
            root_env.write_bytes(text)
        with (backup / 'state.json').open('x', encoding='utf-8') as stream:
            json.dump({'project': args.project, 'manifest_sha256': manifest_hash, 'previous_compose_sha256': hashlib.sha256(old).hexdigest(), 'backup_archive': archive,
                       'previous_env_sha256': hashlib.sha256(old_env).hexdigest() if old_env is not None else None,
                       'local_preview': local_preview, 'finance_deferred': args.defer_finance, 'production_accepted': False}, stream, indent=2)
        print('Data asli melayani localhost:8080. Backup/state privat: ' + str(backup))
    except Exception:
        # Tidak menyalin DB kembali. Pertahankan volume dan blokir writer sampai operator merekonsiliasi.
        if switched:
            try:
                run(promoted + ['exec', '-T', '--user', 'www-data', 'app', 'php', 'artisan', 'down'])
            except Exception:
                run(promoted + ['stop', 'app'])
            raise RuntimeError('Promosi terhenti setelah pengalihan. Data dipertahankan; gunakan runbook rollback dan backup privat. Jangan seed atau menyalin DB ke CI3.') from None
        run(cli + ['up'])
        raise


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, OSError, ValueError, subprocess.SubprocessError) as error:
        print('DITOLAK: ' + (str(error) if isinstance(error, RuntimeError) else 'Periksa input/koneksi secara privat.'), file=sys.stderr)
        sys.exit(1)
