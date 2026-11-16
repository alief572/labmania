"""Validasi model Compose tanpa membuat container atau membaca rahasia."""

import base64
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


@unittest.skipUnless(shutil.which("docker"), "Docker CLI diperlukan; jalankan lewat WSL")
class RealdataConfigTest(unittest.TestCase):
    def test_projects_are_isolated_and_only_loopback_8081_is_published(self):
        here = Path(__file__).resolve().parent
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            env = root / "compose.env"
            values = {
                "REHEARSAL_ENV_FILE": str(env),
                "APP_KEY": "base64:" + base64.b64encode(bytes(32)).decode(),
                "DB_PASSWORD": "0" * 64,
                "REHEARSAL_DB_ROOT_PASSWORD": "1" * 64,
                "MIGRATION_SOURCE_PASSWORD": "2" * 64,
                "MIGRATION_LOADER_PASSWORD": "3" * 64,
                "REHEARSAL_BACKUP_PASSWORD": "4" * 64,
                "REHEARSAL_SNAPSHOT_DIR": str(root / "snapshot"),
                "REHEARSAL_FONT_DIR": str(root / "fonts"),
                "REHEARSAL_BACKUP_DIR": str(root / "backups"),
            }
            env.write_text("\n".join(f"{key}={value}" for key, value in values.items()), encoding="utf-8")
            volume_names = set()
            for suffix in ("a", "b", "restore"):
                project = "labmania-realdata-" + suffix
                result = subprocess.run(
                    ["docker", "compose", "--env-file", str(env), "-f", str(here / "compose.yaml"),
                     "-f", str(here / "compose.realdata.yaml"), "-p", project, "--profile", "preview", "config", "--format", "json"],
                    capture_output=True, text=True, check=True,
                )
                config = json.loads(result.stdout)
                app = config["services"]["app"]
                settings = app["environment"]
                self.assertEqual(project, config["name"])
                self.assertEqual(["preview"], app["profiles"])
                self.assertEqual(1, len(app["ports"]))
                port = app["ports"][0]
                self.assertEqual(("127.0.0.1", "8081", 80), (port["host_ip"], port["published"], port["target"]))
                self.assertEqual("rehearsal", settings["APP_ENV"])
                self.assertEqual("rehearsal-db", settings["DB_HOST"])
                self.assertEqual("false", settings["LOCAL_UAT_ENABLED"])
                self.assertEqual("false", settings["APP_DEBUG"])
                self.assertEqual("log", settings["MAIL_MAILER"])
                self.assertEqual("false", settings["AUDIT_RETENTION_ENABLED"])
                self.assertNotIn("ports", config["services"]["rehearsal-db"])
                mode = next(x for x in config["services"]["rehearsal-db"]["command"] if x.startswith("--sql-mode="))
                modes = set(mode.split("=", 1)[1].split(","))
                self.assertIn("STRICT_TRANS_TABLES", modes)
                self.assertNotIn("NO_ZERO_DATE", modes)
                self.assertNotIn("ALLOW_INVALID_DATES", modes)
                self.assertTrue(config["networks"]["rehearsal"]["internal"])
                self.assertEqual({"rehearsal", "preview-web"}, set(app["networks"]))
                self.assertFalse(config["networks"]["preview-web"].get("internal", False))
                self.assertEqual({"rehearsal"}, set(config["services"]["rehearsal-db"]["networks"]))
                snapshot = next(v for v in app["volumes"] if v["target"] == "/snapshot")
                self.assertTrue(snapshot["read_only"])
                for volume in config["volumes"].values():
                    self.assertTrue(volume["name"].startswith(project + "_"))
                    self.assertNotIn(volume["name"], volume_names)
                    volume_names.add(volume["name"])
                promoted = subprocess.run(
                    ["docker", "compose", "--env-file", str(env), "-f", str(here / "compose.yaml"),
                     "-f", str(here / "compose.realdata.yaml"), "-f", str(here.parent / "local/compose.realdata-8080.yaml"),
                     "-p", project, "config", "--format", "json"], capture_output=True, text=True, check=True,
                )
                active = json.loads(promoted.stdout)["services"]["app"]
                self.assertFalse(active.get("profiles"))
                self.assertEqual("http://localhost:8080", active["environment"]["APP_URL"])
                self.assertEqual("labmania_realdata_session", active["environment"]["SESSION_COOKIE"])
                self.assertEqual(1, len(active["ports"]))
                self.assertEqual(("127.0.0.1", "8080"), (active["ports"][0]["host_ip"], active["ports"][0]["published"]))
                self.assertEqual(app["volumes"], active["volumes"])


if __name__ == "__main__":
    unittest.main()
