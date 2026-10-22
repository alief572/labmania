<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Laporan Kunjungan (Visit Report) Controller
 *
 * Handles visit report management including SPK project listing,
 * visit report creation, editing, and report generation.
 *
 * @author  System
 * @copyright Copyright (c) 2025
 */
class Laporan_kunjungan extends Admin_Controller
{
    // Permissions
    protected $viewPermission   = 'Laporan_Kunjungan.View';
    protected $addPermission    = 'Laporan_Kunjungan.Add';
    protected $managePermission = 'Laporan_Kunjungan.Manage';
    protected $deletePermission = 'Laporan_Kunjungan.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Laporan Kunjungan');
        $this->template->page_icon('fa fa-clipboard');
        $this->load->model('laporan_kunjungan/Laporan_kunjungan_model');
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * Display SPK project list page with DataTables.
     * Restricted by viewPermission.
     *
     * @return void
     */
    public function index()
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        $this->template->title('Laporan Kunjungan - Daftar SPK');
        $this->template->render('index');
    }

    /**
     * Server-side DataTables endpoint for approved SPK project list.
     * Returns JSON response with draw, recordsTotal, recordsFiltered, data.
     *
     * Only returns SPK projects where:
     * - approval_level2_sts = 1 (approved)
     * - Logged-in consultant is assigned as id_konsultan_1 or id_konsultan_2
     *
     * @return void Outputs JSON
     */
    public function get_data_spk()
    {
        $draw   = $this->input->post('draw');
        $start  = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $user_id    = $this->auth->user_id();
        $is_admin   = $this->auth->is_admin();
        $search_val = isset($search['value']) ? $search['value'] : '';

        // Get paginated SPK list from model
        // Admin can see all approved SPK, consultants only see their own
        $result = $this->Laporan_kunjungan_model->get_approved_spk_list(
            $user_id,
            (int) $start,
            (int) $length,
            $search_val,
            $is_admin
        );

        // Get list of SPK IDs that have existing drafts for the CURRENT user
        $spk_ids_in_result = array_map(function($item) { return $item->id_spk_penawaran; }, $result['data']);
        $drafts_map = [];
        if (!empty($spk_ids_in_result)) {
            $this->db->select('id_spk_penawaran');
            $this->db->from('visit_report_headers');
            $this->db->where('status', 'draft');
            $this->db->where('consultant_id', $this->auth->user_id());
            $this->db->where_in('id_spk_penawaran', $spk_ids_in_result);
            $draft_rows = $this->db->get()->result();
            foreach ($draft_rows as $dr) {
                $drafts_map[$dr->id_spk_penawaran] = true;
            }
        }

        $hasil = [];
        $no = (int) $start;

        foreach ($result['data'] as $item) {
            $no++;

            // Build action buttons based on permissions
            $action = '';
            // Encode id_spk_penawaran: replace / with _SLASH_ for URL safety
            $id_encoded = str_replace('/', '_SLASH_', $item->id_spk_penawaran);

            if (has_permission($this->viewPermission)) {
                $action .= '<a href="' . base_url('laporan_kunjungan/view_spk/' . $id_encoded) . '" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i> View</a> ';
            }

            if (has_permission($this->addPermission)) {
                $has_draft = isset($drafts_map[$item->id_spk_penawaran]);
                if ($has_draft) {
                    $action .= '<a href="' . base_url('laporan_kunjungan/create/' . $id_encoded) . '" class="btn btn-sm btn-warning" title="Lanjutkan Draft"><i class="fa fa-pencil"></i> Visit</a> ';
                } else {
                    $action .= '<a href="' . base_url('laporan_kunjungan/create/' . $id_encoded) . '" class="btn btn-sm btn-success" title="Buat Laporan Kunjungan"><i class="fa fa-plus"></i> Visit</a> ';
                }
            }

            if (has_permission($this->viewPermission)) {
                $action .= '<a href="' . base_url('laporan_kunjungan/view/' . $id_encoded) . '" class="btn btn-sm btn-default" title="Report"><i class="fa fa-file-text-o"></i> Report</a> ';
            }

            // Format consultant names
            $konsultan = '';
            if (!empty($item->nm_konsultan_1)) {
                $konsultan .= $item->nm_konsultan_1;
            }
            if (!empty($item->nm_konsultan_2)) {
                $konsultan .= (!empty($konsultan) ? ', ' : '') . $item->nm_konsultan_2;
            }

            // Format target completion date (waktu_to = end date from SPK)
            $target_selesai = !empty($item->waktu_to) ? date('d-m-Y', strtotime($item->waktu_to)) : '-';

            $hasil[] = [
                'no'              => $no,
                'perusahaan'      => $item->nm_customer,
                'project'         => !empty($item->nm_paket) ? $item->nm_paket : $item->nm_project,
                'project_leader'  => ucfirst($item->nm_project_leader ?? ''),
                'konsultan'       => $konsultan,
                'target_selesai'  => $target_selesai,
                'action'          => $action
            ];
        }

        echo json_encode([
            'draw'            => intval($draw),
            'recordsTotal'    => (int) $result['total'],
            'recordsFiltered' => (int) $result['filtered'],
            'data'            => $hasil
        ]);
    }

    /**
     * Display visit report list page with DataTables.
     * Restricted by viewPermission.
     * Optionally filters by a specific SPK project when $id_spk_penawaran is provided.
     *
     * @param string|null $id_spk_penawaran Pipe-encoded SPK ID (slashes replaced with pipes)
     *
     * @return void
     */
    public function visit_reports($id_spk_penawaran = null)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        $data = [];

        if (!empty($id_spk_penawaran)) {
            // Decode _SLASH_ encoding back to slashes
            $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);
            $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($id_spk_decoded);
            $data['id_spk_penawaran'] = $id_spk_decoded;
            $data['spk_info'] = $spk_info ? $spk_info : null;
        } else {
            $data['id_spk_penawaran'] = null;
            $data['spk_info'] = null;
        }

        $this->template->set($data);
        $this->template->title('Laporan Kunjungan - Daftar Visit Report');
        $this->template->render('visit_reports');
    }

    /**
     * Server-side DataTables endpoint for visit report list.
     * Returns JSON response with draw, recordsTotal, recordsFiltered, data.
     *
     * Supports custom status_filter parameter (values: 'all', 'draft', 'final').
     * Only returns reports belonging to the logged-in consultant.
     *
     * @return void Outputs JSON
     */
    public function get_data_visit_reports()
    {
        $draw   = $this->input->post('draw');
        $start  = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $user_id       = $this->auth->user_id();
        $search_val    = isset($search['value']) ? $search['value'] : '';
        $status_filter = $this->input->post('status_filter');

        // Default to 'all' if not provided or invalid
        if (empty($status_filter) || !in_array($status_filter, ['all', 'draft', 'final'])) {
            $status_filter = 'all';
        }

        // Get paginated visit reports from model
        $result = $this->Laporan_kunjungan_model->get_reports_by_user(
            $user_id,
            (int) $start,
            (int) $length,
            $search_val,
            $status_filter
        );

        $hasil = [];
        $no = (int) $start;

        foreach ($result['data'] as $item) {
            $no++;

            // Format visit_date as dd-mm-yyyy
            $visit_date = !empty($item->visit_date) ? date('d-m-Y', strtotime($item->visit_date)) : '-';

            // Format start_time and finish_time as dd-mm-yyyy HH:MM
            $start_time  = !empty($item->start_time) ? date('d-m-Y H:i', strtotime($item->start_time)) : '-';
            $finish_time = !empty($item->finish_time) ? date('d-m-Y H:i', strtotime($item->finish_time)) : '-';

            // Status badge
            if ($item->status === 'final') {
                $status_badge = '<span class="label label-success">Final</span>';
            } else {
                $status_badge = '<span class="label label-warning">Draft</span>';
            }

            // Build action buttons
            $action = '';

            // View button — always visible
            $action .= '<a href="' . base_url('laporan_kunjungan/view/' . $item->id) . '" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a> ';

            // Download PDF button — only for final
            if ($item->status === 'final') {
                $action .= '<a href="' . base_url('laporan_kunjungan/generate_pdf/' . $item->id) . '" class="btn btn-sm btn-default" title="Download Report"><i class="fa fa-file-pdf-o"></i></a> ';
            }

            $hasil[] = [
                'no'          => $no,
                'visit_date'  => $visit_date,
                'perusahaan'  => $item->company_name,
                'project'     => $item->project_name,
                'start_time'  => $start_time,
                'finish_time' => $finish_time,
                'status'      => $status_badge,
                'action'      => $action
            ];
        }

        echo json_encode([
            'draw'            => intval($draw),
            'recordsTotal'    => (int) $result['total'],
            'recordsFiltered' => (int) $result['filtered'],
            'data'            => $hasil
        ]);
    }

    /**
     * Display visit report creation form pre-populated with SPK data.
     * Restricted by addPermission.
     *
     * @param string $id_spk_penawaran Pipe-encoded SPK ID (slashes replaced with pipes)
     *
     * @return void
     */
    public function create($id_spk_penawaran = null)
    {
        // Admin bypass or check addPermission
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->addPermission);
        }

        // Handle multiple URI segments (id_spk_penawaran may contain slashes)
        if ($id_spk_penawaran === null) {
            $this->session->set_flashdata('message', 'ID SPK tidak valid.');
            redirect('laporan_kunjungan');
        }

        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        // Check if there's an existing draft for this SPK by the CURRENT user — if yes, redirect to edit
        $this->db->from('visit_report_headers');
        $this->db->where('id_spk_penawaran', $id_spk_decoded);
        $this->db->where('status', 'draft');
        $this->db->where('consultant_id', $this->auth->user_id());
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $existing_draft = $this->db->get()->row();

        if ($existing_draft) {
            redirect('laporan_kunjungan/edit/' . $existing_draft->id);
            return;
        }

        // Get SPK detail from model
        $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($id_spk_decoded);

        if (empty($spk_info)) {
            $this->session->set_flashdata('message', 'Data SPK tidak ditemukan. ID: ' . $id_spk_decoded);
            redirect('laporan_kunjungan');
        }

        $data = [
            'spk_info'        => $spk_info,
            'current_date'    => date('Y-m-d'),
            'consultant_name' => $this->auth->nama(),
            'consultant_id'   => $this->auth->user_id(),
        ];

        $this->template->set($data);
        $this->template->title('Laporan Kunjungan - Buat Laporan');
        $this->template->render('create');
    }

    /**
     * AJAX POST: Get SPK activities for a project.
     * Returns JSON: {status: 1, data: [...activities]}
     *
     * @param string $id_spk_penawaran Pipe-encoded SPK ID
     *
     * @return void Outputs JSON
     */
    public function get_activities($id_spk_penawaran)
    {
        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        $activities = $this->Laporan_kunjungan_model->get_spk_activities($id_spk_decoded);

        echo json_encode([
            'status' => 1,
            'data'   => $activities
        ]);
    }

    /**
     * AJAX POST: Get previous action plans and improvements for a project.
     * Returns JSON: {status: 1, action_plans: [...], improvements: [...]}
     *
     * @param string $id_spk_penawaran Pipe-encoded SPK ID
     *
     * @return void Outputs JSON
     */
    public function get_previous_action_plans($id_spk_penawaran)
    {
        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        $action_plans  = $this->Laporan_kunjungan_model->get_previous_action_plans($id_spk_decoded);
        $improvements  = $this->Laporan_kunjungan_model->get_previous_improvements($id_spk_decoded);

        echo json_encode([
            'status'       => 1,
            'action_plans' => $action_plans,
            'improvements' => $improvements
        ]);
    }

    /**
     * AJAX POST: Get mandays allocation information for a project.
     * Returns JSON: {status: 1, mandays_total: int, mandays_used: int, report_count: int}
     *
     * @param string $id_spk_penawaran Pipe-encoded SPK ID
     *
     * @return void Outputs JSON
     */
    public function get_mandays_info($id_spk_penawaran)
    {
        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        $mandays_total = $this->Laporan_kunjungan_model->get_mandays_total($id_spk_decoded);
        $mandays_used  = $this->Laporan_kunjungan_model->get_mandays_used($id_spk_decoded);
        $report_count  = $this->Laporan_kunjungan_model->get_visit_report_count($id_spk_decoded);

        echo json_encode([
            'status'        => 1,
            'mandays_total' => (int) $mandays_total,
            'mandays_used'  => (int) $mandays_used,
            'report_count'  => (int) $report_count
        ]);
    }

    /**
     * Load draft report for editing with all fields pre-populated.
     * Restricted by managePermission.
     * Rejects if report status is 'final' or if consultant doesn't own the report.
     *
     * @param int $id_report Report header ID
     *
     * @return void
     */
    public function edit($id_report)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->managePermission);
        }

        // Get full report data
        $report = $this->Laporan_kunjungan_model->get_report_by_id($id_report);

        if (empty($report)) {
            $this->session->set_flashdata('message', 'Data laporan tidak ditemukan.');
            redirect('laporan_kunjungan');
        }

        // Check if report is finalized — reject editing
        if ($report['header']['status'] === 'final') {
            $this->session->set_flashdata('message', 'Laporan yang sudah final tidak dapat diedit.');
            redirect('laporan_kunjungan');
        }

        // Check ownership — only the consultant who created the report can edit (admin bypass)
        if (!$this->auth->is_admin()) {
            if ((int) $report['header']['consultant_id'] !== (int) $this->auth->user_id()) {
                $this->session->set_flashdata('message', 'Anda tidak memiliki akses untuk mengedit laporan ini.');
                redirect('laporan_kunjungan');
            }
        }

        // Get SPK info for the report
        $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($report['header']['id_spk_penawaran']);

        $data = [
            'report'          => $report,
            'spk_info'        => $spk_info,
            'current_date'    => date('Y-m-d'),
            'consultant_name' => $this->auth->nama(),
            'consultant_id'   => $this->auth->user_id(),
        ];

        $this->template->set($data);
        $this->template->title('Laporan Kunjungan - Edit Laporan');
        $this->template->render('edit');
    }

    /**
     * Display SPK overview with visit list.
     * Shows SPK info and all visit reports for this SPK.
     * Restricted by viewPermission.
     *
     * @param string $id_spk_penawaran _SLASH_-encoded SPK ID
     *
     * @return void
     */
    public function view_spk($id_spk_penawaran)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        // Get SPK info
        $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($id_spk_decoded);

        if (empty($spk_info)) {
            $this->session->set_flashdata('message', 'Data SPK tidak ditemukan.');
            redirect('laporan_kunjungan');
        }

        // Get list of all visit reports for this SPK
        $this->db->select('id, report_id, visit_date, consultant_name, start_time, finish_time, status');
        $this->db->from('visit_report_headers');
        $this->db->where('id_spk_penawaran', $id_spk_decoded);
        $this->db->order_by('visit_date', 'DESC');
        $visit_list = $this->db->get()->result();

        $data = [
            'spk_info'          => $spk_info,
            'visit_list'        => $visit_list,
            'id_spk_penawaran'  => $id_spk_decoded,
        ];

        $this->template->set($data);
        $this->template->title('Laporan Kunjungan - View SPK');
        $this->template->render('view_spk');
    }

    /**
     * AJAX GET: Get visit report detail for modal display (read-only).
     * Returns JSON with header info, action plans, and improvements for a single visit.
     *
     * @param int $id_report Report header ID
     *
     * @return void Outputs JSON
     */
    public function get_visit_detail($id_report)
    {
        $report = $this->Laporan_kunjungan_model->get_report_by_id($id_report);

        if (empty($report)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan.']);
            return;
        }

        // Format header info
        $header = $report['header'];
        $visit_date = !empty($header['visit_date']) ? date('d-m-Y', strtotime($header['visit_date'])) : '-';
        $start_time = !empty($header['start_time']) ? (strlen($header['start_time']) > 10 ? date('d-m-Y H:i', strtotime($header['start_time'])) : $header['start_time']) : '-';
        $finish_time = !empty($header['finish_time']) ? (strlen($header['finish_time']) > 10 ? date('d-m-Y H:i', strtotime($header['finish_time'])) : $header['finish_time']) : '-';

        // Build action plans list from activities
        $action_plans = [];
        if (!empty($report['activities'])) {
            foreach ($report['activities'] as $activity) {
                if (!empty($activity['action_plans'])) {
                    foreach ($activity['action_plans'] as $plan) {
                        $action_plans[] = [
                            'visit_date'      => $visit_date,
                            'consultant_name' => htmlspecialchars($header['consultant_name'] ?? ''),
                            'activity_name'   => htmlspecialchars($activity['activity_name'] ?? ''),
                            'description'     => htmlspecialchars($plan['description'] ?? ''),
                            'pic'             => htmlspecialchars($plan['pic'] ?? ''),
                            'due_date'        => !empty($plan['due_date']) ? date('d-m-Y', strtotime($plan['due_date'])) : '-',
                            'status'          => $plan['status'] ?? 'progress',
                        ];
                    }
                } else {
                    // Activity without action plans - still show the activity
                    $action_plans[] = [
                        'visit_date'      => $visit_date,
                        'consultant_name' => htmlspecialchars($header['consultant_name'] ?? ''),
                        'activity_name'   => htmlspecialchars($activity['activity_name'] ?? ''),
                        'description'     => '-',
                        'pic'             => '-',
                        'due_date'        => '-',
                        'status'          => '-',
                    ];
                }
            }
        }

        // Build improvements list
        $improvements = [];
        if (!empty($report['improvements'])) {
            foreach ($report['improvements'] as $imp) {
                $improvements[] = [
                    'potensi_improvement' => htmlspecialchars($imp['potensi_improvement'] ?? ''),
                    'hasil_improvement'   => htmlspecialchars($imp['hasil_improvement'] ?? ''),
                    'status'              => $imp['status'] ?? 'progress',
                ];
            }
        }

        echo json_encode([
            'status' => 1,
            'data'   => [
                'visit_date'      => $visit_date,
                'consultant_name' => htmlspecialchars($header['consultant_name'] ?? ''),
                'start_time'      => $start_time,
                'finish_time'     => $finish_time,
                'status'          => ucfirst($header['status']),
                'action_plans'    => $action_plans,
                'improvements'    => $improvements,
            ]
        ]);
    }

    /**
     * Display report view for an SPK project.
     * Shows all action plans and improvements across all visits for this SPK.
     * Restricted by viewPermission.
     *
     * @param string $id_spk_penawaran _SLASH_-encoded SPK ID
     *
     * @return void
     */
    public function view($id_spk_penawaran)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        // Get SPK info
        $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($id_spk_decoded);

        if (empty($spk_info)) {
            $this->session->set_flashdata('message', 'Data SPK tidak ditemukan.');
            redirect('laporan_kunjungan');
        }

        // Get ALL action plans for this SPK (from all finalized visits)
        // Get ALL action plans for this SPK (from finalized visits only)
        $all_action_plans = $this->Laporan_kunjungan_model->get_previous_action_plans($id_spk_decoded);
        $all_improvements = $this->Laporan_kunjungan_model->get_previous_improvements($id_spk_decoded);

        // Get list of all visit reports for this SPK
        $this->db->select('id, report_id, visit_date, consultant_name, start_time, finish_time, status');
        $this->db->from('visit_report_headers');
        $this->db->where('id_spk_penawaran', $id_spk_decoded);
        $this->db->order_by('visit_date', 'DESC');
        $visit_list = $this->db->get()->result();

        $data = [
            'spk_info'          => $spk_info,
            'action_plans'      => $all_action_plans,
            'improvements'      => $all_improvements,
            'visit_list'        => $visit_list,
            'id_spk_penawaran'  => $id_spk_decoded,
        ];

        $this->template->set($data);
        $this->template->title('Laporan Kunjungan - Report');
        $this->template->render('view');
    }

    /**
     * AJAX POST: Record start or finish time for a visit report.
     *
     * POST params:
     * - type: 'start' or 'finish'
     * - start_time: existing start time (for finish validation)
     * - report_id: optional, for existing drafts
     *
     * Returns JSON: {status: 1, time: 'HH:MM'} or {status: 0, pesan: 'error message'}
     *
     * @return void Outputs JSON
     */
    public function record_time()
    {
        $type       = $this->input->post('type');
        $start_time = $this->input->post('start_time');
        $report_id  = $this->input->post('report_id');

        // Validate type parameter
        if (!in_array($type, ['start', 'finish'])) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Tipe waktu tidak valid.'
            ]);
            return;
        }

        $current_datetime = date('Y-m-d H:i:s');
        $display_datetime = date('d-m-Y H:i');

        if ($type === 'start') {
            // Check if start_time already recorded (no overwrite)
            if (!empty($start_time)) {
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Waktu mulai sudah tercatat, tidak dapat diubah.'
                ]);
                return;
            }

            echo json_encode([
                'status'  => 1,
                'time'    => $current_datetime,
                'display' => $display_datetime
            ]);
            return;
        }

        if ($type === 'finish') {
            // Finish requires start time to exist
            if (empty($start_time)) {
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Waktu mulai harus dicatat terlebih dahulu.'
                ]);
                return;
            }

            // Check if finish_time already recorded (no overwrite)
            $finish_time = $this->input->post('finish_time');
            if (!empty($finish_time)) {
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Waktu selesai sudah tercatat, tidak dapat diubah.'
                ]);
                return;
            }

            echo json_encode([
                'status'  => 1,
                'time'    => $current_datetime,
                'display' => $display_datetime
            ]);
            return;
        }
    }

    /**
     * POST handler: Save a new visit report (draft or finalized).
     *
     * Expects POST data:
     * - id_spk_penawaran, company_name, project_name, visit_date
     * - start_time, finish_time, consultant_id, consultant_name
     * - save_type: 'draft' or 'final'
     * - activities[]: array of {activity_source, spk_activity_id, activity_name}
     * - action_plans[]: array of {activity_index, description, pic, due_date, status}
     * - improvements[]: array of {potensi_improvement, hasil_improvement, status}
     *
     * On finalize: validates required fields.
     * On draft: saves without full validation.
     * Uses database transactions for atomic writes.
     *
     * @return void Outputs JSON
     */
    public function save()
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->addPermission);
        }

        // Read POST data
        $id_spk_penawaran = $this->input->post('id_spk_penawaran');
        $company_name     = $this->input->post('company_name');
        $project_name     = $this->input->post('project_name');
        $visit_date       = $this->input->post('visit_date');
        $start_time       = $this->input->post('start_time');
        $finish_time      = $this->input->post('finish_time');
        $consultant_id    = $this->input->post('consultant_id');
        $consultant_name  = $this->input->post('consultant_name');
        $save_type        = $this->input->post('save_type');
        $activities       = $this->input->post('activities');
        $action_plans     = $this->input->post('action_plans');
        $improvements     = $this->input->post('improvements');

        // Normalize arrays
        if (empty($activities) || !is_array($activities)) {
            $activities = [];
        }
        if (empty($action_plans) || !is_array($action_plans)) {
            $action_plans = [];
        }
        if (empty($improvements) || !is_array($improvements)) {
            $improvements = [];
        }

        // Determine status
        $status = ($save_type === 'final') ? 'final' : 'draft';

        // Validation for finalize
        if ($status === 'final') {
            $errors = [];

            if (empty($start_time)) {
                $errors['start_time'] = 'Waktu mulai harus diisi.';
            }
            if (empty($finish_time)) {
                $errors['finish_time'] = 'Waktu selesai harus diisi.';
            }
            if (empty($activities)) {
                $errors['activities'] = 'Minimal satu kegiatan harus dipilih.';
            }

            // Validate each action plan has description, pic, due_date
            if (!empty($action_plans)) {
                foreach ($action_plans as $idx => $plan) {
                    if (empty($plan['description'])) {
                        $errors['action_plans[' . $idx . '][description]'] = 'Deskripsi action plan harus diisi.';
                    }
                    if (empty($plan['pic'])) {
                        $errors['action_plans[' . $idx . '][pic]'] = 'PIC action plan harus diisi.';
                    }
                    if (empty($plan['due_date'])) {
                        $errors['action_plans[' . $idx . '][due_date]'] = 'Due date action plan harus diisi.';
                    }
                }
            }

            // Validate improvements: if added, both fields must be filled
            if (!empty($improvements)) {
                foreach ($improvements as $idx => $imp) {
                    if (empty($imp['potensi_improvement'])) {
                        $errors['improvements[' . $idx . '][potensi_improvement]'] = 'Potensi improvement harus diisi.';
                    }
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Validasi gagal. Periksa kembali data yang diisi.',
                    'errors' => $errors
                ]);
                return;
            }
        }

        // Begin transaction
        $this->db->trans_begin();

        try {
            // Generate report ID
            $report_id = $this->Laporan_kunjungan_model->generate_report_id();

            if ($report_id === false) {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Gagal generate ID laporan. Silakan coba lagi.'
                ]);
                return;
            }

            $now = date('Y-m-d H:i:s');

            // Insert header
            $header_data = [
                'report_id'         => $report_id,
                'id_spk_penawaran'  => $id_spk_penawaran,
                'company_name'      => $company_name,
                'project_name'      => $project_name,
                'visit_date'        => $visit_date,
                'start_time'        => !empty($start_time) ? $start_time : null,
                'finish_time'       => !empty($finish_time) ? $finish_time : null,
                'consultant_id'     => $consultant_id,
                'consultant_name'   => $consultant_name,
                'status'            => $status,
                'created_at'        => $now,
                'updated_at'        => null,
                'created_by'        => $this->auth->user_id(),
                'updated_by'        => null,
            ];

            $header_id = $this->Laporan_kunjungan_model->save_report_header($header_data);

            if ($header_id === false) {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Gagal menyimpan header laporan.'
                ]);
                return;
            }

            // Insert activities and their action plans
            if (!empty($activities)) {
                // Prepare activities for batch insert
                $activity_data = [];
                foreach ($activities as $index => $act) {
                    $activity_data[] = [
                        'activity_source'  => isset($act['activity_source']) ? $act['activity_source'] : 'custom',
                        'spk_activity_id'  => isset($act['spk_activity_id']) ? $act['spk_activity_id'] : null,
                        'activity_name'    => $act['activity_name'],
                        'sort_order'       => $index + 1,
                    ];
                }

                $this->Laporan_kunjungan_model->save_report_activities($header_id, $activity_data);

                // Get inserted activity IDs for linking action plans
                $this->db->select('id, sort_order');
                $this->db->from('visit_report_activities');
                $this->db->where('report_id', $header_id);
                $this->db->order_by('sort_order', 'ASC');
                $inserted_activities = $this->db->get()->result();

                // Map activity_index to activity ID
                $activity_id_map = [];
                foreach ($inserted_activities as $ins_act) {
                    $activity_id_map[$ins_act->sort_order - 1] = $ins_act->id;
                }

                // Insert action plans linked to their activities
                if (!empty($action_plans)) {
                    // Build a map from group_key to activity ID
                    $group_key_to_id = [];
                    foreach ($activities as $index => $act) {
                        $group_key = isset($act['group_key']) ? $act['group_key'] : '';
                        if (isset($activity_id_map[$index])) {
                            $group_key_to_id[$group_key] = $activity_id_map[$index];
                        }
                    }

                    // Also build fallback map by activity_index
                    $plans_by_activity_id = [];
                    foreach ($action_plans as $plan) {
                        $group_key = isset($plan['group_key']) ? $plan['group_key'] : '';
                        $activity_index = isset($plan['activity_index']) ? (int) $plan['activity_index'] : 0;

                        // Try to match by group_key first, then by activity_index
                        $target_activity_id = null;
                        if (!empty($group_key) && isset($group_key_to_id[$group_key])) {
                            $target_activity_id = $group_key_to_id[$group_key];
                        } elseif (isset($activity_id_map[$activity_index])) {
                            $target_activity_id = $activity_id_map[$activity_index];
                        }

                        if ($target_activity_id) {
                            $plans_by_activity_id[$target_activity_id][] = $plan;
                        }
                    }

                    foreach ($plans_by_activity_id as $act_id => $plans) {
                        $plan_data = [];
                        foreach ($plans as $plan) {
                            $plan_data[] = [
                                'report_id'   => $header_id,
                                'description' => $plan['description'],
                                'pic'         => $plan['pic'],
                                'due_date'    => $plan['due_date'],
                                'status'      => isset($plan['status']) ? $plan['status'] : 'progress',
                            ];
                        }
                        $this->Laporan_kunjungan_model->save_action_plans($act_id, $plan_data);
                    }
                }
            }

            // Insert improvements
            if (!empty($improvements)) {
                $this->Laporan_kunjungan_model->save_improvements($header_id, $improvements);
            }

            // Check transaction status
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'
                ]);
                return;
            }

            $this->db->trans_complete();

            echo json_encode([
                'status'    => 1,
                'pesan'     => 'Laporan kunjungan berhasil disimpan.',
                'report_id' => $report_id,
                'header_id' => $header_id
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * POST handler: Update an existing draft visit report.
     *
     * Expects POST data same as save() plus:
     * - id: report header ID (primary key)
     *
     * Only draft reports can be updated. Finalized reports are rejected.
     * Uses delete-and-reinsert strategy for detail records within a transaction.
     *
     * @return void Outputs JSON
     */
    public function update()
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->addPermission);
        }

        // Read POST data
        $id               = $this->input->post('id');
        $id_spk_penawaran = $this->input->post('id_spk_penawaran');
        $company_name     = $this->input->post('company_name');
        $project_name     = $this->input->post('project_name');
        $visit_date       = $this->input->post('visit_date');
        $start_time       = $this->input->post('start_time');
        $finish_time      = $this->input->post('finish_time');
        $consultant_id    = $this->input->post('consultant_id');
        $consultant_name  = $this->input->post('consultant_name');
        $save_type        = $this->input->post('save_type');
        $activities       = $this->input->post('activities');
        $action_plans     = $this->input->post('action_plans');
        $improvements     = $this->input->post('improvements');

        // Normalize arrays
        if (empty($activities) || !is_array($activities)) {
            $activities = [];
        }
        if (empty($action_plans) || !is_array($action_plans)) {
            $action_plans = [];
        }
        if (empty($improvements) || !is_array($improvements)) {
            $improvements = [];
        }

        // Validate report exists and is draft
        $existing_report = $this->Laporan_kunjungan_model->get_report_by_id($id);

        if (empty($existing_report)) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Laporan tidak ditemukan.'
            ]);
            return;
        }

        if ($existing_report['header']['status'] !== 'draft') {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Laporan yang sudah final tidak dapat diubah.'
            ]);
            return;
        }

        // Determine status
        $status = ($save_type === 'final') ? 'final' : 'draft';

        // Validation for finalize
        if ($status === 'final') {
            $errors = [];

            if (empty($start_time)) {
                $errors['start_time'] = 'Waktu mulai harus diisi.';
            }
            if (empty($finish_time)) {
                $errors['finish_time'] = 'Waktu selesai harus diisi.';
            }
            if (empty($activities)) {
                $errors['activities'] = 'Minimal satu kegiatan harus dipilih.';
            }

            // Validate each action plan has description, pic, due_date
            if (!empty($action_plans)) {
                foreach ($action_plans as $idx => $plan) {
                    if (empty($plan['description'])) {
                        $errors['action_plans[' . $idx . '][description]'] = 'Deskripsi action plan harus diisi.';
                    }
                    if (empty($plan['pic'])) {
                        $errors['action_plans[' . $idx . '][pic]'] = 'PIC action plan harus diisi.';
                    }
                    if (empty($plan['due_date'])) {
                        $errors['action_plans[' . $idx . '][due_date]'] = 'Due date action plan harus diisi.';
                    }
                }
            }

            // Validate improvements: if added, both fields must be filled
            if (!empty($improvements)) {
                foreach ($improvements as $idx => $imp) {
                    if (empty($imp['potensi_improvement'])) {
                        $errors['improvements[' . $idx . '][potensi_improvement]'] = 'Potensi improvement harus diisi.';
                    }
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Validasi gagal. Periksa kembali data yang diisi.',
                    'errors' => $errors
                ]);
                return;
            }
        }

        // Begin transaction
        $this->db->trans_begin();

        try {
            $now = date('Y-m-d H:i:s');

            // Delete existing detail records
            $this->Laporan_kunjungan_model->delete_report_details($id);

            // Update header
            $header_data = [
                'id_spk_penawaran'  => $id_spk_penawaran,
                'company_name'      => $company_name,
                'project_name'      => $project_name,
                'visit_date'        => $visit_date,
                'start_time'        => !empty($start_time) ? $start_time : null,
                'finish_time'       => !empty($finish_time) ? $finish_time : null,
                'consultant_id'     => $consultant_id,
                'consultant_name'   => $consultant_name,
                'status'            => $status,
                'updated_at'        => $now,
                'updated_by'        => $this->auth->user_id(),
            ];

            $this->Laporan_kunjungan_model->update_report_header($id, $header_data);

            // Re-insert activities and their action plans
            if (!empty($activities)) {
                $activity_data = [];
                foreach ($activities as $index => $act) {
                    $activity_data[] = [
                        'activity_source'  => isset($act['activity_source']) ? $act['activity_source'] : 'custom',
                        'spk_activity_id'  => isset($act['spk_activity_id']) ? $act['spk_activity_id'] : null,
                        'activity_name'    => $act['activity_name'],
                        'sort_order'       => $index + 1,
                    ];
                }

                $this->Laporan_kunjungan_model->save_report_activities($id, $activity_data);

                // Get inserted activity IDs for linking action plans
                $this->db->select('id, sort_order');
                $this->db->from('visit_report_activities');
                $this->db->where('report_id', $id);
                $this->db->order_by('sort_order', 'ASC');
                $inserted_activities = $this->db->get()->result();

                // Map activity_index to activity ID
                $activity_id_map = [];
                foreach ($inserted_activities as $ins_act) {
                    $activity_id_map[$ins_act->sort_order - 1] = $ins_act->id;
                }

                // Insert action plans linked to their activities
                if (!empty($action_plans)) {
                    // Build a map from group_key to activity ID
                    $group_key_to_id = [];
                    foreach ($activities as $index => $act) {
                        $group_key = isset($act['group_key']) ? $act['group_key'] : '';
                        if (isset($activity_id_map[$index])) {
                            $group_key_to_id[$group_key] = $activity_id_map[$index];
                        }
                    }

                    $plans_by_activity_id = [];
                    foreach ($action_plans as $plan) {
                        $group_key = isset($plan['group_key']) ? $plan['group_key'] : '';
                        $activity_index = isset($plan['activity_index']) ? (int) $plan['activity_index'] : 0;

                        $target_activity_id = null;
                        if (!empty($group_key) && isset($group_key_to_id[$group_key])) {
                            $target_activity_id = $group_key_to_id[$group_key];
                        } elseif (isset($activity_id_map[$activity_index])) {
                            $target_activity_id = $activity_id_map[$activity_index];
                        }

                        if ($target_activity_id) {
                            $plans_by_activity_id[$target_activity_id][] = $plan;
                        }
                    }

                    foreach ($plans_by_activity_id as $act_id => $plans) {
                        $plan_data = [];
                        foreach ($plans as $plan) {
                            $plan_data[] = [
                                'report_id'   => $id,
                                'description' => $plan['description'],
                                'pic'         => $plan['pic'],
                                'due_date'    => $plan['due_date'],
                                'status'      => isset($plan['status']) ? $plan['status'] : 'progress',
                            ];
                        }
                        $this->Laporan_kunjungan_model->save_action_plans($act_id, $plan_data);
                    }
                }
            }

            // Re-insert improvements
            if (!empty($improvements)) {
                $this->Laporan_kunjungan_model->save_improvements($id, $improvements);
            }

            // Check transaction status
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 0,
                    'pesan'  => 'Terjadi kesalahan saat memperbarui data. Silakan coba lagi.'
                ]);
                return;
            }

            $this->db->trans_complete();

            echo json_encode([
                'status'    => 1,
                'pesan'     => 'Laporan kunjungan berhasil diperbarui.',
                'report_id' => $existing_report['header']['report_id']
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX POST: Update a previous action plan status.
     *
     * POST params:
     * - id_plan: action plan ID
     * - status: new status value (only 'done' is accepted)
     *
     * Only allows transition from 'progress' to 'done'.
     * Rejects transition from 'done' to 'progress'.
     *
     * @return void Outputs JSON
     */
    public function update_action_plan_status()
    {
        $id_plan = $this->input->post('id_plan');
        $status  = $this->input->post('status');

        if (empty($id_plan)) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'ID action plan tidak valid.'
            ]);
            return;
        }

        $result = $this->Laporan_kunjungan_model->update_action_plan_status($id_plan, $status);

        if ($result) {
            echo json_encode([
                'status' => 1,
                'pesan'  => 'Status action plan berhasil diperbarui.'
            ]);
        } else {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Gagal memperbarui status. Status hanya dapat diubah dari progress ke done.'
            ]);
        }
    }

    /**
     * Generate and download PDF for a finalized visit report.
     * Restricted by viewPermission.
     *
     * Validates that the report exists and is finalized before generating.
     * Uses mPDF library to render HTML template to PDF.
     * Streams PDF to browser for download.
     *
     * @param int $id_report Report header ID
     *
     * @return void Outputs PDF or redirects with error
     */
    public function generate_pdf($id_report)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        // Get full report data
        $report = $this->Laporan_kunjungan_model->get_report_by_id($id_report);

        if (empty($report)) {
            $this->session->set_flashdata('message', 'Data laporan tidak ditemukan.');
            redirect('laporan_kunjungan');
            return;
        }

        // Only finalized reports can be downloaded as PDF
        if ($report['header']['status'] !== 'final') {
            $this->session->set_flashdata('message', 'Hanya laporan final yang dapat di-download.');
            redirect('laporan_kunjungan');
            return;
        }

        // Load previous action plans and improvements for the same project
        $previous_action_plans = $this->Laporan_kunjungan_model->get_previous_action_plans(
            $report['header']['id_spk_penawaran']
        );
        $previous_improvements = $this->Laporan_kunjungan_model->get_previous_improvements(
            $report['header']['id_spk_penawaran']
        );

        // Prepare data for the PDF template
        $data = [
            'report'                => $report,
            'previous_action_plans' => $previous_action_plans,
            'previous_improvements' => $previous_improvements,
        ];

        try {
            // Render the PDF template view as HTML string
            $html = $this->load->view('pdf_template', $data, true);

            // Load mPDF library
            $this->load->library('Mpdf');

            // Initialize mPDF with A4 portrait orientation
            $mpdf = new mPDF('', 'A4', 0, '', 15, 15, 16, 16, 9, 9, 'P');

            // Set PDF metadata
            $mpdf->SetTitle('Laporan Kunjungan - ' . $report['header']['report_id']);
            $mpdf->SetAuthor($report['header']['consultant_name']);
            $mpdf->SetSubject('Laporan Kunjungan ' . $report['header']['company_name'] . ' - ' . $report['header']['project_name']);

            // Write HTML content to PDF
            $mpdf->WriteHTML($html);

            // Generate filename
            $filename = 'Laporan_Kunjungan_' . $report['header']['report_id'] . '.pdf';

            // Output PDF to browser for download
            $mpdf->Output($filename, 'D');

        } catch (Exception $e) {
            // Handle mPDF errors gracefully
            log_message('error', 'PDF Generation Error: ' . $e->getMessage());
            $this->session->set_flashdata('message', 'Gagal membuat PDF. Silakan coba lagi.');
            redirect('laporan_kunjungan');
            return;
        }
    }

    /**
     * POST: Send finalized visit report PDF via email.
     *
     * POST params:
     * - email: recipient email address
     *
     * Validates:
     * - Report exists and is finalized
     * - Email format is valid (server-side with filter_var FILTER_VALIDATE_EMAIL)
     *
     * Generates PDF, saves to temp file, attaches and sends via CI email library.
     * Cleans up temp file after sending (whether success or failure).
     *
     * Returns JSON:
     * - On success: {status: 1, pesan: 'Email berhasil dikirim.'}
     * - On failure: {status: 0, pesan: 'Error message'}
     *
     * @param int $id_report Report header ID
     *
     * @return void Outputs JSON
     */
    public function send_email($id_report)
    {
        // Read email from POST
        $email = trim($this->input->post('email'));

        // Validate email format server-side
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Format email tidak valid.'
            ]);
            return;
        }

        // Get report data
        $report = $this->Laporan_kunjungan_model->get_report_by_id($id_report);

        if (empty($report)) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Laporan tidak ditemukan.'
            ]);
            return;
        }

        // Validate report is finalized
        if ($report['header']['status'] !== 'final') {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Hanya laporan yang sudah final yang dapat dikirim via email.'
            ]);
            return;
        }

        // Get previous action plans and improvements for PDF content
        $previous_action_plans = $this->Laporan_kunjungan_model->get_previous_action_plans(
            $report['header']['id_spk_penawaran']
        );
        $previous_improvements = $this->Laporan_kunjungan_model->get_previous_improvements(
            $report['header']['id_spk_penawaran']
        );

        // Generate PDF content as string
        $temp_file = null;

        try {
            // Prepare data for PDF template
            $data = [
                'report'                => $report,
                'previous_action_plans' => $previous_action_plans,
                'previous_improvements' => $previous_improvements,
            ];

            // Render PDF template to HTML string
            $html = $this->load->view('pdf_template', $data, true);

            // Load mPDF library
            $this->load->library('Mpdf');

            // Initialize mPDF with A4 portrait orientation
            $mpdf = new mPDF('', 'A4', 0, '', 15, 15, 16, 16, 9, 9, 'P');

            // Set PDF metadata
            $mpdf->SetTitle('Laporan Kunjungan - ' . $report['header']['report_id']);
            $mpdf->SetAuthor($report['header']['consultant_name']);
            $mpdf->SetSubject('Laporan Kunjungan ' . $report['header']['company_name'] . ' - ' . $report['header']['project_name']);

            // Write HTML content to PDF
            $mpdf->WriteHTML($html);

            // Output PDF as string
            $pdf_content = $mpdf->Output('', 'S');

        } catch (Exception $e) {
            log_message('error', 'PDF generation for email failed: ' . $e->getMessage());
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Gagal membuat PDF. Silakan coba lagi.'
            ]);
            return;
        }

        // Save PDF to temp file in application cache directory
        $temp_dir = APPPATH . 'cache' . DIRECTORY_SEPARATOR;
        $visit_date = date('Ymd', strtotime($report['header']['visit_date']));
        $filename = 'Laporan_Kunjungan_' . $report['header']['report_id'] . '_' . $visit_date . '.pdf';
        $temp_file = $temp_dir . $filename;

        if (file_put_contents($temp_file, $pdf_content) === false) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Gagal menyimpan file PDF sementara.'
            ]);
            return;
        }

        // Load CI email library
        $this->load->library('email');

        // Configure email
        $this->email->clear(true);
        $this->email->from('noreply@consultant-app.com', 'Consultant App');
        $this->email->to($email);

        // Email subject: "Laporan Kunjungan - {project_name} ({visit_date})"
        $formatted_date = date('d-m-Y', strtotime($report['header']['visit_date']));
        $subject = 'Laporan Kunjungan - ' . $report['header']['project_name'] . ' (' . $formatted_date . ')';
        $this->email->subject($subject);

        // Email body: brief message with report summary
        $body  = 'Yth. Bapak/Ibu,' . "\n\n";
        $body .= 'Berikut terlampir laporan kunjungan dengan detail sebagai berikut:' . "\n\n";
        $body .= 'Perusahaan : ' . $report['header']['company_name'] . "\n";
        $body .= 'Project    : ' . $report['header']['project_name'] . "\n";
        $body .= 'Tanggal    : ' . $formatted_date . "\n";
        $body .= 'Konsultan  : ' . $report['header']['consultant_name'] . "\n";
        $body .= 'Waktu      : ' . date('H:i', strtotime($report['header']['start_time'])) . ' - ' . date('H:i', strtotime($report['header']['finish_time'])) . "\n\n";
        $body .= 'Silakan buka lampiran PDF untuk detail lengkap laporan kunjungan.' . "\n\n";
        $body .= 'Terima kasih.' . "\n";
        $body .= 'Consultant App';

        $this->email->message($body);

        // Attach the PDF file
        $this->email->attach($temp_file, 'attachment', $filename, 'application/pdf');

        // Send email
        $send_result = $this->email->send();

        // Clean up temp file (whether success or failure)
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        // Return response
        if ($send_result) {
            echo json_encode([
                'status' => 1,
                'pesan'  => 'Email berhasil dikirim.'
            ]);
        } else {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Gagal mengirim email. Silakan download PDF secara manual.'
            ]);
        }
    }

    /**
     * AJAX POST: Delete a draft visit report.
     * Only draft reports can be deleted.
     *
     * @param int $id_report Report header ID
     *
     * @return void Outputs JSON
     */
    public function delete($id_report)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->deletePermission);
        }

        // Get report
        $report = $this->Laporan_kunjungan_model->get_report_by_id($id_report);

        if (empty($report)) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Laporan tidak ditemukan.'
            ]);
            return;
        }

        // Only draft reports can be deleted
        if ($report['header']['status'] !== 'draft') {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Hanya laporan draft yang dapat dihapus.'
            ]);
            return;
        }

        // Begin transaction
        $this->db->trans_begin();

        // Delete details first (action plans, activities, improvements)
        $this->Laporan_kunjungan_model->delete_report_details($id_report);

        // Delete header
        $this->db->where('id', $id_report);
        $this->db->delete('visit_report_headers');

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Gagal menghapus laporan.'
            ]);
            return;
        }

        $this->db->trans_complete();

        echo json_encode([
            'status' => 1,
            'pesan'  => 'Laporan berhasil dihapus.'
        ]);
    }

    /**
     * AJAX POST: Toggle action plan status (progress <-> done).
     * Allows bidirectional status change.
     *
     * @return void Outputs JSON
     */
    public function toggle_action_plan_status()
    {
        $id = $this->input->post('id');
        $new_status = $this->input->post('status');

        if (empty($id) || !in_array($new_status, ['progress', 'done'])) {
            echo json_encode(['status' => 0, 'pesan' => 'Parameter tidak valid.']);
            return;
        }

        $this->db->where('id', $id);
        $this->db->update('visit_report_action_plans', [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($this->db->affected_rows() >= 0) {
            echo json_encode(['status' => 1, 'pesan' => 'Status berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 0, 'pesan' => 'Gagal memperbarui status.']);
        }
    }

    /**
     * AJAX POST: Toggle improvement status (progress <-> done).
     * Allows bidirectional status change.
     *
     * @return void Outputs JSON
     */
    public function toggle_improvement_status()
    {
        $id = $this->input->post('id');
        $new_status = $this->input->post('status');

        if (empty($id) || !in_array($new_status, ['progress', 'done'])) {
            echo json_encode(['status' => 0, 'pesan' => 'Parameter tidak valid.']);
            return;
        }

        $this->db->where('id', $id);
        $this->db->update('visit_report_improvements', [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($this->db->affected_rows() >= 0) {
            echo json_encode(['status' => 1, 'pesan' => 'Status berhasil diperbarui.']);
        } else {
            echo json_encode(['status' => 0, 'pesan' => 'Gagal memperbarui status.']);
        }
    }

    /**
     * AJAX POST: Update Hasil Improvement text.
     *
     * @return void Outputs JSON
     */
    public function update_improvement_hasil()
    {
        $id = $this->input->post('id');
        $hasil = $this->input->post('hasil_improvement');

        if (empty($id)) {
            echo json_encode(['status' => 0, 'pesan' => 'ID tidak valid.']);
            return;
        }

        $this->db->where('id', $id);
        $this->db->update('visit_report_improvements', [
            'hasil_improvement' => $hasil !== null ? $hasil : '',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($this->db->affected_rows() >= 0) {
            echo json_encode(['status' => 1, 'pesan' => 'Hasil improvement berhasil disimpan.']);
        } else {
            echo json_encode(['status' => 0, 'pesan' => 'Gagal menyimpan.']);
        }
    }

    /**
     * Generate printable HTML report for an SPK project (all finalized visits).
     * Opens in new window for print/save as PDF.
     *
     * @param string $id_spk_penawaran _SLASH_-encoded SPK ID
     *
     * @return void Outputs HTML
     */
    public function generate_pdf_spk($id_spk_penawaran)
    {
        if (!$this->auth->is_admin()) {
            $this->auth->restrict($this->viewPermission);
        }

        // Decode _SLASH_ encoding back to slashes
        $id_spk_decoded = str_replace('_SLASH_', '/', $id_spk_penawaran);

        // Get SPK info
        $spk_info = $this->Laporan_kunjungan_model->get_spk_detail($id_spk_decoded);

        if (empty($spk_info)) {
            $this->session->set_flashdata('message', 'Data SPK tidak ditemukan.');
            redirect('laporan_kunjungan');
            return;
        }

        // Get all finalized action plans and improvements
        $action_plans = $this->Laporan_kunjungan_model->get_previous_action_plans($id_spk_decoded);
        $improvements = $this->Laporan_kunjungan_model->get_previous_improvements($id_spk_decoded);

        // Prepare data for print template
        $data = [
            'spk_info'     => $spk_info,
            'action_plans' => $action_plans,
            'improvements' => $improvements,
        ];

        // Load print-friendly template (standalone HTML, no admin template)
        $this->load->view('pdf_template_spk', $data);
    }
}
