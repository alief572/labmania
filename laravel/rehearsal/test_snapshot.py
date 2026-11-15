import tempfile
import unittest
import contextlib
import io
import json
from pathlib import Path
from unittest.mock import patch

from snapshot import draft, files, main, write_private


class SnapshotTest(unittest.TestCase):
    def test_manifest_hash_and_no_overwrite(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / "files").mkdir()
            (root / "snapshot.sql").write_text("-- synthetic", encoding="utf-8")
            (root / "files" / "sample.pdf").write_bytes(b"%PDF")
            data = draft(root, "snapshot.sql", "synthetic", "2026-11-14T10:00:00+07:00")
            self.assertEqual(1, len(data["snapshot"]["files"]))
            self.assertEqual(4, data["snapshot"]["files"][0]["size"])
            self.assertIsNone(data["approval"]["by"])
            self.assertEqual([], data["tables"])
            output = root / "private.json"
            write_private(output, "first")
            with self.assertRaises(FileExistsError):
                write_private(output, "second")
            self.assertEqual("first", output.read_text())
            with self.assertRaises(ValueError):
                draft(root, "../outside.sql", "synthetic", "2026-11-14")

    def test_dockerignore_excludes_nested_env_and_private_inputs(self):
        # Regresi 14-11-2026: pola ".env*" hanya cocok di root context, sehingga rehearsal/.env.rehearsal
        # (berisi APP_KEY dan password) ikut tersalin ke image lewat COPY. Pola bersarang wajib ada.
        lines = [line.strip() for line in (Path(__file__).resolve().parents[1] / ".dockerignore").read_text(encoding="utf-8").splitlines()]
        self.assertIn("**/.env*", lines)
        self.assertIn("!.env.example", lines)
        self.assertIn("!rehearsal/.env.rehearsal.example", lines)
        self.assertLess(lines.index("**/.env*"), lines.index("!rehearsal/.env.rehearsal.example"), "negasi harus setelah pola yang dikecualikan")
        self.assertFalse(any(line.startswith("!**/") for line in lines), "negasi global memaksa traversal storage runtime yang privat")
        for private in ("rehearsal/snapshot", "rehearsal/backups", "rehearsal/recovery"):
            self.assertIn(private, lines)

    def test_missing_files_root_is_rejected(self):
        with tempfile.TemporaryDirectory() as folder:
            with self.assertRaises(ValueError):
                files(Path(folder) / "missing")

    def test_capture_requires_timezone_and_nonempty_dump(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / "files").mkdir()
            dump = root / "snapshot.sql"
            dump.write_text("-- fixture", encoding="utf-8")
            for timestamp in ("2026-11-14", "2026-11-14T10:00:00"):
                with self.subTest(timestamp=timestamp), self.assertRaises(ValueError):
                    draft(root, "snapshot.sql", "fixture", timestamp)
            dump.write_bytes(b"")
            with self.assertRaisesRegex(ValueError, "kosong"):
                draft(root, "snapshot.sql", "fixture", "2026-11-14T10:00:00+07:00")

    def test_separate_environments_share_only_app_key_and_backup_password(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            snapshot = root / "snapshot"
            fonts = root / "fonts"
            snapshot.mkdir()
            fonts.mkdir()
            for font in ("times.ttf", "timesbd.ttf"):
                (fonts / font).write_bytes(b"fixture")
            base = ["snapshot.py", "init-env", "--snapshot", str(snapshot), "--fonts", str(fonts),
                    "--backups", str(root / "backups")]

            def generate(arguments):
                with patch("sys.argv", base + arguments), patch("snapshot.write_private") as write, contextlib.redirect_stdout(io.StringIO()):
                    main()
                    path, content = write.call_args.args
                values = {key: json.loads(value) if value.startswith('"') else value
                          for key, _, value in (line.partition("=") for line in content.splitlines())}
                return path, content, values

            path, content, first = generate(["--output", ".env.rehearsal-realdata-a"])
            source = root / "a.env"
            source.write_text(content + 'INVOICE_BANK="BANK ASAL"\n', encoding="utf-8")
            _, _, second = generate(["--output", ".env.rehearsal-realdata-b", "--key-from", str(source)])
            self.assertEqual(".env.rehearsal-realdata-a", path.name)
            self.assertEqual(".env.rehearsal-realdata-b", second["REHEARSAL_ENV_FILE"])
            for key in ("APP_KEY", "REHEARSAL_BACKUP_PASSWORD"):
                self.assertEqual(first[key], second[key])
            for key in ("DB_PASSWORD", "REHEARSAL_DB_ROOT_PASSWORD", "MIGRATION_SOURCE_PASSWORD", "MIGRATION_LOADER_PASSWORD"):
                self.assertNotEqual(first[key], second[key])
            self.assertEqual("", second["INVOICE_BANK"])
            for output in ("../outside", ".env.production", ".env.rehearsal.example"):
                with self.subTest(output=output), self.assertRaises(ValueError):
                    generate(["--output", output])
            source.write_text('APP_KEY="invalid"\nREHEARSAL_BACKUP_PASSWORD="short"\n', encoding="utf-8")
            with self.assertRaises(ValueError):
                generate(["--key-from", str(source)])


if __name__ == "__main__":
    unittest.main()
