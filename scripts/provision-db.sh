#!/usr/bin/env bash
# Hasilkan SQL pembuatan database + akun Laravel untuk MySQL host Ubuntu. Script ini TIDAK menjalankan SQL
# dan tidak pernah menyentuh database yang sudah ada: CREATE DATABASE/USER sengaja tanpa IF NOT EXISTS,
# jadi database atau akun yang sudah ada membuat eksekusi gagal (bukan tertimpa).
#
# Pakai (password lewat environment, bukan argumen; SQL dialirkan lewat pipe, jangan diarahkan ke file):
#   export LARAVEL_DB_PASSWORD="$(openssl rand -hex 24)"      # simpan ke .env.production sebagai DB_PASSWORD
#   scripts/provision-db.sh --subnet auto --dry-run            # tinjau SQL (password disamarkan)
#   scripts/provision-db.sh --subnet auto | sudo mysql
#
# Opsi:
#   --subnet CIDR|auto  subnet network Docker project, mis. 172.18.0.0/16. auto membaca dari network
#                       labmania-production_default (ada setelah: docker compose ... up --no-start app)
#   --db NAME           database Laravel baru (default labmania_laravel)
#   --user NAME         akun aplikasi (default labmania_laravel)
#   --gl-db NAME --gl-user NAME   opsional: akun SELECT-only untuk gl.coa_master; password GL_READER_PASSWORD
#   --dry-run           tampilkan SQL dengan password disamarkan
# Hak akun aplikasi hanya SELECT, INSERT, UPDATE, DELETE pada database itu; skema dimuat dari dump/restore
# oleh akun admin, dan migration rilis berikutnya juga dijalankan admin, bukan akun aplikasi.
set -euo pipefail

DB=labmania_laravel; APP_USER=labmania_laravel; SUBNET=; GL_DB=; GL_USER=; DRY=0
die() { echo "ERROR: $*" >&2; exit 2; }
while [ $# -gt 0 ]; do
    case $1 in
        --db) DB=${2:?--db butuh nilai}; shift;;
        --user) APP_USER=${2:?--user butuh nilai}; shift;;
        --subnet) SUBNET=${2:?--subnet butuh nilai}; shift;;
        --gl-db) GL_DB=${2:?--gl-db butuh nilai}; shift;;
        --gl-user) GL_USER=${2:?--gl-user butuh nilai}; shift;;
        --dry-run) DRY=1;;
        -h|--help) sed -n 2,22p "$0"; exit 0;;
        *) die "opsi tidak dikenal: $1";;
    esac
    shift
done

ident() { [[ $2 =~ ^[A-Za-z0-9_]{1,32}$ ]] || die "$1 harus 1-32 karakter [A-Za-z0-9_]: '$2'"; }
ident --db "$DB"; ident --user "$APP_USER"
case $DB in db_labmania|mysql|sys|information_schema|performance_schema) die "nama database '$DB' tidak boleh dipakai";; esac
[ "$APP_USER" = root ] && die "akun aplikasi tidak boleh root"
if [ -n "$GL_DB$GL_USER" ]; then
    [ -n "$GL_DB" ] && [ -n "$GL_USER" ] || die "--gl-db dan --gl-user harus diisi bersama"
    ident --gl-db "$GL_DB"; ident --gl-user "$GL_USER"
    [ "$GL_USER" = root ] && die "akun GL tidak boleh root"
fi

[ -n "$SUBNET" ] || die "--subnet wajib (mis. 172.18.0.0/16 atau auto)"
if [ "$SUBNET" = auto ]; then
    command -v docker >/dev/null 2>&1 || die "docker tidak ada; isi --subnet manual"
    SUBNET=$(docker network inspect labmania-production_default --format '{{range .IPAM.Config}}{{.Subnet}} {{end}}' 2>/dev/null \
        | tr ' ' '\n' | grep -E '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+$' | head -n1 || true)
    [ -n "$SUBNET" ] || die "network labmania-production_default belum ada; jalankan 'docker compose -p labmania-production --env-file .env.production -f compose.production.yaml up --no-start app' atau isi --subnet"
fi
OCT='(0|[1-9][0-9]{0,2})'
[[ $SUBNET =~ ^$OCT\.$OCT\.$OCT\.$OCT/([0-9]{1,2})$ ]] || die "subnet bukan CIDR IPv4: '$SUBNET'"
o=("${BASH_REMATCH[@]:1:4}"); prefix=${BASH_REMATCH[5]}
for x in "${o[@]}"; do [ "$x" -le 255 ] || die "oktet di luar 0-255: '$SUBNET'"; done
{ [ "$prefix" -ge 8 ] && [ "$prefix" -le 30 ]; } || die "prefix subnet harus 8-30 (menolak jaringan terlalu luas): /$prefix"
ip=$(( (o[0] << 24) | (o[1] << 16) | (o[2] << 8) | o[3] ))
mask=$(( (0xFFFFFFFF << (32 - prefix)) & 0xFFFFFFFF ))
dotted() { printf '%d.%d.%d.%d' $(($1 >> 24 & 255)) $(($1 >> 16 & 255)) $(($1 >> 8 & 255)) $(($1 & 255)); }
HOST="$(dotted $((ip & mask)))/$(dotted "$mask")"   # host pattern MySQL: jaringan/netmask

secret() { # NAMA_ENV label
    local v=${!1:-}
    if [ "$DRY" = 1 ]; then printf '***'; return; fi
    [ -n "$v" ] || die "$1 belum diisi (export lewat environment)"
    [[ $v =~ ^[A-Za-z0-9._~+=-]{20,128}$ ]] || die "$1 harus 20-128 karakter [A-Za-z0-9._~+=-] (tanpa kutip, backslash, spasi, \$); contoh: openssl rand -hex 24"
    printf '%s' "$v"
}
APP_PW=$(secret LARAVEL_DB_PASSWORD)
[ -n "$GL_DB" ] && GL_PW=$(secret GL_READER_PASSWORD)

cat <<SQL
-- Labmania production: database + akun aplikasi. Dibuat oleh scripts/provision-db.sh.
-- Gagal bila database/akun sudah ada (disengaja): tidak ada yang ditimpa.
CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$APP_USER'@'$HOST' IDENTIFIED BY '$APP_PW';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`$DB\`.* TO '$APP_USER'@'$HOST';
SHOW GRANTS FOR '$APP_USER'@'$HOST';
SQL
if [ -n "$GL_DB" ]; then
    cat <<SQL
-- GL/COA: akun baca-saja pada satu tabel. Verifikasi dulu bahwa $GL_DB adalah sumber GL yang sah.
CREATE USER '$GL_USER'@'$HOST' IDENTIFIED BY '${GL_PW}';
GRANT SELECT ON \`$GL_DB\`.\`coa_master\` TO '$GL_USER'@'$HOST';
SHOW GRANTS FOR '$GL_USER'@'$HOST';
SQL
fi
