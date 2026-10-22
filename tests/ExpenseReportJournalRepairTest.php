<?php
require_once dirname(__DIR__) . '/scripts/repair_expense_report_journals.php';

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function rejects(callable $action, $message)
{
    try {
        $action();
    } catch (RuntimeException $error) {
        check(strpos($error->getMessage(), $message) !== false, $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $message);
}

function fixture($expense = '100.00', $kasbon = '100.00', $difference = '0.00')
{
    return [
        'header' => ['id' => 'TEST/EXP/H/2025', 'id_header' => 'SHARED/KASBON', 'sts' => '1',
            'total_expense_report' => $expense, 'total_kasbon' => $kasbon, 'selisih' => $difference,
            'approved_date' => null, 'created_date' => '2025-06-25 10:00:00'],
        'company' => ['id' => '1', 'nm_company' => 'Test Company'],
        'bank' => ['coa_bank' => '1101-02-01'],
        'details' => [['id' => '1', 'qty_expense' => '1.00', 'nominal_expense' => $expense,
            'kasbon_type' => '2', 'mapped_coa' => null]],
        'accounts' => ['5101-01-04' => 'Akomodasi', '5101-01-05' => 'Biaya Lain',
            '1103-01-04' => 'Piutang Karyawan', '1101-02-01' => 'Bank BCA', '9999-99-99' => 'Hutang Expense'],
        'journals' => [], 'source' => [],
    ];
}

$tests = [];
$tests['equal, refund, reimbursement balance'] = function () {
    foreach ([['100.00', '100.00', '0.00'], ['75.00', '100.00', '25.00'], ['125.00', '100.00', '-25.00']] as $amounts) {
        $plan = ExpenseJournalPlan::build(fixture(...$amounts));
        check($plan['status'] === 'repair', 'Missing journal should need repair');
        $debit = $credit = 0;
        foreach ($plan['expected'] as $row) {
            $debit += ExpenseJournalPlan::cents($row['debit']);
            $credit += ExpenseJournalPlan::cents($row['kredit']);
        }
        check($debit === $credit, 'Journal must balance');
        check(count($plan['expected']) === ($amounts[2] === '0.00' ? 2 : 3), 'Unexpected row count');
    }
};
$tests['fixed decimal arithmetic and legacy quantity rule'] = function () {
    $f = fixture('0.03', '0.03');
    $f['details'][0]['qty_expense'] = '1.50';
    $f['details'][0]['nominal_expense'] = '0.02';
    check(ExpenseJournalPlan::build($f)['expected'][0]['debit'] === '0.03', 'Decimal multiplication');
    $f = fixture();
    $f['details'][0]['qty_expense'] = '0.50';
    check(ExpenseJournalPlan::build($f)['expected'][0]['debit'] === '100.00', 'Legacy quantity below one');
    rejects(function () { ExpenseJournalPlan::cents('999999999999999999.99'); }, 'range');
};
$tests['valid mapped account retained; missing or invalid falls back'] = function () {
    $f = fixture();
    $f['details'][0]['mapped_coa'] = '5101-01-05';
    check(ExpenseJournalPlan::build($f)['expected'][0]['coa'] === '5101-01-05', 'Retain valid mapped COA');
    $f['details'][0]['mapped_coa'] = 'INVALID';
    check(ExpenseJournalPlan::build($f)['expected'][0]['coa'] === '5101-01-04', 'Accommodation fallback');
    $f['details'][0]['kasbon_type'] = '3';
    check(ExpenseJournalPlan::build($f)['expected'][0]['coa'] === '5101-01-05', 'Others fallback');
};
$tests['missing bank and invalid source held'] = function () {
    $f = fixture('75.00', '100.00', '25.00');
    $f['bank'] = null;
    check(ExpenseJournalPlan::build($f)['status'] === 'held', 'Missing refund bank');
    $f = fixture();
    $f['details'][0]['nominal_expense'] = '90.00';
    check(ExpenseJournalPlan::build($f)['status'] === 'held', 'Header mismatch');
    $f = fixture();
    $f['company'] = null;
    check(ExpenseJournalPlan::build($f)['status'] === 'held', 'Missing company');
};
$tests['dates prefer existing, then approval, then creation'] = function () {
    $f = fixture();
    check(ExpenseJournalPlan::build($f)['expected'][0]['tgl_jurnal'] === '2025-06-25', 'Creation fallback');
    $f['header']['approved_date'] = '2025-07-01 10:00:00';
    check(ExpenseJournalPlan::build($f)['expected'][0]['tgl_jurnal'] === '2025-07-01', 'Approval date');
    $f['journals'] = [['tgl_jurnal' => '2025-08-01', 'sts' => '0']];
    check(ExpenseJournalPlan::build($f)['expected'][0]['tgl_jurnal'] === '2025-08-01', 'Existing date');
    $f['journals'][] = ['tgl_jurnal' => '2025-08-02', 'sts' => '0'];
    check(ExpenseJournalPlan::build($f)['status'] === 'held', 'Conflicting dates');
};
$tests['complete journals skipped, duplicates and corruption repaired'] = function () {
    $f = fixture();
    $f['journals'] = ExpenseJournalPlan::build($f)['expected'];
    foreach ($f['journals'] as &$row) {
        $row['sts'] = '0';
    }
    unset($row);
    check(ExpenseJournalPlan::build($f)['status'] === 'ok', 'Idempotence');
    $clean = $f['journals'];
    $f['journals'] = array_merge($clean, $clean);
    check(ExpenseJournalPlan::build($f)['status'] === 'repair', 'Balanced duplicate');
    $f['journals'] = $clean;
    $f['journals'][0]['coa'] = '';
    check(ExpenseJournalPlan::build($f)['status'] === 'repair', 'Blank COA');
    $f['journals'] = $clean;
    $f['journals'][1]['kredit'] = '0.00';
    check(ExpenseJournalPlan::build($f)['status'] === 'repair', 'Zero credit');
    $f['journals'] = [$clean[0]];
    check(ExpenseJournalPlan::build($f)['status'] === 'repair', 'Partial journal');
    $f['journals'][0]['sts'] = '1';
    check(ExpenseJournalPlan::build($f)['status'] === 'held', 'Posted rows protected');
};
$tests['same kasbon remains separate by expense and repeated detail amounts retained'] = function () {
    $f = fixture();
    $f['details'] = array_fill(0, 2, $f['details'][0]);
    $f['details'][0]['nominal_expense'] = '50.00';
    $f['details'][1]['nominal_expense'] = '50.00';
    $f['details'][1]['id'] = '2';
    foreach (['0023/EXP/H/2025', '0024/EXP/H/2025', '0025/EXP/H/2025'] as $id) {
        $f['header']['id'] = $id;
        $p = ExpenseJournalPlan::build($f);
        check(count($p['expected']) === 3, 'Equal amounts are legitimate separate details');
        check($p['expected'][0]['no_transaksi'] === $id, 'Use expense ID');
    }
};

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "PASS $name\n";
    } catch (Throwable $error) {
        $failed++;
        echo "FAIL $name: {$error->getMessage()}\n";
    }
}
if (in_array('--integration', $argv, true)) {
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // This fixture connects only to the local Docker database, never the development config.
        $db = new mysqli('mysql8', 'root', 'root', 'db_consultant_new');
        $db->set_charset('utf8mb4');
        $db->query("CREATE TEMPORARY TABLE er_repair_test_journal (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, no_jurnal VARCHAR(255), tgl_jurnal DATE,
            coa VARCHAR(255), id_company VARCHAR(255), nm_company VARCHAR(255), nm_coa VARCHAR(255),
            debit DECIMAL(20,2) NOT NULL DEFAULT 0, kredit DECIMAL(20,2) NOT NULL,
            keterangan TEXT, sts ENUM('0','1') DEFAULT '0', no_transaksi VARCHAR(255),
            jenis_transaksi VARCHAR(255), created_by VARCHAR(255), created_date DATETIME,
            CHECK (debit < 1000)
        ) ENGINE=InnoDB");
        $store = new class($db) extends ExpenseJournalStore {
            public $fixture;

            protected function journalTable()
            {
                return 'er_repair_test_journal';
            }

            public function snapshot($id, $lock = false)
            {
                $snapshot = $this->fixture;
                $snapshot['journals'] = $this->journalRows($id, $lock);
                return $snapshot;
            }
        };
        $store->fixture = fixture();
        $db->query("INSERT INTO er_repair_test_journal
            (no_jurnal,tgl_jurnal,coa,id_company,nm_company,nm_coa,debit,kredit,keterangan,sts,no_transaksi,jenis_transaksi,created_by,created_date)
            VALUES ('00001-AJV-VI-25','2025-06-25','1103-01-04','1','Test Company','Piutang Karyawan',0,100,
            'old journal',NULL,'TEST/EXP/H/2025','Expense Report Consultant','original','2025-06-25 10:00:00')");
        $directory = sys_get_temp_dir() . '/expense-journal-repair-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $entry = ExpenseJournalPlan::build($store->snapshot('TEST/EXP/H/2025'));
        $result = $store->replace($entry, $directory, 'test-run');
        check($result['removed'] === 1 && $result['inserted'] === 2, 'Atomic replacement counts');
        check(ExpenseJournalPlan::build($store->snapshot($entry['id']))['status'] === 'ok', 'Second run must skip');
        $after = $store->snapshot($entry['id'])['journals'];
        check(count(array_unique(array_column($after, 'no_jurnal'))) === 2, 'Unique journal numbers');
        check($after[0]['no_jurnal'] === '00002-AJV-VI-25', 'Continue highest month sequence');
        $receiptPath = $directory . '/receipt-' . hash('sha256', $entry['id']) . '.json';
        $receipt = json_decode(file_get_contents($receiptPath), true);
        check($store->restore($receipt) === 'restored', 'Restore original journal');
        check($store->snapshot($entry['id'])['journals'] === $entry['snapshot']['journals'], 'Restore exact IDs/null statuses/audit fields');
        check($store->restore($receipt) === 'already_original', 'Restore can be repeated');
        echo "PASS MySQL replacement, numbering, idempotence and exact backup restore\n";

        $entry = ExpenseJournalPlan::build($store->snapshot('TEST/EXP/H/2025'));
        $store->fixture['header']['created_date'] = '2025-06-26 10:00:00';
        rejects(function () use ($store, $entry, $directory) { $store->replace($entry, $directory, 'changed'); }, 'changed after audit');
        $store->fixture = fixture('1100.00', '100.00', '-1000.00');
        $before = $store->snapshot('TEST/EXP/H/2025');
        $entry = ExpenseJournalPlan::build($before);
        $failedInsert = false;
        try {
            $store->replace($entry, $directory, 'insert-failure');
        } catch (mysqli_sql_exception $error) {
            $failedInsert = true;
        }
        check($failedInsert, 'Real CHECK constraint should reject insert');
        check($store->snapshot($entry['id'])['journals'] === $before['journals'], 'Rollback restores deleted originals');
        echo "PASS MySQL changed-source protection and rollback after actual insert failure\n";

        $store->fixture = fixture();
        $entry = ExpenseJournalPlan::build($store->snapshot('TEST/EXP/H/2025'));
        $unwritable = $directory . '/nonexistent';
        rejects(function () use ($store, $entry, $unwritable) { @$store->replace($entry, $unwritable, 'backup-failure'); }, 'Cannot create backup');
        check($store->snapshot($entry['id'])['journals'] === $entry['snapshot']['journals'], 'Rollback on receipt write failure');

        $secondDirectory = $directory . '/second';
        mkdir($secondDirectory, 0700);
        $store->replace($entry, $secondDirectory, 'second');
        $receipt = json_decode(file_get_contents($secondDirectory . '/receipt-' . hash('sha256', $entry['id']) . '.json'), true);
        $store->query("UPDATE er_repair_test_journal SET sts='1' WHERE id=?", [$receipt['after'][0]['id']]);
        rejects(function () use ($store, $receipt) { $store->restore($receipt); }, 'changed since repair');
        check(ExpenseJournalPlan::build($store->snapshot($entry['id']))['status'] === 'held', 'Posted data held');
        echo "PASS MySQL receipt-write rollback and refusal to restore changed/posted data\n";
        $lockTable = 'er_repair_lock_test_' . bin2hex(random_bytes(5));
        $db->query("CREATE TABLE `$lockTable` LIKE er_repair_test_journal");
        $secondConnection = new mysqli('mysql8', 'root', 'root', 'db_consultant_new');
        $lockedStore = new class($db, $lockTable) extends ExpenseJournalStore {
            public $fixture;
            private $table;
            public function __construct($db, $table)
            {
                parent::__construct($db);
                $this->table = $table;
            }
            protected function journalTable()
            {
                return '`' . $this->table . '`';
            }
            public function snapshot($id, $lock = false)
            {
                return array_merge($this->fixture, ['journals' => $this->journalRows($id, $lock)]);
            }
        };
        try {
            $lockedStore->fixture = fixture();
            $entry = ExpenseJournalPlan::build($lockedStore->snapshot('TEST/EXP/H/2025'));
            $lockedStore->startMaintenance([$entry]);
            $maintenanceDirectory = $directory . '/maintenance';
            mkdir($maintenanceDirectory, 0700);
            $lockedStore->replace($entry, $maintenanceDirectory, 'maintenance');
            $committed = $lockedStore->snapshot($entry['id'])['journals'];
            $lockedStore->fixture = fixture('1100.00', '100.00', '-1000.00');
            $entry = ExpenseJournalPlan::build($lockedStore->snapshot('TEST/EXP/H/2025'));
            $caught = false;
            try {
                $lockedStore->replace($entry, $maintenanceDirectory, 'maintenance-fail');
            } catch (mysqli_sql_exception $error) {
                $caught = true;
            }
            check($caught && $lockedStore->snapshot($entry['id'])['journals'] === $committed, 'Rollback with maintenance locks');
            $secondConnection->query('SET SESSION lock_wait_timeout=1,innodb_lock_wait_timeout=1');
            $blocked = false;
            try {
                $secondConnection->query("INSERT INTO `$lockTable` (debit,kredit) VALUES (0,0)");
            } catch (mysqli_sql_exception $error) {
                check($error->getCode() === 1205, 'Expected lock timeout');
                $blocked = true;
            }
            check($blocked, 'Maintenance lock must survive per-expense commit and rollback');
            $lockedStore->endMaintenance();
            $secondConnection->query("INSERT INTO `$lockTable` (debit,kredit) VALUES (0,0)");
            echo "PASS maintenance blocks concurrent writers across commit/rollback and releases cleanly\n";
        } finally {
            $lockedStore->endMaintenance();
            $secondConnection->close();
            $db->query("DROP TABLE `$lockTable`");
        }
        $db->close();
    } catch (Throwable $error) {
        $failed++;
        echo 'FAIL integration: ' . $error->getMessage() . "\n";
    }
}
exit($failed ? 1 : 0);
