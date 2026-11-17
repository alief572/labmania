"""Tes integrasi scripts/provision-db.sh dan scripts/backup-production.sh lawan MySQL 8.4 sementara.

Butuh Docker daemon dan image mysql:8.4 (lokal atau bisa ditarik). Semua resource (network, container)
diberi nama unik dan dihapus di akhir. Tidak menyentuh database/kontainer lain.
"""

from pathlib import Path
import os
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[1]
IMAGE = "mysql:8.4"
tag = f"lmdbtest{os.getpid()}"
net, srv, tool = f"{tag}-net", f"{tag}-srv", f"{tag}-tool"
ROOT_PW = "root-fixture-pw-0123456789"
APP_PW = "app-fixture-pw-0123456789"


def sh(*args, check=True, input=None, timeout=300):
    result = subprocess.run(args, capture_output=True, text=True, input=input, timeout=timeout)
    if check and result.returncode != 0:
        raise AssertionError(f"{' '.join(args[:6])} -> {result.returncode}\n{result.stdout}\n{result.stderr}")
    return result


def inside(cmd, **kw):
    return sh("docker", "exec", "-i", tool, "bash", "-c", cmd, **kw)


def admin_sql(sql, db=""):
    return inside(f"mysql --defaults-extra-file=/work/admin.cnf --batch --skip-column-names {db} -e \"{sql}\"")


try:
    sh("docker", "network", "create", net)
    subnet = sh("docker", "network", "inspect", net, "--format", "{{range .IPAM.Config}}{{.Subnet}}{{end}}").stdout.strip()
    assert subnet.count(".") == 3 and "/" in subnet, subnet

    sh("docker", "run", "-d", "--name", srv, "--network", net, "-e", f"MYSQL_ROOT_PASSWORD={ROOT_PW}", IMAGE)
    sh("docker", "run", "-d", "--name", tool, "--network", net, "--entrypoint", "sleep", IMAGE, "infinity")
    inside("mkdir -p /work/scripts /srv/storage/app/private/documents /srv/storage/app/mpdf /srv/out")

    with tempfile.TemporaryDirectory() as folder:
        work = Path(folder)
        (work / "admin.cnf").write_text(f"[client]\nhost={srv}\nuser=root\npassword={ROOT_PW}\n", newline="\n")
        env = work / "env"
        env.write_text("DB_DATABASE=labmania_src\nPRODUCTION_STORAGE_DIR=/srv/storage\n", newline="\n")
        for name in ("provision-db.sh", "backup-production.sh"):
            sh("docker", "cp", str(root / "scripts" / name), f"{tool}:/work/scripts/{name}")
        sh("docker", "cp", str(work / "admin.cnf"), f"{tool}:/work/admin.cnf")
        sh("docker", "cp", str(env), f"{tool}:/work/env")
    inside("chmod 600 /work/admin.cnf /work/env; chmod +x /work/scripts/*.sh")

    deadline = time.time() + 120
    while True:
        if inside("mysql --defaults-extra-file=/work/admin.cnf -e 'select 1'", check=False).returncode == 0:
            break
        assert time.time() < deadline, "MySQL tidak siap dalam 120 detik"
        time.sleep(2)

    # ---- provision-db.sh: SQL dijalankan sungguhan, hak dibuktikan dari container di subnet itu.
    out = inside(
        f"LARAVEL_DB_PASSWORD={APP_PW} /work/scripts/provision-db.sh --subnet {subnet} --db lm_prov --user lm_app "
        "| mysql --defaults-extra-file=/work/admin.cnf --batch --skip-column-names"
    ).stdout
    assert "GRANT SELECT, INSERT, UPDATE, DELETE ON `lm_prov`.*" in out, out
    assert "ALL PRIVILEGES" not in out and "GRANT OPTION" not in out

    def app(sql):
        return inside(f"MYSQL_PWD={APP_PW} mysql -h {srv} -u lm_app --batch --skip-column-names -e \"{sql}\"", check=False)

    assert app("select 1").returncode == 0, "akun aplikasi harus bisa konek dari subnet Docker"
    admin_sql("create table lm_prov.t(id int primary key, v varchar(20)) engine=InnoDB")
    assert app("insert into lm_prov.t values (1,'a'); update lm_prov.t set v='b'; select v from lm_prov.t; delete from lm_prov.t").returncode == 0
    assert app("create table lm_prov.x(id int)").returncode != 0, "akun aplikasi tidak boleh membuat tabel"
    assert app("drop table lm_prov.t").returncode != 0, "akun aplikasi tidak boleh drop"
    assert app("select 1 from mysql.user limit 1").returncode != 0, "akun aplikasi tidak boleh membaca mysql.user"
    # Ulang: database/akun sudah ada -> harus gagal, bukan menimpa.
    again = inside(
        f"LARAVEL_DB_PASSWORD={APP_PW} /work/scripts/provision-db.sh --subnet {subnet} --db lm_prov --user lm_app "
        "| mysql --defaults-extra-file=/work/admin.cnf --batch", check=False)
    assert again.returncode != 0, "provisioning ulang harus gagal"
    # Subnet lain tidak boleh cocok: host pattern bermasker, bukan wildcard.
    host_pat = admin_sql("select host from mysql.user where user='lm_app'").stdout.strip()
    assert "/" in host_pat and "%" not in host_pat, host_pat

    # ---- backup-production.sh lawan DB berisi InnoDB + MyISAM + dokumen.
    admin_sql("create database labmania_src character set utf8mb4 collate utf8mb4_unicode_ci")
    admin_sql("create table a(id int primary key, nama varchar(50), blobcol blob) engine=InnoDB; "
              "insert into a values (1,CONVERT(UNHEX('C39C6EC3AF636F646520E29C93') USING utf8mb4),0x00ff10),(2,'dua',NULL); "
              "create table coa(id int primary key, nm varchar(20)) engine=MyISAM; insert into coa values (7,'kas');", "labmania_src")
    inside("echo isi > /srv/storage/app/private/documents/a.pdf; echo sampah > /srv/storage/app/mpdf/junk.tmp")

    B = "/work/scripts/backup-production.sh --env /work/env --defaults-extra-file /work/admin.cnf"
    r = inside(f"{B} --out /srv/out", check=False)
    assert r.returncode == 2 and "MyISAM" in r.stderr and "--writers-stopped" in r.stderr, r.stderr
    assert inside("ls /srv/out").stdout.strip() == "", "tanpa --writers-stopped tidak boleh membuat folder"

    r = inside("/work/scripts/backup-production.sh --env /work/env --defaults-extra-file /work/admin.cnf --out /work/out", check=False)
    assert r.returncode == 2 and "di dalam repository" in r.stderr, r.stderr

    r = inside(f"{B} --out /srv/out --writers-stopped", check=False)
    assert r.returncode == 0, r.stdout + r.stderr
    assert "PASS  backup utuh" in r.stdout
    assert ROOT_PW not in r.stdout + r.stderr, "secret tercetak"
    dest = inside("ls -d /srv/out/labmania-*").stdout.strip()
    files = set(inside(f"ls {dest}").stdout.split())
    assert files == {"SHA256SUMS", "db-labmania_src.sql.gz", "manifest.txt", "storage-app.tar.gz"}, files
    assert inside(f"stat -c %a {dest}").stdout.strip() == "700"
    manifest = inside(f"cat {dest}/manifest.txt").stdout
    assert "a\t2" in manifest and "coa\t1" in manifest and "1 berkas" in manifest, manifest
    assert "TIDAK disertakan" in manifest
    dump = inside(f"zcat {dest}/db-labmania_src.sql.gz").stdout
    assert "CREATE DATABASE" not in dump and "\nUSE " not in dump and "DROP DATABASE" not in dump
    tar = inside(f"tar -tzf {dest}/storage-app.tar.gz").stdout
    assert "app/private/documents/a.pdf" in tar and "mpdf" not in tar, tar

    # Restore ke database kosong: hasil harus sama dengan sumber.
    admin_sql("create database labmania_restore character set utf8mb4 collate utf8mb4_unicode_ci")
    inside(f"zcat {dest}/db-labmania_src.sql.gz | mysql --defaults-extra-file=/work/admin.cnf labmania_restore")
    assert admin_sql("select count(*), hex(blobcol) from a where id=1 group by blobcol", "labmania_restore").stdout.split() == ["1", "00FF10"]
    # Bandingkan byte (hex): argv Windows tidak aman untuk non-ASCII, tetapi dump/restore harus utuh di level byte.
    assert admin_sql("select hex(nama) from a where id=1", "labmania_restore").stdout.strip() == "C39C6EC3AF636F646520E29C93"
    assert admin_sql("select nm from coa", "labmania_restore").stdout.strip() == "kas"

    # --verify mendeteksi kerusakan.
    assert inside(f"/work/scripts/backup-production.sh --verify {dest}", check=False).returncode == 0
    inside(f"printf 'x' >> {dest}/storage-app.tar.gz")
    bad = inside(f"/work/scripts/backup-production.sh --verify {dest}", check=False)
    assert bad.returncode == 1 and "FAIL" in bad.stderr, bad.stderr
    # Dump terpotong (tanpa baris '-- Dump completed') harus ditolak walau hash disesuaikan.
    inside(f"cd {dest} && zcat db-labmania_src.sql.gz | head -n -3 | gzip > db-labmania_src.sql.gz.new && "
           f"mv db-labmania_src.sql.gz.new db-labmania_src.sql.gz && sha256sum -- * | grep -v SHA256SUMS > SHA256SUMS")
    cut = inside(f"/work/scripts/backup-production.sh --verify {dest}", check=False)
    assert cut.returncode == 1 and "tidak lengkap" in cut.stderr, cut.stderr

    # Database sumber tidak ada -> berhenti sebelum membuat folder.
    inside("sed -i 's/labmania_src/tidak_ada/' /work/env")
    r = inside(f"{B} --out /srv/out2 --writers-stopped", check=False)
    assert r.returncode == 2 and "tidak ada" in r.stderr and inside("ls /srv/out2 2>/dev/null | wc -l").stdout.strip() == "0", r.stderr

    print("Provision + backup lulus lawan MySQL 8.4 sementara.")
finally:
    for name in (tool, srv):
        sh("docker", "rm", "-f", "-v", name, check=False)
    sh("docker", "network", "rm", net, check=False)
