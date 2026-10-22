<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

final class ExpenseJournalPlan
{
    const TYPE = 'Expense Report Consultant';

    public static function cents($value)
    {
        $value = (string) $value;
        if (!preg_match('/^(-?)(\d{1,14})(?:\.(\d{1,2}))?$/D', $value, $m)) {
            throw new RuntimeException('Invalid decimal or amount outside supported range');
        }
        return ($m[1] === '-' ? -1 : 1) * ((int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0'));
    }

    public static function money($cents)
    {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad(abs($cents) % 100, 2, '0', STR_PAD_LEFT);
    }

    public static function fingerprint(array $data)
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public static function canonical(array $rows)
    {
        $result = [];
        foreach ($rows as $row) {
            $item = [];
            foreach (['tgl_jurnal', 'coa', 'nm_coa', 'id_company', 'nm_company', 'keterangan', 'no_transaksi', 'jenis_transaksi'] as $key) {
                $item[$key] = (string) ($row[$key] ?? '');
            }
            $item['debit'] = self::cents($row['debit'] ?? '0');
            $item['kredit'] = self::cents($row['kredit'] ?? '0');
            $result[] = json_encode($item, JSON_THROW_ON_ERROR);
        }
        sort($result, SORT_STRING);
        return $result;
    }

    public static function build(array $snapshot)
    {
        $plan = ['id' => $snapshot['header']['id'], 'fingerprint' => self::fingerprint($snapshot),
            'status' => 'held', 'reasons' => [], 'expected' => [], 'snapshot' => $snapshot];
        try {
            if (!empty($snapshot['issues'])) {
                throw new RuntimeException(implode('; ', $snapshot['issues']));
            }
            $h = $snapshot['header'];
            if ((string) $h['sts'] !== '1' || empty($snapshot['company']) || empty($snapshot['details'])) {
                throw new RuntimeException('Not approved, missing company, or missing expense details');
            }
            $dates = [];
            foreach ($snapshot['journals'] as $row) {
                if (isset($row['sts']) && (string) $row['sts'] !== '0') {
                    throw new RuntimeException('Posted or unknown journal status');
                }
                $dates[] = $row['tgl_jurnal'] ?? '';
            }
            $dates = array_values(array_unique($dates));
            if (count($dates) > 1) {
                throw new RuntimeException('Conflicting existing journal dates');
            }
            $date = $dates[0] ?? substr($h['approved_date'] ?: $h['created_date'], 0, 10);
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                throw new RuntimeException('Invalid journal date');
            }
            $expense = self::cents($h['total_expense_report']);
            $kasbon = self::cents($h['total_kasbon']);
            $difference = self::cents($h['selisih']);
            if ($expense < 0 || $kasbon < 0 || $difference !== $kasbon - $expense) {
                throw new RuntimeException('Inconsistent header totals');
            }
            $expected = [];
            $add = function ($coa, $debit, $credit) use (&$expected, $snapshot, $h, $date) {
                if (!isset($snapshot['accounts'][$coa])) {
                    throw new RuntimeException('COA missing or ambiguous in GL: ' . $coa);
                }
                $name = $snapshot['accounts'][$coa];
                $expected[] = ['tgl_jurnal' => $date, 'coa' => (string) $coa, 'nm_coa' => $name,
                    'id_company' => (string) $snapshot['company']['id'], 'nm_company' => $snapshot['company']['nm_company'],
                    'debit' => self::money($debit), 'kredit' => self::money($credit),
                    'keterangan' => $name . ' - ' . $h['id'], 'no_transaksi' => $h['id'], 'jenis_transaksi' => self::TYPE];
            };
            $total = 0;
            foreach ($snapshot['details'] as $detail) {
                $qty = self::cents($detail['qty_expense']);
                $price = self::cents($detail['nominal_expense']);
                if ($qty < 0 || $price < 0 || ($qty >= 100 && $price > intdiv(PHP_INT_MAX - 50, $qty))) {
                    throw new RuntimeException('Negative expense or multiplication outside supported range');
                }
                $amount = $qty < 100 ? $price : intdiv($qty * $price + 50, 100);
                if ($amount > PHP_INT_MAX - $total) {
                    throw new RuntimeException('Expense sum outside supported range');
                }
                $total += $amount;
                $coa = $detail['mapped_coa'] ?? '';
                if (!isset($snapshot['accounts'][$coa])) {
                    $fallback = ['2' => '5101-01-04', '3' => '5101-01-05'];
                    $coa = $fallback[$detail['kasbon_type']] ?? '';
                }
                $add($coa, $amount, 0);
            }
            if ($total !== $expense) {
                throw new RuntimeException('Detail total differs from expense header');
            }
            $add('1103-01-04', 0, $kasbon);
            if ($difference > 0) {
                $bankCoa = $snapshot['bank']['coa_bank'] ?? '';
                if ($bankCoa === '' || !isset($snapshot['accounts'][$bankCoa])) {
                    throw new RuntimeException('Refund bank missing or has invalid COA');
                }
                $add($bankCoa, $difference, 0);
            } elseif ($difference < 0) {
                $add('9999-99-99', 0, -$difference);
            }
            $plan['expected'] = $expected;
            $same = self::canonical($expected) === self::canonical($snapshot['journals']);
            $plan['status'] = $same ? 'ok' : 'repair';
            if (!$same) {
                $plan['reasons'][] = !$snapshot['journals'] ? 'missing_journal' : 'journal_content_mismatch';
                if (count($snapshot['journals']) !== count($expected)) {
                    $plan['reasons'][] = 'row_count_mismatch';
                }
                $debit = $credit = 0;
                foreach ($snapshot['journals'] as $row) {
                    $debit += self::cents($row['debit'] ?? '0');
                    $credit += self::cents($row['kredit'] ?? '0');
                    if (empty($row['coa'])) {
                        $plan['reasons'][] = 'blank_coa';
                    }
                }
                if ($debit !== $credit) {
                    $plan['reasons'][] = 'unbalanced';
                }
                $plan['reasons'] = array_values(array_unique($plan['reasons']));
            }
        } catch (RuntimeException $error) {
            $plan['status'] = 'held';
            $plan['reasons'] = [$error->getMessage()];
        }
        return $plan;
    }
}

class ExpenseJournalStore
{
    protected $db;
    private $source;
    private $finance;
    private $gl;
    private $sources = [];
    private $locking = false;
    private $maintenance = false;

    public function __construct(mysqli $db, $source = 'db_consultant_new', $finance = 'db_sendigs_ss', $gl = 'db_sendigs_gl_ss')
    {
        foreach ([$source, $finance, $gl] as $name) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) {
                throw new RuntimeException('Invalid schema name');
            }
        }
        $this->db = $db;
        $this->source = $source;
        $this->finance = $finance;
        $this->gl = $gl;
    }

    protected function journalTable()
    {
        return "`{$this->finance}`.`tr_jurnal`";
    }

    public function query($sql, array $values = [])
    {
        $statement = $this->db->prepare($sql);
        if ($values) {
            $statement->bind_param(str_repeat('s', count($values)), ...$values);
        }
        $statement->execute();
        $result = $statement->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        return $rows;
    }

    public function approvedIds()
    {
        return array_column($this->query("SELECT id FROM `{$this->source}`.kons_tr_expense_report_project_header WHERE sts='1' ORDER BY id"), 'id');
    }

    protected function journalRows($id, $lock = false)
    {
        return $this->query('SELECT * FROM ' . $this->journalTable()
            . ' WHERE no_transaksi=? AND jenis_transaksi=? ORDER BY id' . ($lock ? ' FOR UPDATE' : ''), [$id, ExpenseJournalPlan::TYPE]);
    }

    private function records($schema, $table, array $filters)
    {
        $clauses = [];
        foreach ($filters as $column => $value) {
            $clauses[] = "`$column`=?";
        }
        $order = $table === 'kons_tr_penawaran' ? 'id_quotation' : 'id';
        $rows = $this->query("SELECT * FROM `$schema`.`$table` WHERE " . implode(' AND ', $clauses)
            . " ORDER BY `$order`" . ($this->locking ? ' FOR UPDATE' : ''), array_values($filters));
        $this->sources[] = ['table' => "$schema.$table", 'filters' => $filters, 'rows' => $rows];
        return $rows;
    }

    private function one($schema, $table, array $filters)
    {
        $rows = $this->records($schema, $table, $filters);
        if (count($rows) !== 1) {
            throw new RuntimeException('Missing or ambiguous relation: ' . $table);
        }
        return $rows[0];
    }

    public function snapshot($id, $lock = false)
    {
        $this->sources = [];
        $this->locking = $lock && !$this->maintenance;
        $h = $this->one($this->source, 'kons_tr_expense_report_project_header', ['id' => $id]);
        $snapshot = ['header' => $h, 'company' => null, 'bank' => null, 'details' => [], 'accounts' => [],
            'journals' => $this->journalRows($id, $lock), 'issues' => []];
        try {
            $kasbon = $this->one($this->source, 'kons_tr_kasbon_project_header', ['id' => $h['id_header']]);
            $quotation = $this->one($this->source, 'kons_tr_penawaran', ['id_quotation' => $kasbon['id_penawaran']]);
            $snapshot['company'] = $this->one($this->source, 'kons_tr_company', ['id' => $quotation['company']]);
            $accounts = $this->query("SELECT no_perkiraan,nama FROM `{$this->gl}`.coa_master ORDER BY no_perkiraan,nama" . ($this->locking ? ' FOR UPDATE' : ''));
            $this->sources[] = ['table' => $this->gl . '.coa_master', 'rows' => $accounts];
            $counts = array_count_values(array_column($accounts, 'no_perkiraan'));
            foreach ($accounts as $account) {
                if ($counts[$account['no_perkiraan']] === 1 && trim($account['nama']) !== '') {
                    $snapshot['accounts'][$account['no_perkiraan']] = $account['nama'];
                }
            }
            if (ExpenseJournalPlan::cents($h['selisih']) > 0 && !empty($h['id_bank'])) {
                $snapshot['bank'] = $this->one($this->finance, 'ms_bank', ['id' => $h['id_bank']]);
            }
            foreach ($this->records($this->source, 'kons_tr_expense_report_project_detail', ['id_header_expense' => $id]) as $detail) {
                $k = $this->one($this->source, 'kons_tr_kasbon_project_header', ['id' => $detail['id_header_kasbon']]);
                if ($k['id'] !== $h['id_header']) {
                    throw new RuntimeException('Expense detail belongs to another kasbon');
                }
                $type = (string) $k['tipe'];
                $tables = ['2' => ['akomodasi', 'id_akomodasi'], '3' => ['others', 'id_others'], '4' => ['lab', 'id_lab']];
                $master = 'kons_master_biaya';
                if (isset($tables[$type])) {
                    list($suffix, $foreignKey) = $tables[$type];
                    $budget = $this->one($this->source, 'kons_tr_spk_budgeting_' . $suffix,
                        ['id_spk_budgeting' => $k['id_spk_budgeting'], 'id' => $detail['id_detail_kasbon']]);
                    $item = $this->one($this->source, 'kons_tr_kasbon_project_' . $suffix,
                        ['id_header' => $k['id'], $foreignKey => $budget[$foreignKey]]);
                    if ($type === '4') {
                        $master = 'kons_master_lab';
                    }
                } elseif ($type === '5' || $type === '6') {
                    $suffix = $type === '5' ? 'tenaga_ahli' : 'perusahaan';
                    $item = $this->one($this->source, 'kons_tr_kasbon_project_subcont_' . $suffix,
                        ['id_header' => $k['id'], 'id_subcont' => $detail['id_detail_kasbon']]);
                    $master = $type === '5' ? 'kons_master_tenaga_ahli' : 'kons_master_subcont_perusahaan';
                } else {
                    throw new RuntimeException('Unsupported kasbon type: ' . $type);
                }
                $mapping = $this->one($this->source, $master, ['id' => $item['id_item']]);
                $detail['kasbon_type'] = $type;
                $detail['mapped_coa'] = $mapping['no_coa'];
                $snapshot['details'][] = $detail;
            }
        } catch (mysqli_sql_exception $error) {
            throw $error;
        } catch (RuntimeException $error) {
            $snapshot['issues'][] = $error->getMessage();
        }
        $snapshot['source'] = $this->sources;
        return $snapshot;
    }

    public function assertSafeTables()
    {
        $tables = [[$this->source, 'kons_tr_expense_report_project_header'], [$this->source, 'kons_tr_expense_report_project_detail'], [$this->finance, 'tr_jurnal']];
        foreach ($tables as $table) {
            $rows = $this->query('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?', $table);
            if (count($rows) !== 1 || $rows[0]['ENGINE'] !== 'InnoDB') {
                throw new RuntimeException('Transactional InnoDB tables required');
            }
        }
        $dependencies = $this->query('SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME=?', [$this->finance, 'tr_jurnal']);
        $triggers = $this->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA=? AND EVENT_OBJECT_TABLE=?', [$this->finance, 'tr_jurnal']);
        if ($dependencies || $triggers) {
            throw new RuntimeException('Journal dependencies/triggers require manual review');
        }
    }

    public function acquireLock()
    {
        $rows = $this->query('SELECT GET_LOCK(?,0) acquired', ['expense_journal_repair_' . $this->finance]);
        if ((string) $rows[0]['acquired'] !== '1') {
            throw new RuntimeException('Another journal repair is running');
        }
    }

    public function startMaintenance(array $entries)
    {
        $tables = [];
        foreach ($entries as $entry) {
            if ($entry['status'] !== 'repair') {
                continue;
            }
            foreach ($entry['snapshot']['source'] as $source) {
                if (!preg_match('/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)$/D', $source['table'], $m)
                    || !in_array($m[1], [$this->source, $this->finance, $this->gl], true)) {
                    throw new RuntimeException('Invalid source table in manifest');
                }
                $tables["`{$m[1]}`.`{$m[2]}`"] = 'READ';
            }
        }
        $tables[$this->journalTable()] = 'WRITE';
        $clauses = [];
        foreach ($tables as $table => $mode) {
            $clauses[] = $table . ' ' . $mode;
        }
        $this->db->query('SET SESSION lock_wait_timeout=10, innodb_lock_wait_timeout=10');
        $this->db->autocommit(false);
        // START TRANSACTION releases explicit table locks; each COMMIT below keeps them.
        $this->db->query('LOCK TABLES ' . implode(',', $clauses));
        $this->maintenance = true;
    }

    public function endMaintenance()
    {
        if ($this->maintenance) {
            $this->db->rollback();
            $this->db->query('UNLOCK TABLES');
            $this->db->autocommit(true);
            $this->maintenance = false;
        }
    }

    private function insertRow(array $row)
    {
        $columns = array_keys($row);
        foreach ($columns as $column) {
            if (!preg_match('/^[a-z_]+$/D', $column)) {
                throw new RuntimeException('Invalid journal column');
            }
        }
        $this->query('INSERT INTO ' . $this->journalTable() . ' (`' . implode('`,`', $columns) . '`) VALUES ('
            . implode(',', array_fill(0, count($columns), '?')) . ')', array_values($row));
    }

    private function numberedRows(array $expected, $run)
    {
        $romans = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $date = new DateTimeImmutable($expected[0]['tgl_jurnal']);
        $suffix = '-AJV-' . $romans[(int) $date->format('n')] . '-' . $date->format('y');
        $existing = $this->query('SELECT no_jurnal FROM ' . $this->journalTable() . ' WHERE no_jurnal LIKE ? FOR UPDATE', ['%' . $suffix]);
        $maximum = 0;
        foreach ($existing as $row) {
            if (preg_match('/^(\d+)' . preg_quote($suffix, '/') . '$/D', $row['no_jurnal'], $m)) {
                $maximum = max($maximum, (int) $m[1]);
            }
        }
        foreach ($expected as &$row) {
            $number = str_pad(++$maximum, 5, '0', STR_PAD_LEFT) . $suffix;
            if ($this->query('SELECT id FROM ' . $this->journalTable() . ' WHERE no_jurnal=?', [$number])) {
                throw new RuntimeException('Journal number collision: ' . $number);
            }
            $row['no_jurnal'] = $number;
            $row['sts'] = '0';
            $row['created_by'] = 'repair:' . $run;
            $row['created_date'] = date('Y-m-d H:i:s');
        }
        unset($row);
        return $expected;
    }

    public function replace(array $entry, $directory, $run)
    {
        if (!$this->maintenance) {
            $this->db->begin_transaction();
        }
        try {
            $snapshot = $this->snapshot($entry['id'], true);
            if (ExpenseJournalPlan::fingerprint($snapshot) !== $entry['fingerprint']) {
                throw new RuntimeException('Source/journals changed after audit: ' . $entry['id']);
            }
            $fresh = ExpenseJournalPlan::build($snapshot);
            if ($fresh['status'] !== 'repair' || $fresh['expected'] !== $entry['expected']) {
                throw new RuntimeException('Audited plan is no longer applicable');
            }
            $rows = $this->numberedRows($fresh['expected'], $run);
            $this->query('DELETE FROM ' . $this->journalTable() . ' WHERE no_transaksi=? AND jenis_transaksi=?', [$entry['id'], ExpenseJournalPlan::TYPE]);
            foreach ($rows as $row) {
                $this->insertRow($row);
            }
            $after = $this->journalRows($entry['id'], true);
            if (ExpenseJournalPlan::canonical($after) !== ExpenseJournalPlan::canonical($fresh['expected'])) {
                throw new RuntimeException('Post-insert verification failed');
            }
            $receipt = ['id' => $entry['id'], 'before' => $snapshot['journals'], 'after' => $after, 'run' => $run];
            // Save insertion IDs before commit so an interrupted run can still be restored.
            expenseRepairWrite($directory . '/receipt-' . hash('sha256', $entry['id']) . '.json', $receipt);
            $this->db->commit();
            return ['id' => $entry['id'], 'removed' => count($snapshot['journals']), 'inserted' => count($after)];
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    public function restore(array $receipt)
    {
        if (!$this->maintenance) {
            $this->db->begin_transaction();
        }
        try {
            $current = $this->journalRows($receipt['id'], true);
            if (ExpenseJournalPlan::fingerprint($current) === ExpenseJournalPlan::fingerprint($receipt['before'])) {
                $this->db->rollback();
                return 'already_original';
            }
            if (ExpenseJournalPlan::fingerprint($current) !== ExpenseJournalPlan::fingerprint($receipt['after'])) {
                throw new RuntimeException('Journal changed since repair; restore refused: ' . $receipt['id']);
            }
            foreach ($current as $row) {
                if (isset($row['sts']) && (string) $row['sts'] !== '0') {
                    throw new RuntimeException('Cannot restore a posted journal');
                }
            }
            $this->query('DELETE FROM ' . $this->journalTable() . ' WHERE no_transaksi=? AND jenis_transaksi=?', [$receipt['id'], ExpenseJournalPlan::TYPE]);
            foreach ($receipt['before'] as $row) {
                if ($row['no_transaksi'] !== $receipt['id'] || $row['jenis_transaksi'] !== ExpenseJournalPlan::TYPE) {
                    throw new RuntimeException('Invalid restore journal reference');
                }
                $this->insertRow($row);
            }
            if (ExpenseJournalPlan::fingerprint($this->journalRows($receipt['id'])) !== ExpenseJournalPlan::fingerprint($receipt['before'])) {
                throw new RuntimeException('Restore verification failed');
            }
            $this->db->commit();
            return 'restored';
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }
}

function expenseRepairWrite($path, array $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $handle = fopen($path, 'x');
    if (!$handle) {
        throw new RuntimeException('Cannot create backup/report: ' . $path);
    }
    try {
        if (!chmod($path, 0600)) {
            throw new RuntimeException('Cannot restrict backup permissions');
        }
        $written = 0;
        while ($written < strlen($json)) {
            $count = fwrite($handle, substr($json, $written));
            if ($count === false || $count === 0) {
                throw new RuntimeException('Backup write failed: ' . $path);
            }
            $written += $count;
        }
        if (!fflush($handle)) {
            throw new RuntimeException('Backup flush failed');
        }
    } finally {
        fclose($handle);
    }
}

function expenseRepairDirectory($directory)
{
    if (!$directory || $directory[0] !== '/') {
        throw new RuntimeException('Use an absolute private output directory outside the web root');
    }
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Cannot create private output directory');
    }
    $directory = realpath($directory);
    $root = realpath(dirname(__DIR__));
    if ($directory === $root || strpos($directory . '/', $root . '/') === 0) {
        throw new RuntimeException('Backups must be outside the web root');
    }
    if ((fileperms($directory) & 0077) !== 0) {
        throw new RuntimeException('Output directory must be private (permissions 0700)');
    }
    return $directory;
}

function expenseRepairConnect()
{
    define('BASEPATH', dirname(__DIR__) . '/system/');
    define('ENVIRONMENT', 'development');
    require dirname(__DIR__) . '/application/config/development/database.php';
    $main = $db['default'];
    $finance = $db['sendigs_finance'];
    if ($main['database'] !== 'db_consultant_new' || $finance['database'] !== 'db_sendigs_ss'
        || $db['gl_sendigs']['database'] !== 'db_sendigs_gl_ss' || $main['hostname'] !== $finance['hostname']
        || ($main['port'] ?? 3306) != ($finance['port'] ?? 3306)) {
        throw new RuntimeException('Expected consultant/finance/GL schemas on one server');
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = mysqli_init();
    $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $connection->real_connect($main['hostname'], $main['username'], $main['password'], $main['database'], (int) ($main['port'] ?? 3306));
    $connection->set_charset('utf8mb4');
    return [$connection, new ExpenseJournalStore($connection),
        ['host' => $main['hostname'], 'source' => $main['database'], 'finance' => $finance['database'], 'gl' => $db['gl_sendigs']['database']]];
}

function expenseRepairMain()
{
    $options = getopt('', ['audit', 'apply:', 'restore:', 'output-dir:', 'maintenance-window', 'help']);
    if (isset($options['help'])) {
        echo "Audit: php scripts/repair_expense_report_journals.php --output-dir=/private/new-run\n"
            . "Apply: php scripts/repair_expense_report_journals.php --apply=/private/new-run/manifest.json --maintenance-window\n"
            . "Restore: php scripts/repair_expense_report_journals.php --restore=/private/new-run/manifest.json --maintenance-window\n";
        return;
    }
    if (isset($options['apply']) && isset($options['restore'])) {
        throw new RuntimeException('Choose apply or restore');
    }
    date_default_timezone_set('Asia/Jakarta');
    umask(0077);
    list($db, $store, $identity) = expenseRepairConnect();
    $store->assertSafeTables();
    if (!isset($options['apply']) && !isset($options['restore'])) {
        $run = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $directory = expenseRepairDirectory($options['output-dir'] ?? sys_get_temp_dir() . '/expense-journal-repair/' . $run);
        $db->query('SET TRANSACTION READ ONLY');
        $db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        $entries = [];
        foreach ($store->approvedIds() as $id) {
            $entries[] = ExpenseJournalPlan::build($store->snapshot($id));
        }
        $db->rollback();
        $counts = array_count_values(array_column($entries, 'status'));
        $manifest = ['version' => 1, 'run' => $run, 'created_at' => date(DATE_ATOM), 'identity' => $identity,
            'script_sha256' => hash_file('sha256', __FILE__), 'counts' => $counts, 'entries' => $entries];
        expenseRepairWrite($directory . '/manifest.json', $manifest);
        echo json_encode(['manifest' => $directory . '/manifest.json', 'counts' => $counts,
            'held' => array_map(function ($e) { return ['id' => $e['id'], 'reasons' => $e['reasons']]; },
                array_values(array_filter($entries, function ($e) { return $e['status'] === 'held'; })))], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
        return;
    }
    if (!isset($options['maintenance-window'])) {
        throw new RuntimeException('Pause approvals/journal writers, then pass --maintenance-window');
    }
    $file = $options['apply'] ?? $options['restore'];
    $directory = expenseRepairDirectory(dirname($file));
    $manifest = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest['version'] ?? null) !== 1 || $manifest['identity'] !== $identity) {
        throw new RuntimeException('Manifest version or database identity mismatch');
    }
    if (isset($options['apply']) && ($manifest['script_sha256'] ?? '') !== hash_file('sha256', __FILE__)) {
        throw new RuntimeException('Script changed since audit; generate a fresh audit');
    }
    $store->acquireLock();
    $results = [];
    $store->startMaintenance($manifest['entries']);
    try {
      foreach ($manifest['entries'] as $entry) {
        if ($entry['status'] !== 'repair') {
            continue;
        }
        if (isset($options['restore'])) {
            $receiptPath = $directory . '/receipt-' . hash('sha256', $entry['id']) . '.json';
            if (is_file($receiptPath)) {
                $receipt = json_decode(file_get_contents($receiptPath), true, 512, JSON_THROW_ON_ERROR);
                if ($receipt['id'] !== $entry['id'] || $receipt['run'] !== $manifest['run'] || $receipt['before'] !== $entry['snapshot']['journals']) {
                    throw new RuntimeException('Receipt does not match backup');
                }
                $results[] = ['id' => $entry['id'], 'status' => $store->restore($receipt)];
            }
        } else {
            $current = ExpenseJournalPlan::build($store->snapshot($entry['id']));
            if ($current['status'] === 'ok') {
                $results[] = ['id' => $entry['id'], 'status' => 'already_correct'];
                continue;
            }
            $results[] = $store->replace($entry, $directory, $manifest['run']);
        }
      }
    } finally {
        $store->endMaintenance();
    }
    expenseRepairWrite($directory . '/' . (isset($options['restore']) ? 'restore' : 'apply') . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json', ['results' => $results]);
    echo json_encode(['results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        expenseRepairMain();
    } catch (Throwable $error) {
        fwrite(STDERR, 'Repair stopped: ' . $error->getMessage() . "\n");
        exit(1);
    }
}
