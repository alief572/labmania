import datetime
from contextlib import redirect_stdout
import io
import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

import promote


class PromotionGateTest(unittest.TestCase):
    def test_repository_root_keeps_entrypoints_archive_and_private_build_exclusions(self):
        root = Path(__file__).resolve().parents[1]
        self.assertEqual(root, promote.ROOT)
        for name in ('artisan', 'composer.json', 'public/index.php', 'ci3/index.php'):
            self.assertTrue((root / name).is_file(), name)
        exclusions = set((root / '.dockerignore').read_text(encoding='utf-8').splitlines())
        self.assertTrue({'ci3', '.artifacts', 'specs', '**/.env*', 'docker-compose.yml'} <= exclusions)

    def accepted(self):
        return {'manifest_sha256': 'a' * 64, 'accepted_by': 'Pemilik fixture',
                'accepted_at': '2026-01-01T12:00:00+07:00', 'source_capture_evidence': 'Fixture, bukan pengesahan data asli',
                **dict.fromkeys(promote.CONFIRMATIONS, True)}

    def test_every_confirmation_must_be_true_and_hash_bound(self):
        data = self.accepted()
        promote.acceptance(data, 'a' * 64)
        for key in promote.CONFIRMATIONS:
            for value in (False, 'true', None):
                with self.subTest(key=key, value=value), self.assertRaises(RuntimeError):
                    promote.acceptance({**data, key: value}, 'a' * 64)
        with self.assertRaises(RuntimeError):
            promote.acceptance(data, 'b' * 64)

    def test_placeholder_and_missing_capture_evidence_or_timezone_are_rejected(self):
        data = self.accepted()
        for changes in ({'accepted_by': 'Rehearsal'}, {'accepted_by': ''}, {'source_capture_evidence': ''},
                        {'accepted_at': '2026-01-01'}, {'accepted_at': 'invalid'},
                        {'accepted_at': (datetime.datetime.now(datetime.timezone.utc) + datetime.timedelta(days=1)).isoformat()}):
            with self.subTest(changes=changes), self.assertRaises(RuntimeError):
                promote.acceptance({**data, **changes}, 'a' * 64)

    def test_finance_deferral_requires_explicit_acceptance_but_keeps_other_gates(self):
        data = {**self.accepted(), 'finance_confirmed': False, 'finance_deferred': True}
        promote.acceptance(data, 'a' * 64, defer_finance=True)
        for changes, defer in (({}, False), ({'finance_deferred': False}, True), ({'source_capture_confirmed': False}, True), ({'documents_confirmed': False}, True)):
            with self.subTest(changes=changes, defer=defer), self.assertRaises(RuntimeError):
                promote.acceptance({**data, **changes}, 'a' * 64, defer_finance=defer)

    def test_failed_runtime_or_missing_acceptance_never_stops_or_seeds_any_container(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            env = root / 'rehearsal/.env.rehearsal-realdata-c'
            env.parent.mkdir(parents=True)
            env.write_text('fixture only', encoding='utf-8')
            argv = ['promote.py', 'promote', '--project', 'labmania-realdata-c', '--env-file', str(env), '--manifest', 'fixture.json']
            for action, ready in (('promote', False), ('promote', True), ('preview-local', False), ('preview-local', True)):
                argv[1] = action
                def execute(args, **kwargs):
                    if args[-3:] == ['ps', '-q', 'app']:
                        return 'fixture-container'
                    if 'labmania:migrasi-verifikasi' in args:
                        return json.dumps({'verified': True, 'manifest_sha256': 'a' * 64})
                    if 'kesiapan' in args:
                        return json.dumps({'runtime_ready': ready, 'checks': {'finance': ready}})
                    self.fail('Mutasi dipanggil sebelum penerimaan: ' + args[0])
                with self.subTest(action=action, runtime_ready=ready), patch.object(promote, 'ROOT', root), patch.object(promote.sys, 'argv', argv), patch.object(promote, 'run', side_effect=execute), self.assertRaises(RuntimeError):
                    promote.main()
                self.assertFalse((root / '.artifacts').exists())
                self.assertFalse((root / 'docker-compose.yml').exists())

    def test_promotion_backs_up_before_stopping_uat_and_preserves_previous_compose(self):
        self.promotion('promote')

    def test_preview_local_requires_rehearsal_snapshot_and_never_asserts_production_acceptance(self):
        self.promotion('preview-local')

    def promotion(self, action):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            env = root / 'rehearsal/.env.rehearsal-realdata-c'
            env.parent.mkdir(parents=True)
            env.write_text('fixture only', encoding='utf-8')
            previous = root / 'docker-compose.yml'
            previous.write_text('previous UAT fixture', encoding='utf-8')
            (root / '.env').write_text('COMPOSE_PROJECT_NAME=labmania\nUNCHANGED=fixture\n', encoding='utf-8')
            approved = root / 'acceptance.json'
            approved.write_text(json.dumps(self.accepted()), encoding='utf-8')
            argv = ['promote.py', action, '--project', 'labmania-realdata-c', '--env-file', str(env), '--manifest', 'fixture.json']
            argv += ['--defer-finance'] if action == 'preview-local' else ['--acceptance', str(approved)]
            calls = []

            def execute(args, **kwargs):
                calls.append(args)
                if args[-3:] == ['ps', '-q', 'app']:
                    return 'fixture-container'
                if 'labmania:migrasi-verifikasi' in args:
                    return json.dumps({'verified': True, 'manifest_sha256': 'a' * 64, 'snapshot': 'REHEARSAL-fixture'})
                if 'kesiapan' in args:
                    return json.dumps({'runtime_ready': True, 'checks': {}})
                if args[:2] == ['docker', 'inspect']:
                    return json.dumps({'com.docker.compose.project': 'labmania', 'com.docker.compose.service': 'web'})
                if args[-1] == 'config':
                    return 'promoted fixture'
                return ''

            with patch.object(promote, 'ROOT', root), patch.object(promote.sys, 'argv', argv), patch.object(promote, 'run', side_effect=execute), redirect_stdout(io.StringIO()):
                promote.main()
            self.assertEqual('promoted fixture', previous.read_text())
            backups = list((root / '.artifacts').rglob('compose-sebelumnya.yaml'))
            self.assertEqual(1, len(backups))
            self.assertEqual('previous UAT fixture', backups[0].read_text())
            state = json.loads(backups[0].with_name('state.json').read_text())
            self.assertEqual(action == 'preview-local', state['local_preview'])
            self.assertFalse(state['production_accepted'])
            self.assertEqual('COMPOSE_PROJECT_NAME=labmania-realdata-c\nUNCHANGED=fixture\n', (root / '.env').read_text())
            self.assertEqual('COMPOSE_PROJECT_NAME=labmania\nUNCHANGED=fixture\n', backups[0].with_name('environment-sebelumnya.env').read_text())
            backup_index = next(i for i, args in enumerate(calls) if 'backup' in args)
            self.assertEqual('root', calls[backup_index][calls[backup_index].index('--user') + 1])
            stop_index = calls.index(['docker', 'stop', 'labmania_web'])
            self.assertLess(backup_index, stop_index)
            self.assertFalse(any('labmania:uat' in args or 'migrate' in args for args in calls))


if __name__ == '__main__':
    unittest.main()
