"""Tes scripts/preflight-production.sh --offline dengan fixture; tidak memakai Docker/DB."""

from pathlib import Path
import re
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
bash = shutil.which("bash")
assert bash, "bash diperlukan"
KEY = "base64:" + "A" * 43 + "="


def posix(path):
    """Git Bash di Windows butuh /c/... agar path terbaca absolut; di Linux tidak berubah."""
    cygpath = shutil.which("cygpath")
    if not cygpath:
        return Path(path).as_posix()
    return subprocess.run([cygpath, "-u", str(path)], capture_output=True, text=True, check=True).stdout.strip()


script = posix(root / "scripts" / "preflight-production.sh")


def run(env_text, *, mode=0o600, uid_ok=True, make_dirs=True, fonts=True):
    with tempfile.TemporaryDirectory() as folder:
        base = Path(folder)
        storage, font = base / "storage", base / "fonts"
        if make_dirs:
            (storage / "app/private/documents").mkdir(parents=True)
            (storage / "app/private/documents/a.pdf").write_bytes(b"x")
            font.mkdir()
            if fonts:
                (font / "times.ttf").write_bytes(b"x")
                (font / "timesbd.ttf").write_bytes(b"x")
        env = base / ".env.production"
        env.write_text(env_text.format(storage=posix(storage), fonts=posix(font)), encoding="utf-8", newline="\n")
        env.chmod(mode)
        result = subprocess.run([bash, script, "--offline", "--env", posix(env)], capture_output=True, text=True)
        return result.returncode, result.stdout


GOOD = (
    f"APP_KEY='{KEY}'\nAPP_URL=https://apps.example.invalid/labmania\nDB_DATABASE=laravel_fixture\n"
    "DB_USERNAME=laravel_fixture\nDB_PASSWORD='fixture$literal#pw'\nPRODUCTION_STORAGE_DIR={storage}\n"
    "PRODUCTION_FONT_DIR={fonts}\nCOA_DB_HOST=host.docker.internal\n"
)

# Template belum diisi harus gagal dan tidak mencetak nilai secret.
code, out = run((root / ".env.production.example").read_text(encoding="utf-8"))
assert code == 1 and "APP_KEY masih placeholder" in out and "DB_PASSWORD masih placeholder" in out, out
assert "GANTI_PASSWORD" not in out

# Env valid: tidak ada FAIL tak terkait izin/pemilik (izin/pemilik bergantung OS/user host).
code, out = run(GOOD)
fails = [line for line in out.splitlines() if line.startswith("FAIL")]
benign = ("izin file", "pemilik storage")
assert all(any(b in line for b in benign) for line in fails), out
assert "fixture$literal#pw" not in out  # secret tidak pernah dicetak
assert "format APP_KEY" in out and "APP_URL https://apps.example.invalid/labmania" in out
assert "WARN  kosong, operasi finance/cetak" in out

for bad, needle in [
    (GOOD.replace("/labmania", ""), "APP_URL harus"),
    (GOOD.replace("DB_USERNAME=laravel_fixture", "DB_USERNAME=root"), "tidak boleh root"),
    (GOOD.replace("laravel_fixture\nDB_USERNAME", "db_labmania\nDB_USERNAME"), "db_labmania ditolak"),
    (GOOD.replace(KEY, "base64:pendek"), "format APP_KEY"),
    (GOOD.replace("COA_DB_HOST=host.docker.internal", "COA_DB_HOST=127.0.0.1"), "COA_DB_HOST"),
]:
    code, out = run(bad)
    assert needle in out and (code == 1 or needle.startswith("COA")), (needle, out)

code, out = run(GOOD, fonts=False)
assert "font times.ttf tidak ada" in out and "font timesbd.ttf tidak ada" in out and code == 1
code, out = run(GOOD, make_dirs=False)
assert "storage tidak ada" in out and "folder font tidak ada" in out and code == 1
code, out = run(GOOD.replace("PRODUCTION_STORAGE_DIR={storage}", f"PRODUCTION_STORAGE_DIR={posix(root)}/storage"))
assert "di dalam repository" in out and code == 1

# Kunci tidak dikenal ditolak, bukan diabaikan.
assert subprocess.run([bash, script, "--bogus"], capture_output=True, text=True).returncode == 2

assert re.search(r"Ringkasan: \d+ FAIL, \d+ WARN", out)
print("Preflight production lulus; tidak ada container/DB dijalankan.")
