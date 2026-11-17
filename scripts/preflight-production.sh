#!/usr/bin/env bash
# Preflight server production Labmania. Hanya membaca: tidak mengubah apa pun dan tidak mencetak secret.
# Pakai (dari server Ubuntu): scripts/preflight-production.sh [--env FILE] [--offline] [--db]
#   --env FILE  file environment (default .env.production)
#   --offline   lewati pemeriksaan Docker/port/MySQL host; hanya periksa env dan direktori
#   --db        uji koneksi DB dan COA dari dalam container (butuh image sudah dibuild)
# Exit 1 bila ada FAIL. WARN tidak menggagalkan, tetapi harus dibaca operator.
set -u
ENV_FILE=.env.production; OFFLINE=0; DB=0
while [ $# -gt 0 ]; do
    case $1 in
        --env) ENV_FILE=${2:?--env butuh path}; shift;;
        --offline) OFFLINE=1;;
        --db) DB=1;;
        -h|--help) sed -n 2,7p "$0"; exit 0;;
        *) echo "Opsi tidak dikenal: $1" >&2; exit 2;;
    esac
    shift
done
ROOT=$(cd "$(dirname "$0")/.." && pwd) || exit 2
cd "$ROOT" || exit 2
case $ENV_FILE in /*|[A-Za-z]:*) ;; *) ENV_FILE=$ROOT/$ENV_FILE;; esac
COMPOSE=(docker compose -p labmania-production --env-file "$ENV_FILE" -f compose.production.yaml)

fails=0; warns=0
pass() { echo "PASS  $*"; }
warn() { echo "WARN  $*"; warns=$((warns + 1)); }
fail() { echo "FAIL  $*"; fails=$((fails + 1)); }
ver_ge() { [ "$(printf '%s\n%s\n' "$1" "$2" | sort -V | head -n1)" = "$2" ]; }
is_placeholder() { case $1 in GANTI_*|'<'*'>') return 0;; esac; return 1; }

# Baca KEY dari file env tanpa meng-source (secret bisa memuat $ dan karakter khusus).
val() {
    local line
    line=$(grep -E "^$1=" "$ENV_FILE" | tail -n1)
    line=${line#*=}; line=${line%$'\r'}
    case $line in \'*\'|\"*\") line=${line:1:${#line}-2};; esac
    printf '%s' "$line"
}

echo "== Environment: $ENV_FILE"
if [ ! -f "$ENV_FILE" ]; then
    fail "file environment tidak ada (salin dari .env.production.example)"
else
    perm=$(stat -c %a "$ENV_FILE" 2>/dev/null)
    case $perm in 600|400) pass "izin file $perm";; *) fail "izin file $perm; jalankan chmod 600";; esac
    git ls-files --error-unmatch -- "$ENV_FILE" >/dev/null 2>&1 && fail "file environment masuk Git"

    for k in APP_KEY APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD PRODUCTION_STORAGE_DIR PRODUCTION_FONT_DIR; do
        v=$(val "$k")
        if [ -z "$v" ]; then fail "$k kosong"
        elif is_placeholder "$v"; then fail "$k masih placeholder"
        fi
    done

    v=$(val APP_KEY)
    if [ -n "$v" ] && ! is_placeholder "$v"; then
        [[ $v =~ ^base64:[A-Za-z0-9+/]{43}=$ ]] && pass "format APP_KEY" || fail "format APP_KEY bukan base64 32 byte"
    fi
    v=$(val APP_URL)
    if [ -n "$v" ]; then
        [[ $v =~ ^https://[^/]+/labmania/?$ ]] && pass "APP_URL $v" \
            || fail "APP_URL harus https://<host>/labmania (Compose mengunci VITE_BASE dan SESSION_PATH ke /labmania)"
    fi
    [ "$(val DB_USERNAME)" = root ] && fail "DB_USERNAME tidak boleh root"
    [ "$(val DB_DATABASE)" = db_labmania ] && fail "DB_DATABASE db_labmania ditolak startup Compose"

    empty=()
    for k in COA_DB_PASSWORD INVOICE_COA_PIUTANG_ID INVOICE_COA_PPN_DN_ID INVOICE_COA_PUBLIC_ID INVOICE_COA_B2B_ID \
             INVOICE_COA_UANG_MUKA_WAPU_ID INVOICE_COA_HUTANG_WAPU_ID INVOICE_BANK INVOICE_REKENING INVOICE_ATAS_NAMA \
             INVOICE_PENANDA_TANGAN INVOICE_JABATAN; do
        v=$(val "$k"); { [ -z "$v" ] || is_placeholder "$v"; } && empty+=("$k")
    done
    [ ${#empty[@]} -gt 0 ] && warn "kosong, operasi finance/cetak terkait akan ditahan: ${empty[*]}"
    case $(val COA_DB_HOST) in
        ''|127.0.0.1|localhost) warn "COA_DB_HOST kosong/loopback: di container itu berarti aplikasi sendiri; pakai host.docker.internal bila GL di host";;
    esac
fi

echo "== Direktori host"
if [ -f "$ENV_FILE" ]; then
    storage=$(val PRODUCTION_STORAGE_DIR); fonts=$(val PRODUCTION_FONT_DIR)
    if [ -n "$storage" ] && ! is_placeholder "$storage"; then
        case $storage in /*) ;; *) fail "PRODUCTION_STORAGE_DIR harus path absolut";; esac
        case $storage/ in "$ROOT"/*) fail "PRODUCTION_STORAGE_DIR berada di dalam repository";; esac
        if [ ! -d "$storage" ]; then fail "storage tidak ada: $storage"
        else
            owner=$(stat -c %u:%g "$storage")
            [ "$owner" = 33:33 ] && pass "pemilik storage 33:33 (www-data)" || fail "pemilik storage $owner, harus 33:33: chown 33:33 $storage"
            if [ ! -d "$storage/app/private/documents" ]; then
                warn "app/private/documents belum ada: dokumen historis belum dipulihkan?"
            elif [ -z "$(find "$storage/app/private/documents" -type f -print -quit 2>/dev/null)" ]; then
                warn "app/private/documents kosong: volume kosong bukan pemindahan upload"
            else pass "dokumen privat ada"; fi
        fi
    fi
    if [ -n "$fonts" ] && ! is_placeholder "$fonts"; then
        if [ ! -d "$fonts" ]; then fail "folder font tidak ada: $fonts"
        else
            for f in times.ttf timesbd.ttf; do
                [ -f "$fonts/$f" ] && pass "font $f" || fail "font $f tidak ada di $fonts"
            done
        fi
    fi
fi

if [ "$OFFLINE" = 0 ]; then
    echo "== Docker"
    if ! command -v docker >/dev/null 2>&1; then
        fail "docker tidak terpasang"
    else
        dv=$(docker --version 2>/dev/null | sed -E 's/^Docker version ([0-9.]+).*/\1/')
        ver_ge "${dv:-0}" 20.10 && pass "Docker $dv" || fail "Docker ${dv:-?} < 20.10"
        cv=$(docker compose version --short 2>/dev/null | sed 's/^v//')
        ver_ge "${cv:-0}" 2.24 && pass "Compose $cv" || fail "Compose plugin ${cv:-tidak ada} < 2.24"
        if [ ! -f "$ENV_FILE" ]; then :
        elif [ "$ENV_FILE" != "$ROOT/.env.production" ]; then
            # compose.production.yaml memuat env_file: .env.production secara tetap.
            warn "compose config dilewati: hanya valid untuk $ROOT/.env.production"
        elif err=$("${COMPOSE[@]}" config --quiet 2>&1); then pass "compose config valid"
        else fail "compose config gagal: $(echo "$err" | head -n3 | tr '\n' ' ')"; fi
    fi

    echo "== Jaringan host"
    if ! command -v ss >/dev/null 2>&1; then
        warn "perintah ss tidak ada; lewati cek port dan listener MySQL"
    else
        port=$(val PRODUCTION_HTTP_PORT); port=${port:-8081}
        if ss -lnt "sport = :$port" | tail -n +2 | grep -q .; then
            if [ -n "$("${COMPOSE[@]}" ps -q app 2>/dev/null)" ]; then pass "port $port dipakai container production (update)"
            else fail "port $port sudah dipakai proses lain"; fi
        else pass "port $port bebas"; fi

        dbport=$(val DB_PORT); dbport=${dbport:-3306}
        gw=$(docker network inspect bridge --format '{{(index .IPAM.Config 0).Gateway}}' 2>/dev/null)
        addrs=$(ss -lnt "sport = :$dbport" | tail -n +2 | awk '{print $4}')
        if [ -z "$addrs" ]; then fail "tidak ada listener MySQL di port $dbport"
        else
            reach=0; open=0
            while read -r a; do
                a=${a%:*}
                case $a in
                    127.*|'[::1]') ;;
                    0.0.0.0|'*'|'[::]') open=1; reach=1;;
                    *) [ "$a" = "$gw" ] && reach=1;;
                esac
            done <<<"$addrs"
            if [ $reach = 0 ]; then fail "MySQL hanya di loopback; container tidak bisa mengakses (gateway bridge: ${gw:-?}). Tambah listener di IP itu, jangan 0.0.0.0"
            elif [ $open = 1 ]; then warn "MySQL mendengarkan semua interface; pastikan firewall membatasi $dbport ke subnet Docker"
            else pass "MySQL mendengarkan di gateway bridge $gw"; fi
        fi
        echo "INFO  Cek manual: grant akun Laravel dari subnet network labmania-production_default dan aturan firewall."
    fi

    if [ "$DB" = 1 ] && [ -f "$ENV_FILE" ] && [ "$fails" = 0 ]; then
        echo "== Koneksi dari container (--db)"
        php_probe='$o=[PDO::ATTR_TIMEOUT=>5,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];$bad=0;
foreach(["DB","COA_DB"] as $p){$h=getenv("{$p}_HOST");if(!$h){echo "SKIP $p\n";continue;}
try{$d=new PDO("mysql:host=$h;port=".(getenv("{$p}_PORT")?:3306).";dbname=".getenv("{$p}_DATABASE"),getenv("{$p}_USERNAME"),getenv("{$p}_PASSWORD"),$o);
$n=$d->query("select count(*) from information_schema.tables where table_schema=database()")->fetchColumn();echo "OK $p tabel=$n\n";}
catch(Throwable $e){echo "ERR $p kode=".$e->getCode()."\n";$bad=1;}}exit($bad);'
        out=$("${COMPOSE[@]}" run --rm --no-deps -T app php -r "$php_probe" 2>/dev/null); rc=$?
        while read -r status name rest; do
            case $status in
                OK) pass "koneksi $name ($rest)"; [ "$rest" = "tabel=0" ] && warn "$name tidak punya tabel: restore belum dilakukan?";;
                ERR) fail "koneksi $name gagal ($rest): cek listener, grant dari subnet Docker, firewall, kredensial";;
                SKIP) warn "$name dilewati (host tidak diisi)";;
            esac
        done <<<"$out"
        [ $rc -ne 0 ] && [ -z "$out" ] && fail "probe container gagal dijalankan (image sudah dibuild?)"
    fi
fi

echo
echo "Ringkasan: $fails FAIL, $warns WARN"
[ "$fails" = 0 ]
