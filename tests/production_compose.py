"""Kontrak Compose production; hanya render konfigurasi, tidak mengakses daemon/DB."""

import json
from pathlib import Path
import subprocess
import tempfile


root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory() as folder:
    staging = Path(folder)
    compose = staging / "compose.production.yaml"
    compose.write_bytes((root / compose.name).read_bytes())
    values = {
        "APP_KEY": "base64:" + "A" * 43 + "=",
        "APP_URL": "https://example.invalid/labmania",
        "DB_HOST": "127.0.0.1",
        "DB_DATABASE": "laravel_fixture",
        "DB_USERNAME": "laravel_fixture",
        "DB_PASSWORD": "fixture$literal#password",
        "PRODUCTION_STORAGE_DIR": str(staging / "storage"),
        "PRODUCTION_FONT_DIR": str(staging / "fonts"),
        # Environment privat tidak boleh mengalahkan pembatas Compose.
        "APP_ENV": "local",
        "APP_DEBUG": "true",
        "DB_URL": "mysql://fixture.invalid/should-not-be-used",
        "LOCAL_UAT_ENABLED": "true",
    }
    env = staging / ".env.production"

    def render(settings):
        env.write_text("\n".join(f"{key}='{value}'" for key, value in settings.items()), encoding="utf-8")
        return subprocess.run(
            ["docker", "compose", "-p", "labmania-production", "--env-file", str(env),
             "-f", str(compose), "config", "--format", "json"],
            capture_output=True, text=True,
        )

    result = render(values)
    assert result.returncode == 0, result.stderr
    config = json.loads(result.stdout)
    assert config["name"] == "labmania-production"
    assert set(config["services"]) == {"app"}
    app = config["services"]["app"]
    assert app["build"]["dockerfile"] == "rehearsal/Dockerfile"
    assert app["environment"]["APP_ENV"] == "production"
    assert app["environment"]["APP_DEBUG"] == "false"
    assert app["environment"]["DB_URL"] == ""
    assert app["environment"]["DB_HOST"] == "host.docker.internal"
    assert app["environment"]["LOCAL_UAT_ENABLED"] == "false"
    # Compose escape ulang $ pada hasil config agar hasilnya dapat dipakai sebagai Compose.
    assert app["environment"]["DB_PASSWORD"].replace("$$", "$") == values["DB_PASSWORD"]
    assert app["environment"]["SESSION_SECURE_COOKIE"] == "true"
    assert app["environment"]["TRUSTED_PROXIES"] == "*"
    assert app["environment"]["SESSION_PATH"] == "/labmania"
    assert app["build"]["args"]["VITE_BASE"] == "/labmania/build/"
    assert app["environment"]["AUDIT_RETENTION_ENABLED"] == "false"
    assert len(app["ports"]) == 1
    assert app["ports"][0]["host_ip"] == "127.0.0.1"
    assert str(app["ports"][0]["published"]) == "8081"
    assert app["ports"][0]["target"] == 80
    mounts = {item["target"]: item for item in app["volumes"]}
    vhost = "/etc/apache2/sites-available/000-default.conf"
    assert set(mounts) == {"/var/www/laravel/storage", "/fonts", vhost}
    for mount in mounts.values():
        assert mount["type"] == "bind"
        assert not mount.get("bind", {}).get("create_host_path", False)
    assert mounts["/fonts"]["read_only"] is True
    assert mounts[vhost]["read_only"] is True
    assert mounts[vhost]["source"].replace("\\", "/").endswith("rehearsal/apache.production.conf")
    assert app["healthcheck"]["test"][:2] == ["CMD", "php"]
    assert app["extra_hosts"] in (
        {"host.docker.internal": "host-gateway"},
        {"host.docker.internal": ["host-gateway"]},
        ["host.docker.internal=host-gateway"],
    ), repr(app["extra_hosts"])
    assert set(config["networks"]) == {"default"}
    assert not config["networks"]["default"].get("external", False)
    startup = app["command"][2].replace("$$", "$")
    subprocess.run(["sh", "-n"], input=startup, text=True, check=True)
    assert 'test "$DB_DATABASE" != db_labmania' in startup
    assert 'test "$DB_USERNAME" != root' in startup
    assert not any(word in startup for word in ["migrate", "seed", "key:generate", "storage:link"])

    for required in ["APP_KEY", "APP_URL", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD",
                     "PRODUCTION_STORAGE_DIR", "PRODUCTION_FONT_DIR"]:
        missing = dict(values, **{required: ""})
        assert render(missing).returncode != 0, f"Nilai kosong harus ditolak: {required}"

    custom_port = json.loads(render(dict(values, PRODUCTION_HTTP_PORT="18081")).stdout)
    assert str(custom_port["services"]["app"]["ports"][0]["published"]) == "18081"

print("Kontrak Compose production lulus; tidak ada container/DB dijalankan.")
