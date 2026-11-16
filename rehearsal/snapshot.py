"""Siapkan manifest draft dari salinan lengkap; tidak membaca database aktif."""

import argparse
import base64
import datetime
import hashlib
import json
import os
from pathlib import Path
import re
import secrets


def checksum(path):
    with path.open("rb") as stream:
        return hashlib.file_digest(stream, "sha256").hexdigest()


def files(root):
    result = []
    if root.is_symlink() or not root.is_dir():
        raise ValueError("Root berkas harus folder salinan biasa.")
    for path in sorted(root.rglob("*")):
        if path.is_symlink():
            raise ValueError("Symlink snapshot ditolak.")
        if path.is_file():
            result.append({"path": path.relative_to(root).as_posix(), "size": path.stat().st_size, "sha256": checksum(path)})
    return result


def write_private(path, content):
    # Windows mewarisi ACL folder privat pemilik; jangan mencabut ACL sandbox/runtime.
    descriptor = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, "w", encoding="utf-8", newline="\n") as stream:
        stream.write(content)


def draft(root, dump, snapshot_id, captured_at):
    if root.is_symlink():
        raise ValueError("Root snapshot tidak boleh symlink.")
    root = root.resolve(strict=True)
    dump = root / dump
    if dump.is_symlink() or not dump.is_file() or not dump.resolve().is_relative_to(root):
        raise ValueError("Dump wajib berada dalam root snapshot.")
    if dump.stat().st_size == 0:
        raise ValueError("Dump snapshot kosong.")
    if datetime.datetime.fromisoformat(captured_at).utcoffset() is None:
        raise ValueError("Waktu snapshot wajib menyertakan zona waktu.")
    return {
        "version": 1,
        "snapshot": {"id": snapshot_id, "captured_at": captured_at, "consistent": True,
                     "dump": {"path": dump.relative_to(root).as_posix(), "sha256": checksum(dump)},
                     "files_root": "files", "files": files(root / "files")},
        "approval": {"by": None, "at": None},
        "tables": [], "excluded_source_tables": {}, "empty_target_tables": {}, "relations": [],
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    manifest = commands.add_parser("manifest")
    manifest.add_argument("--root", required=True, type=Path)
    manifest.add_argument("--dump", default="snapshot.sql")
    manifest.add_argument("--id", required=True)
    manifest.add_argument("--captured-at", required=True)
    manifest.add_argument("--consistent", action="store_true", required=True, help="Pemilik data menjamin DB dan berkas konsisten, termasuk tabel MyISAM")
    manifest.add_argument("--output", default="manifest-draft.json")
    env = commands.add_parser("init-env")
    env.add_argument("--snapshot", required=True, type=Path)
    env.add_argument("--fonts", required=True, type=Path)
    env.add_argument("--backups", required=True, type=Path)
    env.add_argument("--output", default=".env.rehearsal")
    env.add_argument("--key-from", type=Path, help="Salin hanya APP_KEY dan password backup dari environment rehearsal asal")
    args = parser.parse_args()
    if args.command == "manifest":
        if Path(args.output).name != args.output:
            raise ValueError("Output harus nama file baru dalam root snapshot.")
        data = draft(args.root, args.dump, args.id, args.captured_at)
        destination = args.root / args.output
        write_private(destination, json.dumps(data, indent=2, ensure_ascii=False) + "\n")
    else:
        if not re.fullmatch(r"\.env\.rehearsal(?:-[a-z0-9-]+)?", args.output):
            raise ValueError("Output wajib nama .env.rehearsal atau .env.rehearsal-<nama>.")
        snapshot = args.snapshot.resolve(strict=True)
        fonts = args.fonts.resolve(strict=True)
        if not snapshot.is_dir() or not all((fonts / name).is_file() for name in ["times.ttf", "timesbd.ttf"]):
            raise ValueError("Snapshot atau font berlisensi belum tersedia.")
        context = Path(__file__).resolve().parents[1]
        if any(path.is_relative_to(context) for path in [snapshot, fonts, args.backups.resolve()]):
            raise ValueError("Snapshot, font dan backup baru wajib di luar root repository agar tidak masuk build context image.")
        args.backups.mkdir(parents=True, exist_ok=True)
        destination = Path(__file__).parent / args.output
        template = (Path(__file__).parent / ".env.rehearsal.example").read_text(encoding="utf-8")
        values = {key: secrets.token_hex(32) for key in ["DB_PASSWORD", "REHEARSAL_DB_ROOT_PASSWORD", "MIGRATION_SOURCE_PASSWORD", "MIGRATION_LOADER_PASSWORD", "REHEARSAL_BACKUP_PASSWORD"]}
        values["APP_KEY"] = "base64:" + base64.b64encode(secrets.token_bytes(32)).decode("ascii")
        if args.key_from:
            source = args.key_from.resolve(strict=True)
            if source.is_relative_to(context) and source.parent != Path(__file__).resolve().parent:
                raise ValueError("Environment asal dalam build context harus berada di folder rehearsal.")
            inherited = {}
            for line in source.read_text(encoding="utf-8").splitlines():
                key, _, value = line.partition("=")
                if key in ("APP_KEY", "REHEARSAL_BACKUP_PASSWORD"):
                    if key in inherited:
                        raise ValueError("Environment asal memuat key ganda.")
                    inherited[key] = json.loads(value) if value.startswith('"') else value
            key = inherited.get("APP_KEY", "")
            password = inherited.get("REHEARSAL_BACKUP_PASSWORD", "")
            if not isinstance(key, str) or not key.startswith("base64:") or len(base64.b64decode(key[7:], validate=True)) != 32:
                raise ValueError("APP_KEY asal tidak valid.")
            if not isinstance(password, str) or len(password) < 16:
                raise ValueError("Password backup asal tidak valid.")
            values.update(inherited)
        values["REHEARSAL_ENV_FILE"] = args.output
        values.update({"REHEARSAL_SNAPSHOT_DIR": snapshot.as_posix(), "REHEARSAL_FONT_DIR": fonts.as_posix(), "REHEARSAL_BACKUP_DIR": args.backups.resolve().as_posix()})
        lines = []
        for line in template.splitlines():
            key = line.partition("=")[0]
            value = values.get(key)
            if value is not None:
                if any(char in value for char in '\n\r\x00"$'):
                    raise ValueError("Path environment mengandung karakter yang tidak didukung.")
                line = key + '="' + value + '"'
            lines.append(line)
        write_private(destination, "\n".join(lines) + "\n")
    print("File privat baru:", destination)


if __name__ == "__main__":
    main()
