<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Laporan_kunjungan_model extends BF_Model
{

    protected $table_name = 'visit_report_headers';
    protected $key = 'id';
    protected $set_created = false;
    protected $set_modified = false;

    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================================
    // SPK Query Methods (SELECT only on existing tables)
    // =========================================================================

    /**
     * Get paginated list of approved SPK projects assigned to a consultant.
     *
     * Returns only records where approval_level2_sts = 1 AND the consultant
     * is assigned as id_konsultan_1 or id_konsultan_2.
     *
     * @param int    $user_id  Consultant user ID
     * @param int    $start    Offset for pagination
     * @param int    $length   Number of records per page
     * @param string $search   Search term (partial match on No SPK, company, project, leader)
     *
     * @return array ['data' => array of rows, 'total' => int total count, 'filtered' => int filtered count]
     */
    public function get_approved_spk_list($user_id, $start, $length, $search = '', $is_admin = false)
    {
        // Base conditions - only show SPK with Approved status (sts_spk = '1')
        $this->db->select('a.*, c.nm_paket');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = b.id_paket', 'left');
        $this->db->where('a.sts_spk', '1');

        // Get total count (before search filter)
        $total = $this->db->count_all_results('', false);

        // Apply search filter
        if (!empty($search)) {
            $this->db->group_start();
                $this->db->like('a.id_spk_penawaran', $search, 'both', false);
                $this->db->or_like('a.nm_customer', $search, 'both', false);
                $this->db->or_like('a.nm_project', $search, 'both', false);
                $this->db->or_like('c.nm_paket', $search, 'both', false);
                $this->db->or_like('a.nm_project_leader', $search, 'both', false);
                $this->db->or_like('a.nm_konsultan_1', $search, 'both', false);
                $this->db->or_like('a.nm_konsultan_2', $search, 'both', false);
            $this->db->group_end();
        }

        // Get filtered count
        $filtered = $this->db->count_all_results('', false);

        // Apply ordering and pagination
        $this->db->order_by('a.input_date', 'DESC');
        $this->db->limit($length, $start);

        $data = $this->db->get()->result();

        return [
            'data'     => $data,
            'total'    => $total,
            'filtered' => $filtered
        ];
    }

    /**
     * Get single SPK project details.
     * Joins with kons_tr_penawaran and kons_master_konsultasi_header
     * to get Consultation Package name as project name.
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return object|null SPK row or null if not found
     */
    public function get_spk_detail($id_spk_penawaran)
    {
        $this->db->select('a.*, b.id_paket, c.nm_paket');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = b.id_paket', 'left');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);

        $query = $this->db->get();

        if ($query === false) {
            return null;
        }

        return $query->row();
    }

    /**
     * Get activity list for an SPK project.
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return array Activity rows from kons_tr_spk_aktifitas
     */
    public function get_spk_activities($id_spk_penawaran)
    {
        $this->db->select('id, id_spk_penawaran, nm_aktifitas');
        $this->db->from('kons_tr_spk_aktifitas');
        $this->db->where('id_spk_penawaran', $id_spk_penawaran);

        $query = $this->db->get();

        if ($query === false) {
            return [];
        }

        return $query->result();
    }

    /**
     * Get total allocated mandays for an SPK project.
     * Calculated as SUM(mandays + mandays_tandem + mandays_subcont).
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return int Total mandays (0 if no rows found)
     */
    public function get_mandays_total($id_spk_penawaran)
    {
        $this->db->select_sum('mandays');
        $this->db->select_sum('mandays_tandem');
        $this->db->select_sum('mandays_subcont');
        $this->db->from('kons_tr_spk_penawaran_subcont');
        $this->db->where('id_spk_penawaran', $id_spk_penawaran);

        $row = $this->db->get()->row();

        if ($row) {
            $mandays = (int) $row->mandays + (int) $row->mandays_tandem + (int) $row->mandays_subcont;
            return $mandays;
        }

        return 0;
    }

    /**
     * Get count of finalized visit reports for an SPK project (mandays used).
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return int Count of finalized reports
     */
    public function get_mandays_used($id_spk_penawaran)
    {
        $this->db->from('visit_report_headers');
        $this->db->where('id_spk_penawaran', $id_spk_penawaran);
        $this->db->where('status', 'final');

        return $this->db->count_all_results();
    }

    /**
     * Get total count of finalized visit reports for an SPK project.
     * This is equivalent to get_mandays_used (same logic).
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return int Count of finalized reports
     */
    public function get_visit_report_count($id_spk_penawaran)
    {
        return $this->get_mandays_used($id_spk_penawaran);
    }

    // =========================================================================
    // Visit Report CRUD Methods
    // =========================================================================

    /**
     * Generate a unique report ID in format VR{YYMM}{4-digit sequence}.
     * Example: VR25060001 (first report in June 2025).
     * Retries up to 3 times on duplicate.
     *
     * @return string|false Generated report ID or false on failure
     */
    public function generate_report_id()
    {
        $prefix = 'VR' . date('ym');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            // Get the max existing report_id for the current month prefix
            $this->db->select_max('report_id', 'max_id');
            $this->db->from('visit_report_headers');
            $this->db->like('report_id', $prefix, 'after');
            $row = $this->db->get()->row();

            if ($row && !empty($row->max_id)) {
                // Extract the sequence number (last 4 digits) and increment
                $last_sequence = (int) substr($row->max_id, -4);
                $next_sequence = $last_sequence + 1;
            } else {
                $next_sequence = 1;
            }

            $report_id = $prefix . str_pad($next_sequence, 4, '0', STR_PAD_LEFT);

            // Check if this ID already exists (race condition protection)
            $this->db->where('report_id', $report_id);
            $exists = $this->db->count_all_results('visit_report_headers');

            if ($exists == 0) {
                return $report_id;
            }
        }

        return false;
    }

    /**
     * Save a new visit report header.
     *
     * @param array $data Associative array of header fields
     *
     * @return int|false Inserted ID or false on failure
     */
    public function save_report_header($data)
    {
        $this->db->insert('visit_report_headers', $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * Update an existing visit report header.
     *
     * @param int   $id   Header primary key
     * @param array $data Associative array of fields to update
     *
     * @return bool True on success, false on failure
     */
    public function update_report_header($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('visit_report_headers', $data);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Batch insert activities for a visit report.
     *
     * @param int   $id_report  Report header ID
     * @param array $activities Array of activity data arrays
     *
     * @return bool True on success
     */
    public function save_report_activities($id_report, $activities)
    {
        if (empty($activities)) {
            return true;
        }

        $batch = [];
        foreach ($activities as $index => $activity) {
            $batch[] = [
                'report_id'       => $id_report,
                'activity_source' => isset($activity['activity_source']) ? $activity['activity_source'] : 'custom',
                'spk_activity_id' => isset($activity['spk_activity_id']) ? $activity['spk_activity_id'] : null,
                'activity_name'   => $activity['activity_name'],
                'sort_order'      => isset($activity['sort_order']) ? $activity['sort_order'] : $index + 1,
            ];
        }

        return $this->db->insert_batch('visit_report_activities', $batch);
    }

    /**
     * Batch insert action plans for an activity.
     *
     * @param int   $id_activity Activity ID
     * @param array $plans       Array of action plan data arrays
     *
     * @return bool True on success
     */
    public function save_action_plans($id_activity, $plans)
    {
        if (empty($plans)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $batch = [];
        foreach ($plans as $plan) {
            $batch[] = [
                'activity_id'  => $id_activity,
                'report_id'    => $plan['report_id'],
                'description'  => $plan['description'],
                'pic'          => $plan['pic'],
                'due_date'     => $plan['due_date'],
                'status'       => isset($plan['status']) ? $plan['status'] : 'progress',
                'created_at'   => $now,
                'updated_at'   => null,
            ];
        }

        return $this->db->insert_batch('visit_report_action_plans', $batch);
    }

    /**
     * Batch insert improvements for a visit report.
     *
     * @param int   $id_report    Report header ID
     * @param array $improvements Array of improvement data arrays
     *
     * @return bool True on success
     */
    public function save_improvements($id_report, $improvements)
    {
        if (empty($improvements)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $batch = [];
        foreach ($improvements as $index => $improvement) {
            $batch[] = [
                'report_id'            => $id_report,
                'sort_order'           => isset($improvement['sort_order']) ? $improvement['sort_order'] : $index + 1,
                'potensi_improvement'  => $improvement['potensi_improvement'],
                'hasil_improvement'    => isset($improvement['hasil_improvement']) ? $improvement['hasil_improvement'] : '',
                'status'               => isset($improvement['status']) ? $improvement['status'] : 'progress',
                'created_at'           => $now,
                'updated_at'           => null,
            ];
        }

        return $this->db->insert_batch('visit_report_improvements', $batch);
    }

    /**
     * Delete all detail records for a report (for re-save on update).
     * Deletes in correct order: action_plans first, then activities, then improvements.
     *
     * @param int $id_report Report header ID
     *
     * @return bool True on success
     */
    public function delete_report_details($id_report)
    {
        // 1. Delete action plans by report_id
        $this->db->where('report_id', $id_report);
        $this->db->delete('visit_report_action_plans');

        // 2. Delete activities by report_id
        $this->db->where('report_id', $id_report);
        $this->db->delete('visit_report_activities');

        // 3. Delete improvements by report_id
        $this->db->where('report_id', $id_report);
        $this->db->delete('visit_report_improvements');

        return true;
    }

    /**
     * Get a full visit report by ID with all relations.
     * Returns header + nested activities (each with their action plans) + improvements.
     *
     * @param int $id_report Report header ID
     *
     * @return array|null Report data array or null if not found
     */
    public function get_report_by_id($id_report)
    {
        // Get header
        $this->db->from('visit_report_headers');
        $this->db->where('id', $id_report);
        $header = $this->db->get()->row_array();

        if (empty($header)) {
            return null;
        }

        // Get activities
        $this->db->from('visit_report_activities');
        $this->db->where('report_id', $id_report);
        $this->db->order_by('sort_order', 'ASC');
        $activities = $this->db->get()->result_array();

        // Get action plans for all activities in this report
        $this->db->from('visit_report_action_plans');
        $this->db->where('report_id', $id_report);
        $this->db->order_by('id', 'ASC');
        $action_plans = $this->db->get()->result_array();

        // Group action plans by activity_id
        $plans_by_activity = [];
        foreach ($action_plans as $plan) {
            $plans_by_activity[$plan['activity_id']][] = $plan;
        }

        // Attach action plans to their respective activities
        foreach ($activities as &$activity) {
            $activity['action_plans'] = isset($plans_by_activity[$activity['id']])
                ? $plans_by_activity[$activity['id']]
                : [];
        }
        unset($activity);

        // Get improvements
        $this->db->from('visit_report_improvements');
        $this->db->where('report_id', $id_report);
        $this->db->order_by('sort_order', 'ASC');
        $improvements = $this->db->get()->result_array();

        return [
            'header'       => $header,
            'activities'   => $activities,
            'improvements' => $improvements,
        ];
    }

    /**
     * Get paginated list of visit reports for a specific user.
     * Supports status filtering and search on project_name, company_name, visit_date.
     *
     * @param int    $user_id       Consultant user ID
     * @param int    $start         Offset for pagination
     * @param int    $length        Number of records per page
     * @param string $search        Search term
     * @param string $status_filter Status filter: 'all', 'draft', or 'final'
     *
     * @return array ['data' => array of rows, 'total' => int, 'filtered' => int]
     */
    public function get_reports_by_user($user_id, $start, $length, $search = '', $status_filter = 'all')
    {
        // Base conditions
        $this->db->from('visit_report_headers');
        $this->db->where('consultant_id', $user_id);

        // Apply status filter
        if ($status_filter !== 'all' && in_array($status_filter, ['draft', 'final'])) {
            $this->db->where('status', $status_filter);
        }

        // Get total count (before search filter)
        $total = $this->db->count_all_results('', false);

        // Apply search filter
        if (!empty($search)) {
            $this->db->group_start();
                $this->db->like('project_name', $search, 'both', false);
                $this->db->or_like('company_name', $search, 'both', false);
                $this->db->or_like('visit_date', $search, 'both', false);
            $this->db->group_end();
        }

        // Get filtered count
        $filtered = $this->db->count_all_results('', false);

        // Apply ordering and pagination
        $this->db->order_by('visit_date', 'DESC');
        $this->db->limit($length, $start);

        $data = $this->db->get()->result();

        return [
            'data'     => $data,
            'total'    => $total,
            'filtered' => $filtered,
        ];
    }

    // =========================================================================
    // Previous Action Plan and Improvement Query Methods
    // =========================================================================

    /**
     * Get all previous action plans from earlier finalized reports for the same project.
     *
     * Joins visit_report_action_plans with visit_report_activities and visit_report_headers
     * to retrieve action plans from finalized reports, ordered by visit_date DESC.
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return array Action plan rows with visit_date, consultant_name, activity_name, description, pic, due_date, status, id
     */
    public function get_previous_action_plans($id_spk_penawaran)
    {
        $this->db->select('vrh.visit_date, vrh.consultant_name, vra.activity_name, vrap.description, vrap.pic, vrap.due_date, vrap.status, vrap.id');
        $this->db->from('visit_report_action_plans vrap');
        $this->db->join('visit_report_activities vra', 'vra.id = vrap.activity_id', 'inner');
        $this->db->join('visit_report_headers vrh', 'vrh.id = vrap.report_id', 'inner');
        $this->db->where('vrh.id_spk_penawaran', $id_spk_penawaran);
        $this->db->where('vrh.status', 'final');
        $this->db->order_by('vrh.visit_date', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Get all previous improvements from earlier finalized reports for the same project.
     *
     * Joins visit_report_improvements with visit_report_headers to retrieve improvements
     * from finalized reports, ordered by visit_date DESC, sort_order ASC.
     *
     * @param string $id_spk_penawaran SPK identifier
     *
     * @return array Improvement rows with visit_date, consultant_name, sort_order, potensi_improvement, hasil_improvement, status, id
     */
    public function get_previous_improvements($id_spk_penawaran)
    {
        $this->db->select('vrh.visit_date, vrh.consultant_name, vri.sort_order, vri.potensi_improvement, vri.hasil_improvement, vri.status, vri.id');
        $this->db->from('visit_report_improvements vri');
        $this->db->join('visit_report_headers vrh', 'vrh.id = vri.report_id', 'inner');
        $this->db->where('vrh.id_spk_penawaran', $id_spk_penawaran);
        $this->db->where('vrh.status', 'final');
        $this->db->order_by('vrh.visit_date', 'DESC');
        $this->db->order_by('vri.sort_order', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Update a single action plan status.
     *
     * Only allows transition from 'progress' to 'done'.
     * Rejects transition from 'done' to 'progress' or any invalid status.
     *
     * @param int    $id_plan Action plan ID
     * @param string $status  New status value (only 'done' is accepted)
     *
     * @return bool True on successful update, false on failure or invalid transition
     */
    public function update_action_plan_status($id_plan, $status)
    {
        // Only allow setting status to 'done'
        if ($status !== 'done') {
            return false;
        }

        // Check current status
        $this->db->select('status');
        $this->db->from('visit_report_action_plans');
        $this->db->where('id', $id_plan);
        $current = $this->db->get()->row();

        // Record not found
        if (!$current) {
            return false;
        }

        // Already done — reject (no reversal allowed)
        if ($current->status === 'done') {
            return false;
        }

        // Perform the update
        $this->db->where('id', $id_plan);
        $result = $this->db->update('visit_report_action_plans', [
            'status'     => 'done',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $result;
    }
}
