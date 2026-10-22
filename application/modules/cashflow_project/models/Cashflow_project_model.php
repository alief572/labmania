<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cashflow_project_model extends BF_Model
{
    protected $sendigs;
    protected $sendigs_db_name;

    public function __construct()
    {
        parent::__construct();

        // Load sendigs_finance connection (payment_approve, ms_generate tables)
        try {
            $this->sendigs = $this->load->database('sendigs_finance', true);
            if (!$this->sendigs) {
                log_message('error', 'Cashflow_project_model: Failed to load sendigs_finance database connection.');
                $this->sendigs = null;
                $this->sendigs_db_name = 'db_sendigs_ss'; // fallback
            } else {
                // Resolve actual DB name from config — avoids hardcoding across environments
                $this->sendigs_db_name = $this->sendigs->database;
            }
        } catch (Exception $e) {
            log_message('error', 'Cashflow_project_model: Exception loading sendigs_finance DB - ' . $e->getMessage());
            $this->sendigs = null;
            $this->sendigs_db_name = 'db_sendigs_ss'; // fallback
        }
    }

    /**
     * Get distinct years from kasbon project header transactions.
     * Used to populate the year filter dropdown on the Index page.
     *
     * @return array Array of year values sorted descending (e.g., [2026, 2025, 2024])
     */
    public function get_available_years()
    {
        $this->db->select('DISTINCT YEAR(created_date) as year');
        $this->db->from('kons_tr_kasbon_project_header');
        $this->db->where('created_date IS NOT NULL');
        $this->db->order_by('year', 'DESC');

        $query = $this->db->get();

        if (!$query || $query->num_rows() === 0) {
            return [];
        }

        $years = [];
        foreach ($query->result() as $row) {
            $years[] = (int) $row->year;
        }

        return $years;
    }

    public function get_data_spk()
    {
        $draw   = $this->input->post('draw');
        $start  = (int) $this->input->post('start');
        $length = (int) $this->input->post('length');
        $search = $this->input->post('search');
        $year   = (int) $this->input->post('year');

        // Count total (without search) — use query binding to prevent injection
        $count_sql = "SELECT COUNT(DISTINCT a.id_spk_budgeting) as total
            FROM kons_tr_spk_budgeting a
            INNER JOIN kons_tr_kasbon_project_header b ON b.id_spk_budgeting = a.id_spk_budgeting
            WHERE b.tipe != 1";

        $bindings = [];
        $where_year = '';
        if (!empty($year)) {
            $where_year   = " AND YEAR(b.created_date) = ?";
            $bindings[]   = $year;
        }

        $count_query  = $this->db->query($count_sql . $where_year, $bindings);
        $records_total = ($count_query) ? (int) $count_query->row()->total : 0;

        // Build search condition
        $search_condition = '';
        $search_bindings  = $bindings; // start with year binding if any
        if (!empty($search) && !empty($search['value'])) {
            $search_val       = $this->db->escape_like_str($search['value']);
            $search_condition = " AND (a.id_spk_budgeting LIKE '%{$search_val}%' OR a.id_spk_penawaran LIKE '%{$search_val}%' OR a.nm_customer LIKE '%{$search_val}%')";
        }

        // Count filtered
        $count_filtered_sql = "SELECT COUNT(DISTINCT a.id_spk_budgeting) as total
            FROM kons_tr_spk_budgeting a
            INNER JOIN kons_tr_kasbon_project_header b ON b.id_spk_budgeting = a.id_spk_budgeting
            LEFT JOIN kons_tr_spk_penawaran c ON c.id_spk_penawaran = a.id_spk_penawaran
            LEFT JOIN kons_master_konsultasi_header d ON d.id_konsultasi_h = a.id_project
            WHERE b.tipe != 1" . $where_year . $search_condition;
        $count_filtered_query = $this->db->query($count_filtered_sql, $search_bindings);
        $records_filtered     = ($count_filtered_query) ? (int) $count_filtered_query->row()->total : 0;

        // Get paginated SPK data
        $data_sql = "SELECT a.id_spk_budgeting, a.nm_customer, a.nm_project_leader, a.id_spk_penawaran,
                c.nm_sales, d.nm_paket as nama_project
            FROM kons_tr_spk_budgeting a
            INNER JOIN kons_tr_kasbon_project_header b ON b.id_spk_budgeting = a.id_spk_budgeting
            LEFT JOIN kons_tr_spk_penawaran c ON c.id_spk_penawaran = a.id_spk_penawaran
            LEFT JOIN kons_master_konsultasi_header d ON d.id_konsultasi_h = a.id_project
            WHERE b.tipe != 1" . $where_year . $search_condition . "
            GROUP BY a.id_spk_budgeting
            ORDER BY a.id_spk_budgeting DESC
            LIMIT ?, ?";

        $data_bindings   = $search_bindings;
        $data_bindings[] = $start;
        $data_bindings[] = $length;

        $get_data = $this->db->query($data_sql, $data_bindings);

        $hasil = [];
        $no    = $start;

        if ($get_data && $get_data->num_rows() > 0) {
            foreach ($get_data->result() as $item) {
                $no++;

                // Get total budget for this SPK
                $total_budget = $this->get_spk_budget($item->id_spk_budgeting);

                // Get total actual for this SPK (all tipes, approved ER + approved DP)
                $total_actual = $this->get_total_actual_spk($item->id_spk_budgeting);

                $option = '<a href="' . base_url('cashflow_project/view_cashflow/' . urlencode(str_replace('/', '|', $item->id_spk_budgeting))) . '" class="btn btn-view" title="View Cashflow"><i class="fa fa-eye"></i></a>';

                $hasil[] = [
                    'no'                => $no,
                    'id_spk_budgeting'  => $item->id_spk_budgeting,
                    'id_spk_penawaran'  => $item->id_spk_penawaran,
                    'nm_customer'       => $item->nm_customer,
                    'nm_sales'          => $item->nm_sales,
                    'nm_project_leader' => $item->nm_project_leader,
                    'nm_project'        => $item->nama_project,
                    'total_budget'      => $total_budget,
                    'total_actual'      => $total_actual,
                    'option'            => $option
                ];
            }
        }

        return [
            'draw'            => intval($draw),
            'recordsTotal'    => $records_total,
            'recordsFiltered' => $records_filtered,
            'data'            => $hasil
        ];
    }

    /**
     * Get total actual for a given SPK across all tipes.
     * - Kasbon (metode_pembayaran=1): counts approved Expense Report (sts=1) total_expense_report
     * - Direct Payment (metode_pembayaran=2): counts grand_total where payment_approve.status=2 in db_sendigs_ss
     *
     * @param string $id_spk The id_spk_budgeting value
     * @return float Total actual amount
     */
    public function get_total_actual_spk($id_spk)
    {
        // Approved ER amounts (Kasbon with approved Expense Report, sts=1)
        $this->db->select('IFNULL(SUM(er.total_expense_report), 0) as total', false);
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header er', 'er.id_header = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe !=', 1);
        $this->db->where('er.sts', 1);
        $query_er  = $this->db->get();
        $actual_er = ($query_er && $query_er->num_rows() > 0) ? (float) $query_er->row()->total : 0;

        // Approved DP amounts (Direct Payment with payment_approve.status=2)
        $this->db->select('IFNULL(SUM(a.grand_total), 0) as total', false);
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join($this->sendigs_db_name . '.payment_approve pa', 'pa.no_doc = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 2);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe !=', 1);
        $this->db->where('pa.status', 2);
        $query_dp  = $this->db->get();
        $actual_dp = ($query_dp && $query_dp->num_rows() > 0) ? (float) $query_dp->row()->total : 0;

        return $actual_er + $actual_dp;
    }

    /**
     * Get total SPK budget by summing total_final from 5 budget tables.
     * Explicitly excludes kons_tr_spk_budgeting_aktifitas (Tipe 1).
     *
     * @param string $id_spk The id_spk_budgeting value
     * @return float Total budget amount
     */
    public function get_spk_budget($id_spk)
    {
        $tables = [
            'kons_tr_spk_budgeting_akomodasi',
            'kons_tr_spk_budgeting_others',
            'kons_tr_spk_budgeting_lab',
            'kons_tr_spk_budgeting_subcont_tenaga_ahli',
            'kons_tr_spk_budgeting_subcont_perusahaan'
        ];

        $total = 0;
        foreach ($tables as $table) {
            $q = $this->db->select('IFNULL(SUM(total_final), 0) as budget', false)
                ->where('id_spk_budgeting', $id_spk)
                ->get($table);
            $total += ($q && $q->num_rows() > 0) ? (float) $q->row()->budget : 0;
        }

        return $total;
    }

    /**
     * Get approved Direct Payment transactions for a specific SPK, tipe, and year.
     *
     * Direct Payment = metode_pembayaran = 2 in kons_tr_kasbon_project_header.
     * Approved = payment_approve.status = 2 (cross-database join to db_sendigs_ss).
     *
     * @param string $id_spk The id_spk_budgeting value
     * @param int $tipe The transaction tipe (2=Akomodasi, 3=Others, 4=Lab, 5=Subcont TA, 6=Subcont Perusahaan)
     * @param int $year The calendar year to filter by
     * @return array Array of transactions with keys: nominal, tanggal_transaksi, no_transaksi
     */
    public function get_approved_dp_transactions($id_spk, $tipe, $year)
    {
        $this->db->select('a.grand_total as nominal, DATE(a.created_date) as tanggal_transaksi, a.id as no_transaksi');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join($this->sendigs_db_name . '.payment_approve b', 'b.no_doc = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 2);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe', $tipe);
        $this->db->where('b.status', 2);
        $this->db->where('YEAR(a.created_date)', (int) $year, false);

        $query = $this->db->get();

        if (!$query || $query->num_rows() === 0) {
            return [];
        }

        return $query->result_array();
    }

    /**
     * Get approved Expense Report (ER) transactions for a given SPK, tipe, and year.
     * Queries Kasbon transactions (metode_pembayaran = 1) that have an approved
     * Expense Report (sts = 1) linked via id_header.
     *
     * @param string $id_spk The id_spk_budgeting value
     * @param int $tipe The transaction tipe (2=Akomodasi, 3=Others, 4=Lab, 5=Subcont TA, 6=Subcont Perusahaan)
     * @param int $year The calendar year to filter by (based on ER created_date)
     * @return array Array of transactions with keys: nominal, tanggal_transaksi, no_transaksi
     */
    public function get_approved_er_transactions($id_spk, $tipe, $year)
    {
        $this->db->select('b.total_expense_report as nominal, DATE(b.created_date) as tanggal_transaksi, b.id as no_transaksi');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe', $tipe);
        $this->db->where('b.sts', 1);
        $this->db->where('YEAR(b.created_date)', $year, false);

        $query = $this->db->get();

        if (!$query || $query->num_rows() === 0) {
            return [];
        }

        return $query->result_array();
    }

    /**
     * Get budget summary per tipe for a given SPK.
     * Calculates Budget, Total Aktual, Pengajuan Terpakai, and Sisa Budget.
     *
     * @param string $id_spk The id_spk_budgeting value
     * @param int $tipe The transaction tipe (2=Akomodasi, 3=Others, 4=Lab, 5=Subcont TA, 6=Subcont Perusahaan)
     * @return object Object with properties: budget, total_aktual, pengajuan_terpakai, sisa_budget
     */
    public function get_summary_per_tipe($id_spk, $tipe)
    {
        // 1. Get Budget from the corresponding budget table
        $budget_tables = [
            2 => 'kons_tr_spk_budgeting_akomodasi',
            3 => 'kons_tr_spk_budgeting_others',
            4 => 'kons_tr_spk_budgeting_lab',
            5 => 'kons_tr_spk_budgeting_subcont_tenaga_ahli',
            6 => 'kons_tr_spk_budgeting_subcont_perusahaan'
        ];

        $budget = 0;
        if (isset($budget_tables[$tipe])) {
            $q = $this->db->select('IFNULL(SUM(total_final), 0) as budget', false)
                ->where('id_spk_budgeting', $id_spk)
                ->get($budget_tables[$tipe]);
            $budget = ($q && $q->num_rows() > 0) ? (float) $q->row()->budget : 0;
        }

        // 2. Calculate Total Aktual
        // Approved ER amounts (Kasbon with approved Expense Report, sts=1)
        $this->db->select('IFNULL(SUM(b.total_expense_report), 0) as total', false);
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe', $tipe);
        $this->db->where('b.sts', 1);
        $query_er  = $this->db->get();
        $actual_er = ($query_er && $query_er->num_rows() > 0) ? (float) $query_er->row()->total : 0;

        // Approved DP amounts (Direct Payment with payment_approve.status=2)
        $this->db->select('IFNULL(SUM(a.grand_total), 0) as total', false);
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join($this->sendigs_db_name . '.payment_approve b', 'b.no_doc = a.id', 'inner');
        $this->db->where('a.metode_pembayaran', 2);
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe', $tipe);
        $this->db->where('b.status', 2);
        $query_dp  = $this->db->get();
        $actual_dp = ($query_dp && $query_dp->num_rows() > 0) ? (float) $query_dp->row()->total : 0;

        $total_aktual = $actual_er + $actual_dp;

        // 3. Calculate Pengajuan Terpakai
        // Simply sum all kasbon grand_total for this SPK and tipe (regardless of ER/payment status)
        $this->db->select('IFNULL(SUM(a.grand_total), 0) as total', false);
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk);
        $this->db->where('a.tipe', $tipe);
        $query_pt          = $this->db->get();
        $pengajuan_terpakai = ($query_pt && $query_pt->num_rows() > 0) ? (float) $query_pt->row()->total : 0;

        // 4. Sisa Budget = Budget - Pengajuan Terpakai
        $sisa_budget = $budget - $pengajuan_terpakai;

        return (object) [
            'budget' => $budget,
            'total_aktual' => $total_aktual,
            'pengajuan_terpakai' => $pengajuan_terpakai,
            'sisa_budget' => $sisa_budget
        ];
    }

    /**
     * Get consolidated report data for Index page DataTable.
     * Combines approved Direct Payment and Expense Report transactions across all SPKs.
     * Excludes Tipe 1 (Subcont Aktifitas) entirely.
     *
     * @param int $year Calendar year to filter transactions
     * @param int $start Pagination offset (DataTable start parameter)
     * @param int $length Page size (DataTable length parameter)
     * @param array|null $search DataTable search parameter array with 'value' key
     * @return array Contains: data (rows), recordsTotal, recordsFiltered, grand_total_budget, grand_total_realisasi, selisih
     */
    public function get_report_data($year, $start, $length, $search)
    {
        $tipe_names = [
            2 => 'Akomodasi',
            3 => 'Others',
            4 => 'Lab',
            5 => 'Subcont Tenaga Ahli',
            6 => 'Subcont Perusahaan'
        ];

        $year = (int) $year;
        $start = (int) $start;
        $length = (int) $length;

        // Build base UNION query combining DP and ER approved transactions
        $sql_parts = [];

        // DP transactions (Direct Payment, approved via payment_approve.status = 2)
        $sql_parts[] = "SELECT 
            a.id_spk_budgeting,
            s.nm_customer,
            a.grand_total AS nominal,
            DATE(a.created_date) AS tanggal_transaksi,
            a.id AS no_transaksi,
            a.tipe,
            a.deskripsi AS item
        FROM kons_tr_kasbon_project_header a
        INNER JOIN {$this->sendigs_db_name}.payment_approve pa ON pa.no_doc = a.id
        LEFT JOIN kons_tr_spk_budgeting s ON s.id_spk_budgeting = a.id_spk_budgeting
        WHERE a.metode_pembayaran = 2
        AND pa.status = 2
        AND a.tipe != 1
        AND YEAR(a.created_date) = {$year}";

        // ER transactions (Kasbon with approved Expense Report, sts = 1)
        $sql_parts[] = "SELECT 
            a.id_spk_budgeting,
            s.nm_customer,
            b.total_expense_report AS nominal,
            DATE(b.created_date) AS tanggal_transaksi,
            b.id AS no_transaksi,
            a.tipe,
            a.deskripsi AS item
        FROM kons_tr_kasbon_project_header a
        INNER JOIN kons_tr_expense_report_project_header b ON b.id_header = a.id
        LEFT JOIN kons_tr_spk_budgeting s ON s.id_spk_budgeting = a.id_spk_budgeting
        WHERE a.metode_pembayaran = 1
        AND b.sts = 1
        AND a.tipe != 1
        AND YEAR(b.created_date) = {$year}";

        // Combine with UNION ALL
        $base_sql = '(' . implode(') UNION ALL (', $sql_parts) . ')';

        // Build search WHERE clause
        $where_clause = '';
        if (!empty($search) && !empty($search['value'])) {
            $search_val = $this->db->escape_like_str($search['value']);
            $where_clause = " WHERE (t.id_spk_budgeting LIKE '%{$search_val}%' OR t.nm_customer LIKE '%{$search_val}%')";
        }

        // Get total record count (without search filter)
        $total_query   = $this->db->query("SELECT COUNT(*) AS cnt FROM ({$base_sql}) AS t");
        $records_total = ($total_query) ? (int) $total_query->row()->cnt : 0;

        // Get filtered record count (with search filter applied)
        $filtered_query   = $this->db->query("SELECT COUNT(*) AS cnt FROM ({$base_sql}) AS t {$where_clause}");
        $records_filtered = ($filtered_query) ? (int) $filtered_query->row()->cnt : 0;

        // Get paginated data ordered by SPK ascending, then tanggal ascending
        $data_sql   = "SELECT * FROM ({$base_sql}) AS t {$where_clause} ORDER BY t.id_spk_budgeting ASC, t.tanggal_transaksi ASC LIMIT {$start}, {$length}";
        $data_query = $this->db->query($data_sql);

        $rows        = [];
        $spk_budgets = [];

        if ($data_query && $data_query->num_rows() > 0) {
            foreach ($data_query->result() as $row) {
                // Map tipe to display name
                $jenis = isset($tipe_names[$row->tipe]) ? $tipe_names[$row->tipe] : '';

                // Cache budget per SPK
                if (!isset($spk_budgets[$row->id_spk_budgeting])) {
                    $spk_budgets[$row->id_spk_budgeting] = $this->get_spk_budget($row->id_spk_budgeting);
                }

                $rows[] = [
                    'id_spk_budgeting'  => $row->id_spk_budgeting,
                    'nm_customer'       => $row->nm_customer,
                    'budget'            => $spk_budgets[$row->id_spk_budgeting],
                    'tanggal_transaksi' => $row->tanggal_transaksi,
                    'no_transaksi'      => $row->no_transaksi,
                    'jenis_pengeluaran' => $jenis,
                    'item'              => $row->item,
                    'nominal'           => (float) $row->nominal
                ];
            }
        }

        // Calculate Grand Total Realisasi from ALL filtered data (not just current page)
        $totals_sql    = "SELECT IFNULL(SUM(t.nominal), 0) AS grand_realisasi FROM ({$base_sql}) AS t {$where_clause}";
        $totals_query  = $this->db->query($totals_sql);
        $grand_total_realisasi = ($totals_query) ? (float) $totals_query->row()->grand_realisasi : 0;

        // Get all distinct SPKs from the filtered dataset for budget total calculation
        $spks_sql           = "SELECT DISTINCT t.id_spk_budgeting FROM ({$base_sql}) AS t {$where_clause}";
        $spks_query         = $this->db->query($spks_sql);
        $grand_total_budget = 0;
        if ($spks_query && $spks_query->num_rows() > 0) {
            foreach ($spks_query->result() as $spk) {
                if (!isset($spk_budgets[$spk->id_spk_budgeting])) {
                    $spk_budgets[$spk->id_spk_budgeting] = $this->get_spk_budget($spk->id_spk_budgeting);
                }
                $grand_total_budget += $spk_budgets[$spk->id_spk_budgeting];
            }
        }

        return [
            'data'                  => $rows,
            'recordsTotal'          => $records_total,
            'recordsFiltered'       => $records_filtered,
            'grand_total_budget'    => $grand_total_budget,
            'grand_total_realisasi' => $grand_total_realisasi,
            'selisih'               => $grand_total_budget - $grand_total_realisasi
        ];
    }

    /**
     * Get all report data for Excel export (no pagination, no search).
     * Same UNION ALL query as get_report_data but returns complete dataset
     * with subtotal rows per SPK group and a Grand Total row at the end.
     *
     * Each element in the returned array has a 'type' key:
     * - 'data': normal transaction row
     * - 'subtotal': subtotal row for a SPK group
     * - 'grand_total': final grand total row
     *
     * @param int $year Calendar year to filter transactions
     * @return array Complete dataset ready for Excel row generation
     */
    public function get_all_report_data($year)
    {
        $tipe_names = [
            2 => 'Akomodasi',
            3 => 'Others',
            4 => 'Lab',
            5 => 'Subcont Tenaga Ahli',
            6 => 'Subcont Perusahaan'
        ];

        $year = (int) $year;

        // 1. Get ALL SPKs matching the index page list for this year — use binding for $year
        $spks_sql = "SELECT a.id_spk_budgeting, a.id_spk_penawaran, a.nm_customer
            FROM kons_tr_spk_budgeting a
            INNER JOIN kons_tr_kasbon_project_header b ON b.id_spk_budgeting = a.id_spk_budgeting
            WHERE b.tipe != 1
            AND YEAR(b.created_date) = ?
            GROUP BY a.id_spk_budgeting
            ORDER BY a.id_spk_budgeting DESC";
        $spk_query = $this->db->query($spks_sql, [$year]);
        $spk_list  = ($spk_query) ? $spk_query->result() : [];

        if (empty($spk_list)) {
            return [];
        }

        // 2. Query all approved transactions (DP + ER)
        $sql_parts = [];

        $sql_parts[] = "SELECT 
            a.id_spk_budgeting,
            s.id_spk_penawaran,
            s.nm_customer,
            a.grand_total AS nominal,
            DATE(a.created_date) AS tanggal_transaksi,
            a.id AS no_transaksi,
            a.tipe,
            a.deskripsi AS item
        FROM kons_tr_kasbon_project_header a
        INNER JOIN {$this->sendigs_db_name}.payment_approve pa ON pa.no_doc = a.id
        LEFT JOIN kons_tr_spk_budgeting s ON s.id_spk_budgeting = a.id_spk_budgeting
        WHERE a.metode_pembayaran = 2
        AND pa.status = 2
        AND a.tipe != 1
        AND YEAR(a.created_date) = {$year}";

        $sql_parts[] = "SELECT 
            a.id_spk_budgeting,
            s.id_spk_penawaran,
            s.nm_customer,
            b.total_expense_report AS nominal,
            DATE(b.created_date) AS tanggal_transaksi,
            b.id AS no_transaksi,
            a.tipe,
            a.deskripsi AS item
        FROM kons_tr_kasbon_project_header a
        INNER JOIN kons_tr_expense_report_project_header b ON b.id_header = a.id
        LEFT JOIN kons_tr_spk_budgeting s ON s.id_spk_budgeting = a.id_spk_budgeting
        WHERE a.metode_pembayaran = 1
        AND b.sts = 1
        AND a.tipe != 1
        AND YEAR(b.created_date) = {$year}";

        $base_sql   = '(' . implode(') UNION ALL (', $sql_parts) . ')';
        $data_sql   = "SELECT * FROM ({$base_sql}) AS t ORDER BY t.id_spk_budgeting ASC, t.tanggal_transaksi ASC";
        $data_query = $this->db->query($data_sql);

        // Group approved transactions by SPK
        $transactions_by_spk = [];
        if ($data_query && $data_query->num_rows() > 0) {
            foreach ($data_query->result() as $tx) {
                $transactions_by_spk[$tx->id_spk_budgeting][] = $tx;
            }
        }

        $rows = [];
        $no = 0;
        $grand_total_realisasi = 0;
        $grand_total_budget = 0;

        // 3. Build data rows for ALL SPKs in index list
        foreach ($spk_list as $spk) {
            $no++;
            $spk_id = $spk->id_spk_budgeting;
            $budget = $this->get_spk_budget($spk_id);
            $grand_total_budget += $budget;
            $spk_subtotal = 0;

            if (isset($transactions_by_spk[$spk_id]) && !empty($transactions_by_spk[$spk_id])) {
                foreach ($transactions_by_spk[$spk_id] as $tx) {
                    $jenis = isset($tipe_names[$tx->tipe]) ? $tipe_names[$tx->tipe] : '';
                    $nominal = (float) $tx->nominal;
                    $spk_subtotal += $nominal;
                    $grand_total_realisasi += $nominal;

                    $rows[] = [
                        'type' => 'data',
                        'no' => $no,
                        'id_spk_budgeting' => $spk_id,
                        'id_spk_penawaran' => !empty($tx->id_spk_penawaran) ? $tx->id_spk_penawaran : $spk->id_spk_penawaran,
                        'nm_customer' => !empty($tx->nm_customer) ? $tx->nm_customer : $spk->nm_customer,
                        'budget' => $budget,
                        'tanggal_transaksi' => $tx->tanggal_transaksi,
                        'no_transaksi' => $tx->no_transaksi,
                        'jenis_pengeluaran' => $jenis,
                        'item' => $tx->item,
                        'nominal' => $nominal
                    ];
                }
            } else {
                // SPK has no approved transactions yet -> include 1 row with empty/dash values
                $rows[] = [
                    'type' => 'data',
                    'no' => $no,
                    'id_spk_budgeting' => $spk_id,
                    'id_spk_penawaran' => $spk->id_spk_penawaran,
                    'nm_customer' => $spk->nm_customer,
                    'budget' => $budget,
                    'tanggal_transaksi' => '',
                    'no_transaksi' => '',
                    'jenis_pengeluaran' => '',
                    'item' => '',
                    'nominal' => 0
                ];
            }

            // Insert Subtotal row for each SPK
            $rows[] = [
                'type' => 'subtotal',
                'id_spk_budgeting' => $spk_id,
                'id_spk_penawaran' => $spk->id_spk_penawaran,
                'nominal' => $spk_subtotal
            ];
        }

        // Grand Total row
        $rows[] = [
            'type' => 'grand_total',
            'grand_total_budget' => $grand_total_budget,
            'grand_total_realisasi' => $grand_total_realisasi,
            'selisih' => $grand_total_budget - $grand_total_realisasi
        ];

        return $rows;
    }

    /**
     * Get approved transactions for a specific SPK and tipe with per-item COA details.
     * Combines DP (payment_approve.status=2) and ER (sts=1) transactions.
     * Joins to detail tables and COA master tables to get item-level COA information.
     *
     * @param string $id_spk The id_spk_budgeting value
     * @param int $tipe The transaction tipe (2=Akomodasi, 3=Others, 4=Lab, 5=Subcont TA, 6=Subcont Perusahaan)
     * @param int $start Pagination offset
     * @param int $length Page size
     * @return array Contains: data (rows with tanggal_transaksi, no_transaksi, coa, jenis_pengeluaran, item, nominal), recordsTotal, recordsFiltered
     */
    public function get_transactions_by_tipe($id_spk, $tipe, $start, $length, $search = '')
    {
        $tipe_names = [
            2 => 'Akomodasi',
            3 => 'Others',
            4 => 'Lab',
            5 => 'Subcont Tenaga Ahli',
            6 => 'Subcont Perusahaan'
        ];

        // Detail table and COA source mapping
        $detail_tables = [
            2 => 'kons_tr_kasbon_project_akomodasi',
            3 => 'kons_tr_kasbon_project_others',
            4 => 'kons_tr_kasbon_project_lab',
            5 => 'kons_tr_kasbon_project_subcont_tenaga_ahli',
            6 => 'kons_tr_kasbon_project_subcont_perusahaan'
        ];

        $coa_tables = [
            2 => 'kons_master_biaya',
            3 => 'kons_master_biaya',
            4 => 'kons_master_lab',
            5 => 'kons_master_tenaga_ahli',
            6 => 'kons_master_subcont_perusahaan'
        ];

        if (!isset($detail_tables[$tipe])) {
            return ['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }

        $detail_table = $detail_tables[$tipe];
        $coa_table = $coa_tables[$tipe];
        $jenis = $tipe_names[$tipe];
        $start = (int) $start;
        $length = (int) $length;
        $escaped_id_spk = $this->db->escape($id_spk);

        // Build UNION for DP and ER transactions with detail items and COA
        $sql_parts = [];

        // DP part (Direct Payment, metode_pembayaran=2, payment_approve.status=2)
        $sql_parts[] = "SELECT 
            DATE(h.created_date) AS tanggal_transaksi,
            h.id AS no_transaksi,
            CASE WHEN d.id_item IS NOT NULL AND m.no_coa IS NOT NULL AND m.no_coa != '' 
                THEN CONCAT('(', m.no_coa, ') - ', m.nm_coa) 
                ELSE '5101-01-03' 
            END AS coa,
            '{$jenis}' AS jenis_pengeluaran,
            d.nm_item AS item,
            d.total_pengajuan AS nominal,
            'Direct Payment' AS jenis_transaksi
        FROM kons_tr_kasbon_project_header h
        INNER JOIN {$this->sendigs_db_name}.payment_approve pa ON pa.no_doc = h.id
        INNER JOIN {$detail_table} d ON d.id_header = h.id
        LEFT JOIN {$coa_table} m ON m.id = d.id_item
        WHERE h.metode_pembayaran = 2
        AND h.id_spk_budgeting = {$escaped_id_spk}
        AND h.tipe = {$tipe}
        AND pa.status = 2";

        // ER part (Kasbon with approved Expense Report, metode_pembayaran=1, er.sts=1)
        // Join ER detail via kasbon header only (id_detail_kasbon doesn't match detail table IDs reliably)
        $sql_parts[] = "SELECT 
            DATE(er.created_date) AS tanggal_transaksi,
            er.id AS no_transaksi,
            CASE WHEN d.id_item IS NOT NULL AND m.no_coa IS NOT NULL AND m.no_coa != '' 
                THEN CONCAT('(', m.no_coa, ') - ', m.nm_coa) 
                ELSE '5101-01-03' 
            END AS coa,
            '{$jenis}' AS jenis_pengeluaran,
            d.nm_item AS item,
            COALESCE(
                (SELECT SUM(erd.nominal_expense)
                 FROM kons_tr_expense_report_project_detail erd
                 WHERE erd.id_header_kasbon = h.id AND erd.id_detail_kasbon = d.id),
                (SELECT SUM(erd2.nominal_expense)
                 FROM kons_tr_expense_report_project_detail erd2
                 WHERE erd2.id_header_kasbon = h.id
                   AND (SELECT COUNT(*) FROM {$detail_table} dx WHERE dx.id_header = h.id) = 1),
                d.total_pengajuan
            ) AS nominal,
            'Expense Report' AS jenis_transaksi
        FROM kons_tr_kasbon_project_header h
        INNER JOIN kons_tr_expense_report_project_header er ON er.id_header = h.id
        INNER JOIN {$detail_table} d ON d.id_header = h.id
        LEFT JOIN {$coa_table} m ON m.id = d.id_item
        WHERE h.metode_pembayaran = 1
        AND h.id_spk_budgeting = {$escaped_id_spk}
        AND h.tipe = {$tipe}
        AND er.sts = 1";

        $base_sql = '(' . implode(') UNION ALL (', $sql_parts) . ')';

        // Build search condition
        $search_where = '';
        if (!empty($search)) {
            $search_val = $this->db->escape_like_str($search);
            $search_where = " WHERE (t.no_transaksi LIKE '%{$search_val}%' OR t.item LIKE '%{$search_val}%' OR t.coa LIKE '%{$search_val}%' OR t.jenis_transaksi LIKE '%{$search_val}%')";
        }

        // Count total records (without search)
        $count_query   = $this->db->query("SELECT COUNT(*) AS cnt FROM ({$base_sql}) AS t");
        $records_total = ($count_query) ? (int) $count_query->row()->cnt : 0;

        // Count filtered records (with search)
        $count_filtered_query = $this->db->query("SELECT COUNT(*) AS cnt FROM ({$base_sql}) AS t {$search_where}");
        $records_filtered     = ($count_filtered_query) ? (int) $count_filtered_query->row()->cnt : 0;

        // Get paginated data, ordered by tanggal_transaksi descending
        $data_sql   = "SELECT * FROM ({$base_sql}) AS t {$search_where} ORDER BY t.tanggal_transaksi DESC LIMIT {$start}, {$length}";
        $data_query = $this->db->query($data_sql);

        $rows = [];
        if ($data_query && $data_query->num_rows() > 0) {
            foreach ($data_query->result() as $row) {
                $rows[] = [
                    'tanggal_transaksi' => $row->tanggal_transaksi,
                    'no_transaksi'      => $row->no_transaksi,
                    'coa'               => $row->coa,
                    'jenis_pengeluaran' => $row->jenis_pengeluaran,
                    'item'              => $row->item,
                    'jenis_transaksi'   => $row->jenis_transaksi,
                    'actual'            => (float) $row->nominal
                ];
            }
        }

        return [
            'data' => $rows,
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered
        ];
    }

    /**
     * Get SPK header information with joined penawaran and project data.
     *
     * @param string $id_spk The id_spk_budgeting value
     * @return object|null Object with: id_spk_budgeting, nm_customer, nm_project_leader, nm_sales, alamat, waktu_from, waktu_to, nama_project
     */
    public function get_spk_header($id_spk)
    {
        $this->db->select('a.id_spk_budgeting, a.id_spk_penawaran, a.nm_customer, a.nm_project_leader, a.alamat, b.nm_sales, b.waktu_from, b.waktu_to, c.nm_paket as nama_project');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk);

        $query = $this->db->get();
        return ($query && $query->num_rows() > 0) ? $query->row() : null;
    }
}
