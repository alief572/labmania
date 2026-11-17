#!/usr/bin/env bash
# Backup production Labmania: dump DB Laravel + arsip dokumen/storage + manifest dan hash.
# Jalankan di server Ubuntu, dari root repository, sebagai pengguna yang boleh membaca storage (biasanya sudo).
# Tidak menghentikan atau mengubah aplikasi, tidak menimpa backup lama, dan tidak mencetak secret.
#
#   sudo scripts/backup-production.sh --out /srv/labmania/backups [--env FILE] [--defaults-extra-file FILE]
#                                      [--include-env] [--writers-stopped]
#   scripts/backup-production.sh --verify /srv/labmania/backups/labmania-DD-MM-YYYY_HH-MM-SS
#
#   --out DIR                 folder backup privat, absolut, di luar repository (dibuat 0700 bila belum ada)
#   --env FILE                environment production (default .env.production); dipakai untuk nama DB dan path storage
#   --defaults-extra-file F   option file klien MySQL milik admin ([client] user/password/host/port, izin 600).
#                             Tanpa ini klien memakai konfigurasi default (di Ubuntu: `sudo` + socket root).
#                             Jangan pakai akun aplikasi: grant-nya hanya dari subnet Docker, bukan dari host.
#   --include-env             salin file environment (memuat APP_KEY dan password) ke folder backup (izin 600)
#   --writers-stopped         tegaskan seluruh penulis sudah dihentikan; wajib bila DB memuat tabel MyISAM
#   --verify DIR              periksa hash dan integritas backup yang sudah ada, lalu selesai
# Dump tanpa CREATE/DROP DATABASE dan tanpa USE: restore selalu ke database kosong pilihan operator.
# Backup bukan bukti pemulihan sebelum diuji restore. GL/COA tidak ikut (sumber terpisah).
set -euo pipefail

ENV_FILE=.env.production; OUT=; EXTRA=; INCLUDE_ENV=0; STOPPED=0; VERIFY=
die() { echo "ERROR: $*" >&2; exit 2; }
while [ $# -gt 0 ]; do
    case $1 in
        --out) OUT=${2:?--out butuh path}; shift;;
        --env) ENV_FILE=${2:?--env butuh path}; shift;;
        --defaults-extra-file) EXTRA=${2:?butuh path}; shift;;
        --include-env) INCLUDE_ENV=1;;
        --writers-stopped) STOPPED=1;;
        --verify) VERIFY=${2:?--verify butuh folder backup}; shift;;
        -h|--help) sed -n 2,20p "$0"; exit 0;;
        *) die "opsi tidak dikenal: $1";;
    esac
    shift
done

verify() {
    local d=$1 bad=0
    [ -f "$d/SHA256SUMS" ] || die "SHA256SUMS tidak ada di $d"
    (cd "$d" && sha256sum --check --quiet SHA256SUMS) || bad=1
    local f tail3
    for f in "$d"/*.sql.gz; do
        [ -e "$f" ] || continue
        gzip -t "$f" || bad=1
        tail3=$(zcat "$f" | tail -n 3) || bad=1
        case $tail3 in *'-- Dump completed'*) ;; *) echo "dump tidak lengkap: $f" >&2; bad=1;; esac
    done
    for f in "$d"/*.tar.gz; do
        [ -e "$f" ] || continue
        tar -tzf "$f" >/dev/null || bad=1
    done
    if [ $bad = 0 ]; then echo "PASS  backup utuh: $d"; else echo "FAIL  backup rusak atau tidak lengkap: $d" >&2; return 1; fi
}
[ -z "$VERIFY" ] || { verify "$VERIFY"; exit $?; }

ROOT=$(cd "$(dirname "$0")/.." && pwd)
case $ENV_FILE in /*) ;; *) ENV_FILE=$ROOT/$ENV_FILE;; esac
[ -f "$ENV_FILE" ] || die "file environment tidak ada: $ENV_FILE"
[ -n "$OUT" ] || die "--out wajib"
case $OUT in /*) ;; *) die "--out harus path absolut";; esac
case $OUT/ in "$ROOT"/*) die "--out tidak boleh di dalam repository";; esac
for t in mysqldump mysql tar gzip sha256sum; do command -v "$t" >/dev/null 2>&1 || die "$t tidak ditemukan"; done

# Baca KEY dari file env tanpa meng-source (secret bisa memuat $ dan karakter khusus).
val() {
    local line
    line=$(grep -E "^$1=" "$ENV_FILE" | tail -n1 || true); line=${line#*=}; line=${line%$'\r'}
    case $line in \'*\'|\"*\") line=${line:1:${#line}-2};; esac
    printf '%s' "$line"
}
DB=$(val DB_DATABASE); STORAGE=$(val PRODUCTION_STORAGE_DIR)
[[ $DB =~ ^[A-Za-z0-9_]{1,64}$ ]] || die "DB_DATABASE kosong atau tidak valid di $ENV_FILE"
case $DB in db_labmania|mysql|sys|information_schema|performance_schema) die "DB_DATABASE '$DB' bukan database Laravel";; esac
[ -d "$STORAGE/app" ] || die "storage tidak ada atau tanpa app/: $STORAGE"

CLIENT=(); [ -z "$EXTRA" ] || { [ -f "$EXTRA" ] || die "option file tidak ada: $EXTRA"; CLIENT=(--defaults-extra-file="$EXTRA"); }
# --defaults-extra-file harus opsi pertama pada setiap perintah klien.
sql() { mysql "${CLIENT[@]}" --batch --skip-column-names -e "$1"; }
sql 'select 1' >/dev/null 2>&1 || die "tidak bisa terhubung ke MySQL dengan akun admin (pakai sudo atau --defaults-extra-file)"
[ "$(sql "select count(*) from information_schema.schemata where schema_name='$DB'")" = 1 ] || die "database '$DB' tidak ada"

myisam=$(sql "select group_concat(table_name) from information_schema.tables where table_schema='$DB' and engine='MyISAM'")
if [ -n "$myisam" ] && [ "$myisam" != NULL ] && [ $STOPPED = 0 ]; then
    die "tabel MyISAM ($myisam): --single-transaction tidak konsisten untuk tabel ini. Hentikan semua penulis lalu ulangi dengan --writers-stopped"
fi

# Ruang kosong minimal 2x perkiraan ukuran data (dump terkompresi + arsip).
need_kb=$(( $(du -sk "$STORAGE/app" | cut -f1) + $(sql "select ceil(coalesce(sum(data_length+index_length),0)/1024) from information_schema.tables where table_schema='$DB'") ))
umask 077
mkdir -p "$OUT"; chmod 700 "$OUT" 2>/dev/null || true
free_kb=$(df -Pk "$OUT" | awk 'NR==2{print $4}')
[ "$free_kb" -ge $((need_kb * 2)) ] || die "ruang kosong kurang di $OUT: ${free_kb}KB, butuh ~$((need_kb * 2))KB"

STAMP=$(date +%d-%m-%Y_%H-%M-%S)
DEST=$OUT/labmania-$STAMP
mkdir "$DEST" || die "folder backup sudah ada, tidak menimpa: $DEST"   # mkdir tanpa -p: gagal bila sudah ada
trap 'rc=$?; [ $rc = 0 ] || echo "GAGAL; folder sebagian dibiarkan untuk diperiksa: $DEST" >&2' EXIT

echo "== Dump database $DB"
# Flag ini ada di MySQL tetapi tidak di MariaDB; pasang hanya bila klien mendukungnya.
DUMP_HELP=$(mysqldump --help 2>/dev/null || true)
EXTRA_FLAGS=()
case $DUMP_HELP in *--no-tablespaces*) EXTRA_FLAGS+=(--no-tablespaces);; esac
case $DUMP_HELP in *--set-gtid-purged*) EXTRA_FLAGS+=(--set-gtid-purged=OFF);; esac
mysqldump "${CLIENT[@]}" --single-transaction --skip-lock-tables "${EXTRA_FLAGS[@]}" \
    --hex-blob --default-character-set=utf8mb4 --routines --events --triggers "$DB" | gzip -9 > "$DEST/db-$DB.sql.gz"

echo "== Arsip storage/app (tanpa mpdf, session, cache, view, log)"
tar --numeric-owner --exclude='app/mpdf' --exclude='app/mpdf/*' -C "$STORAGE" -czf "$DEST/storage-app.tar.gz" app

if [ $INCLUDE_ENV = 1 ]; then cp "$ENV_FILE" "$DEST/env.production.copy"; chmod 600 "$DEST/env.production.copy"; fi

{
    echo "Backup Labmania $STAMP"
    echo "Database: $DB; tabel: $(sql "select count(*) from information_schema.tables where table_schema='$DB'")"
    echo "Baris per tabel (hitung eksak saat backup selesai; transaksi baru setelah dump belum tercakup):"
    sql "select table_name from information_schema.tables where table_schema='$DB' and table_type='BASE TABLE' order by 1" | while read -r t; do
        printf '  %s\t%s\n' "$t" "$(sql "select count(*) from \`$DB\`.\`$t\`")"
    done
    echo "Dokumen privat: $(find "$STORAGE/app/private/documents" -type f 2>/dev/null | wc -l) berkas"
    echo "APP_KEY: $( [ $INCLUDE_ENV = 1 ] && echo 'ada di env.production.copy' || echo 'TIDAK disertakan; simpan terpisah dan aman' )"
} > "$DEST/manifest.txt"

(cd "$DEST" && sha256sum -- * > SHA256SUMS.tmp && mv SHA256SUMS.tmp SHA256SUMS)
verify "$DEST"
echo
(cd "$DEST" && ls -l --time-style=+ | awk 'NR>1{printf "  %10s  %s\n",$5,$NF}')
echo "Lokasi: $DEST"
[ $INCLUDE_ENV = 1 ] || echo "INGAT: APP_KEY tidak ada di backup ini; tanpanya data terenkripsi dan sesi tidak terbaca."
echo "Backup belum terbukti sampai restore diuji ke database kosong. Pertahankan backup ini sebelum pembaruan."
