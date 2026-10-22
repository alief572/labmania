<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is controller for Pengajuan Rutin
 */

$status = array();
class Approval_kasbon_project extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Approval_Kasbon_Project.View';
    protected $addPermission      = 'Approval_Kasbon_Project.Add';
    protected $managePermission = 'Approval_Kasbon_Project.Manage';
    protected $deletePermission = 'Approval_Kasbon_Project.Delete';

    protected $otherdb;

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Approval Kasbon Project');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Approval_kasbon_project/Approval_kasbon_project_model'));
        date_default_timezone_set('Asia/Bangkok');

        $this->otherdb = $this->load->database('sendigs_finance', TRUE);
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval Pengajuan');
        $this->template->render('index');
    }

    private function _get_spk_team_data($id_spk_budgeting)
    {
        $spk_b = $this->db->get_where('kons_tr_spk_budgeting', ['id_spk_budgeting' => $id_spk_budgeting])->row();

        $spk_p = null;
        if (!empty($spk_b) && !empty($spk_b->id_spk_penawaran)) {
            $spk_p = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $spk_b->id_spk_penawaran])->row();
        }

        $team_employee_ids = [];
        $team_names = [];
        $id_spk_penawaran = !empty($spk_b->id_spk_penawaran) ? $spk_b->id_spk_penawaran : (!empty($spk_p->id_spk_penawaran) ? $spk_p->id_spk_penawaran : '');

        if (!empty($spk_b)) {
            if (!empty($spk_b->id_project_leader)) $team_employee_ids[] = trim((string)$spk_b->id_project_leader);
            if (!empty($spk_b->id_konsultan_1)) $team_employee_ids[] = trim((string)$spk_b->id_konsultan_1);
            if (!empty($spk_b->id_konsultan_2)) $team_employee_ids[] = trim((string)$spk_b->id_konsultan_2);

            if (!empty($spk_b->nm_project_leader)) $team_names[] = strtolower(trim($spk_b->nm_project_leader));
            if (!empty($spk_b->nm_konsultan_1)) $team_names[] = strtolower(trim($spk_b->nm_konsultan_1));
            if (!empty($spk_b->nm_konsultan_2)) $team_names[] = strtolower(trim($spk_b->nm_konsultan_2));
        }

        if (!empty($spk_p)) {
            if (!empty($spk_p->id_sales)) $team_employee_ids[] = trim((string)$spk_p->id_sales);
            if (!empty($spk_p->id_project_leader)) $team_employee_ids[] = trim((string)$spk_p->id_project_leader);
            if (!empty($spk_p->id_konsultan_1)) $team_employee_ids[] = trim((string)$spk_p->id_konsultan_1);
            if (!empty($spk_p->id_konsultan_2)) $team_employee_ids[] = trim((string)$spk_p->id_konsultan_2);

            if (!empty($spk_p->nm_sales)) $team_names[] = strtolower(trim($spk_p->nm_sales));
            if (!empty($spk_p->nm_project_leader)) $team_names[] = strtolower(trim($spk_p->nm_project_leader));
            if (!empty($spk_p->nm_konsultan_1)) $team_names[] = strtolower(trim($spk_p->nm_konsultan_1));
            if (!empty($spk_p->nm_konsultan_2)) $team_names[] = strtolower(trim($spk_p->nm_konsultan_2));
        }

        $team_employee_ids = array_values(array_unique(array_filter($team_employee_ids)));
        $team_names = array_values(array_unique(array_filter($team_names)));

        return [
            'team_employee_ids' => $team_employee_ids,
            'team_names' => $team_names,
            'id_spk_penawaran' => $id_spk_penawaran,
            'budgeting' => $spk_b,
            'penawaran' => $spk_p
        ];
    }

    public function _get_user_origin_spk($employee_id, $nm_lengkap, $current_id_spk_penawaran = '')
    {
        $emp_id = trim((string)$employee_id);
        $nm = strtolower(trim($nm_lengkap));

        // If employee_id is empty, try to resolve it.
        // 1) From users table by nm_lengkap/username (account name may differ from employee name).
        if (empty($emp_id) && !empty($nm)) {
            $user_row = $this->db->select('employee_id')
                ->from('users')
                ->where('TRIM(employee_id) !=', '')
                ->where('employee_id IS NOT NULL', null, false)
                ->group_start()
                ->where('LOWER(TRIM(nm_lengkap))', $nm)
                ->or_where('LOWER(TRIM(username))', $nm)
                ->group_end()
                ->get()->row();
            if (!empty($user_row) && !empty($user_row->employee_id)) {
                $emp_id = trim((string)$user_row->employee_id);
            }
        }

        // 2) Fallback: resolve from employees_internal by name.
        if (empty($emp_id) && !empty($nm)) {
            $emp_row = $this->db->select('id')
                ->from('employees_internal')
                ->group_start()
                ->where('LOWER(name)', $nm)
                ->or_like('LOWER(name)', $nm)
                ->group_end()
                ->get()->row();
            if (!empty($emp_row) && !empty($emp_row->id)) {
                $emp_id = trim((string)$emp_row->id);
            }
        }

        // 1. Search in kons_tr_spk_penawaran
        $has_condition = false;
        $this->db->select('id_spk_penawaran')
            ->from('kons_tr_spk_penawaran')
            ->where('deleted_by IS NULL')
            ->group_start();

        if (!empty($emp_id)) {
            $this->db->where('id_sales', $emp_id)
                ->or_where('id_konsultan_1', $emp_id)
                ->or_where('id_konsultan_2', $emp_id)
                ->or_where('id_project_leader', $emp_id);
            $has_condition = true;
        }
        if (!empty($nm)) {
            $this->db->or_where('LOWER(TRIM(nm_sales))', $nm)
                ->or_like('LOWER(nm_sales)', $nm)
                ->or_where('LOWER(TRIM(nm_konsultan_1))', $nm)
                ->or_like('LOWER(nm_konsultan_1)', $nm)
                ->or_where('LOWER(TRIM(nm_konsultan_2))', $nm)
                ->or_like('LOWER(nm_konsultan_2)', $nm)
                ->or_where('LOWER(TRIM(nm_project_leader))', $nm)
                ->or_like('LOWER(nm_project_leader)', $nm);
            $has_condition = true;
        }

        if ($has_condition) {
            $this->db->group_end();

            if (!empty($current_id_spk_penawaran)) {
                $this->db->where('id_spk_penawaran !=', $current_id_spk_penawaran);
            }

            $this->db->order_by('id_spk_penawaran', 'DESC');
            $spk = $this->db->get()->row();

            if (!empty($spk) && !empty($spk->id_spk_penawaran)) {
                return $spk->id_spk_penawaran;
            }
        } else {
            $this->db->group_end();
        }

        // 2. Fallback search in kons_tr_spk_budgeting
        $has_condition_b = false;
        $this->db->select('id_spk_penawaran')
            ->from('kons_tr_spk_budgeting')
            ->where('delete_by IS NULL')
            ->group_start();

        if (!empty($emp_id)) {
            $this->db->where('id_project_leader', $emp_id)
                ->or_where('id_konsultan_1', $emp_id)
                ->or_where('id_konsultan_2', $emp_id);
            $has_condition_b = true;
        }
        if (!empty($nm)) {
            $this->db->or_where('LOWER(TRIM(nm_project_leader))', $nm)
                ->or_like('LOWER(nm_project_leader)', $nm)
                ->or_where('LOWER(TRIM(nm_konsultan_1))', $nm)
                ->or_like('LOWER(nm_konsultan_1)', $nm)
                ->or_where('LOWER(TRIM(nm_konsultan_2))', $nm)
                ->or_like('LOWER(nm_konsultan_2)', $nm);
            $has_condition_b = true;
        }

        if ($has_condition_b) {
            $this->db->group_end();

            if (!empty($current_id_spk_penawaran)) {
                $this->db->where('id_spk_penawaran !=', $current_id_spk_penawaran);
            }

            $this->db->order_by('id_spk_penawaran', 'DESC');
            $spk_b = $this->db->get()->row();

            if (!empty($spk_b) && !empty($spk_b->id_spk_penawaran)) {
                return $spk_b->id_spk_penawaran;
            }
        } else {
            $this->db->group_end();
        }

        return null;
    }

    private function _format_request_by_cell($nm_pembuat, $employee_id, $spk_team_info)
    {
        $words = explode(' ', trim($nm_pembuat));
        $initials = '';
        if (count($words) >= 2) {
            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else if (count($words) == 1 && strlen($words[0]) > 0) {
            $initials = strtoupper(substr($words[0], 0, 2));
        } else {
            $initials = '??';
        }

        $is_in_team = false;
        $emp_id = !empty($employee_id) ? trim((string)$employee_id) : '';
        if (!empty($emp_id) && in_array($emp_id, $spk_team_info['team_employee_ids'])) {
            $is_in_team = true;
        } else if (empty($emp_id)) {
            // Fallback nama jika user belum dihubungkan ke employee_id
            $pembuat_name = strtolower(trim($nm_pembuat));
            if (in_array($pembuat_name, $spk_team_info['team_names'])) {
                $is_in_team = true;
            }
        }

        $outside_badge = '';
        if (!$is_in_team) {
            $current_spk = !empty($spk_team_info['id_spk_penawaran']) ? $spk_team_info['id_spk_penawaran'] : '';
            $origin_spk = $this->_get_user_origin_spk($emp_id, $nm_pembuat, $current_spk);

            $tim_asal_text = '';
            if (!empty($origin_spk)) {
                $tim_asal_text = ' (tim asal: ' . htmlspecialchars($origin_spk) . ')';
            }

            $outside_badge = '<div style="margin-top: 4px;"><span class="tag-outside" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#c76b00; background:#fff2df; border:1px solid #f0d3a0; padding:1px 8px; border-radius:10px;"><i class="fa fa-exclamation-triangle"></i> Bukan tim SPK ini' . $tim_asal_text . '</span></div>';
        }

        $avatar_html = '<div class="avatar" style="width:26px; height:26px; border-radius:50%; background:#e3e6ea; color:#5c6470; font-size:11px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; margin-right:8px; flex-shrink:0;">' . $initials . '</div>';

        return '<div class="d-flex align-items-center" style="display:flex; align-items:flex-start;">'
            . $avatar_html
            . '<div>'
            . '<div>' . htmlspecialchars($nm_pembuat) . '</div>'
            . $outside_badge
            . '</div>'
            . '</div>';
    }

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.id, a.id_spk_budgeting, a.tgl, a.sts, a.created_by, b.id_spk_penawaran, b.nm_customer, b.nm_project_leader, b.nm_project, c.nm_sales, d.nm_paket, IF(u.nm_lengkap IS NOT NULL, u.nm_lengkap, a.created_by) as nm_pembuat, u.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_spk_budgeting b', 'b.id_spk_budgeting = a.id_spk_budgeting', 'left');
        $this->db->join('kons_tr_spk_penawaran c', 'c.id_spk_penawaran = b.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = c.id_project', 'left');
        $this->db->join('users u', 'u.id_user = a.created_by', 'left');
        $this->db->where('a.deleted_at IS NULL');
        $this->db->where('a.sts_reject IS NULL');
        $this->db->group_start();
        $this->db->where('a.sts', '');
        $this->db->or_where('a.sts', null);
        $this->db->group_end();
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_budgeting', $search['value'], 'both');
            $this->db->or_like('a.id', $search['value'], 'both');
            $this->db->or_like('b.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('b.nm_customer', $search['value'], 'both');
            $this->db->or_like('c.nm_sales', $search['value'], 'both');
            $this->db->or_like('b.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('b.nm_project', $search['value'], 'both');
            $this->db->or_like('u.nm_lengkap', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id');
        $this->db->order_by('a.created_date', 'desc');

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $hasil = [];

        $no = 1;
        foreach ($get_data->result() as $item) {

            $status = '<div class="badge bg-blue">Waiting Approval</div>';
            if ($item->sts == 2) {
                $status = '<div class="badge bg-red">Rejected</div>';
            }

            $option = '<a href="' . base_url('approval_kasbon_project/approval_kasbon/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-primary" title="Approval Kasbon"><i class="fa fa-arrow-up"></i></a>';

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_project_header a');
            $this->db->where('a.id', $item->id);
            $get_header_kasbon = $this->db->get()->row();

            $keterangan = (!empty($get_header_kasbon)) ? $get_header_kasbon->deskripsi : '';
            $tipe = '';
            $tipe_pengajuan = '';
            if (!empty($get_header_kasbon)) {
                if ($get_header_kasbon->tipe == '1') {
                    $tipe = '<div class="badge bg-blue">Subcont</div>';
                }
                if ($get_header_kasbon->tipe == '2') {
                    $tipe = '<div class="badge bg-yellow">Akomodasi</div>';
                }
                if ($get_header_kasbon->tipe == '3') {
                    $tipe = '<div class="badge bg-gray">Others</div>';
                }
                if ($get_header_kasbon->tipe == '4') {
                    $tipe = '<div class="badge bg-green">Lab</div>';
                }
                if ($get_header_kasbon->tipe == '5') {
                    $tipe = '<div class="badge bg-red">Subcont Tenaga Ahli</div>';
                }
                if ($get_header_kasbon->tipe == '6') {
                    $tipe = '<div class="badge bg-purple">Subcont Perusahaan</div>';
                }

                if ($get_header_kasbon->metode_pembayaran == '1') {
                    $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
                }
                if ($get_header_kasbon->metode_pembayaran == '2') {
                    $tipe_pengajuan = '<div class="badge bg-yellow">Direct Payment</div>';
                }
                if ($get_header_kasbon->metode_pembayaran == '3') {
                    $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
                }
            }
            $nominal = (!empty($get_header_kasbon)) ? $get_header_kasbon->grand_total : 0;

            // ----- Compact grouped columns -----
            // Nomor SPK & Paket (SPK number + package name stacked)
            $spk_paket = '<span style="font-weight: 700; color: #333;">' . $item->id_spk_penawaran . '</span>';
            if (!empty($item->nm_paket)) {
                $spk_paket .= '<br><span class="text-muted" style="font-size: 12px;"><i class="fa fa-briefcase"></i> ' . $item->nm_paket . '</span>';
            }

            // Team / PIC (Project Leader + Sales stacked)
            $pic_team = '<div><i class="fa fa-user-circle text-primary"></i> <b>PL:</b> ' . ucfirst($item->nm_project_leader) . '</div>';
            if (!empty($item->nm_sales)) {
                $pic_team .= '<div class="text-muted" style="font-size: 12px;"><i class="fa fa-user text-muted"></i> <b>Sales:</b> ' . ucfirst($item->nm_sales) . '</div>';
            }

            // Kasbon Info (Nomor Kasbon + Tipe + Tipe Pembayaran + Nominal + Keterangan stacked)
            $keterangan_short = $keterangan;
            if (!empty($keterangan) && mb_strlen($keterangan) > 40) {
                $keterangan_short = '<span title="' . htmlspecialchars($keterangan, ENT_QUOTES) . '" style="cursor:help;">' . htmlspecialchars(mb_substr($keterangan, 0, 40), ENT_QUOTES) . '…</span>';
            } else {
                $keterangan_short = htmlspecialchars($keterangan, ENT_QUOTES);
            }

            $kasbon_info = '<div><i class="fa fa-hashtag text-muted"></i> <b>' . $item->id . '</b></div>';
            $kasbon_info .= '<div style="margin-top: 3px;">' . $tipe . ' ' . $tipe_pengajuan . '</div>';
            $kasbon_info .= '<div style="margin-top: 3px;"><i class="fa fa-money text-muted"></i> <b>Rp ' . number_format($nominal) . '</b></div>';
            if (!empty($keterangan)) {
                $kasbon_info .= '<div class="text-muted" style="font-size: 12px; margin-top: 3px;"><i class="fa fa-comment-o"></i> ' . $keterangan_short . '</div>';
            }

            $spk_team_info = $this->_get_spk_team_data($item->id_spk_budgeting);
            $request_by_cell = $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info);
            $date_cell = !empty($item->tgl) ? date('d F Y', strtotime($item->tgl)) : '-';

            $hasil[] = [
                'no' => $no,
                'spk_paket' => $spk_paket,
                'nm_customer' => $item->nm_customer,
                'pic_team' => $pic_team,
                'kasbon_info' => $kasbon_info,
                'request_by' => $request_by_cell,
                'date' => $date_cell,
                'status' => $status,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_all,
            'data' => $hasil
        ]);
    }

    public function approval_kasbon($id_kasbon)
    {
        $id_kasbon = urldecode($id_kasbon);
        $id_kasbon = str_replace('|', '/', $id_kasbon);

        $get_header = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $id_kasbon, 'deleted_at' => NULL])->row();

        if (empty($get_header)) {
            $this->session->set_flashdata('alert_data', '<div class="alert alert-warning" id="flash-message">Data tidak ditemukan</div>');
            redirect(site_url('approval_kasbon_project'));
            return;
        }

        $id_spk_budgeting = $get_header->id_spk_budgeting;

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $this->db->where('a.custom_subcont', '0');
        $get_kasbon_subcont = $this->db->get()->result();

        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $this->db->where('a.custom_subcont', '1');
        $get_kasbon_subcont_custom = $this->db->get()->result();

        // ----------------------------------------------------
        // Tipe 2: AKOMODASI
        // ----------------------------------------------------
        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $get_kasbon_akomodasi_submitted = $this->db->get()->result();

        $submitted_akomodasi = [];
        foreach ($get_kasbon_akomodasi_submitted as $item) {
            // Custom items (id_akomodasi 0/NULL) tidak mereferensi detail budgeting,
            // simpan terpisah agar tidak saling menimpa di map.
            if (empty($item->id_akomodasi)) {
                continue;
            }
            $submitted_akomodasi[(string) trim($item->id_akomodasi)] = $item;
        }

        $submitted_akomodasi_custom = [];
        foreach ($get_kasbon_akomodasi_submitted as $item) {
            if (empty($item->id_akomodasi)) {
                $submitted_akomodasi_custom[] = $item;
            }
        }

        $this->db->select('a.id_akomodasi, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.id_header !=', $id_kasbon);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_akomodasi');
        $get_prior_akomodasi = $this->db->get()->result();

        $prior_akomodasi_map = [];
        foreach ($get_prior_akomodasi as $p) {
            $prior_akomodasi_map[$p->id_akomodasi] = $p;
        }

        $this->db->select('c.id_akomodasi, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->join('kons_tr_spk_budgeting_akomodasi c', 'c.id = b.id_detail');
        $this->db->where('a.tipe', 2);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('c.id_akomodasi');
        $get_ovb_akomodasi_agg = $this->db->get()->result();

        $ovb_akomodasi_map = [];
        foreach ($get_ovb_akomodasi_agg as $o) {
            $ovb_akomodasi_map[$o->id_akomodasi] = $o;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting_akomodasi = $this->db->get()->result();

        $get_kasbon_akomodasi = [];
        $processed_akomodasi_ids = [];

        foreach ($get_budgeting_akomodasi as $b_item) {
            $item_id = (string) trim($b_item->id_akomodasi);
            $processed_akomodasi_ids[$item_id] = true;

            if (isset($submitted_akomodasi[$item_id])) {
                $get_kasbon_akomodasi[] = $submitted_akomodasi[$item_id];
            } else {
                $prior_qty = isset($prior_akomodasi_map[$item_id]) ? (float)$prior_akomodasi_map[$item_id]->ttl_qty_pengajuan : 0;
                $prior_total = isset($prior_akomodasi_map[$item_id]) ? (float)$prior_akomodasi_map[$item_id]->ttl_total_pengajuan : 0;

                $ovb_qty = isset($ovb_akomodasi_map[$item_id]) ? (float)$ovb_akomodasi_map[$item_id]->ttl_qty_tambahan : 0;
                $ovb_total = isset($ovb_akomodasi_map[$item_id]) ? (float)$ovb_akomodasi_map[$item_id]->total_budget_tambahan : 0;
                $ovb_nominal = ($ovb_qty > 0) ? ($ovb_total / $ovb_qty) : 0;

                $qty_estimasi = (float)$b_item->qty_final;
                $price_unit_estimasi = (float)$b_item->price_unit_final;
                $total_budget_estimasi = (float)$b_item->total_final;

                $qty_terpakai = $prior_qty;
                $nominal_terpakai = $price_unit_estimasi;
                $total_terpakai = $prior_total;

                $qty_overbudget = $ovb_qty;
                $nominal_overbudget = $ovb_nominal;
                $total_overbudget = $ovb_total;

                $sisa_budget = ($total_budget_estimasi - $total_terpakai + $total_overbudget);
                $aktual_terpakai = $qty_terpakai;

                $obj = new stdClass();
                $obj->id_header = $id_kasbon;
                $obj->id_spk_budgeting = $id_spk_budgeting;
                $obj->id_spk_penawaran = $b_item->id_spk_penawaran;
                $obj->id_penawaran = $b_item->id_penawaran;
                $obj->id_akomodasi = $item_id;
                $obj->id_item = $b_item->id_item;
                $obj->nm_item = $b_item->nm_item;
                $obj->nm_biaya = !empty($b_item->nm_biaya) ? $b_item->nm_biaya : $b_item->nm_item;
                $obj->qty_pengajuan = 0;
                $obj->nominal_pengajuan = 0;
                $obj->total_pengajuan = 0;
                $obj->qty_estimasi = $qty_estimasi;
                $obj->price_unit_estimasi = $price_unit_estimasi;
                $obj->total_budget_estimasi = $total_budget_estimasi;
                $obj->qty_budget_tambahan = $ovb_qty;
                $obj->budget_tambahan = $ovb_total;
                $obj->aktual_terpakai = $aktual_terpakai;
                $obj->sisa_budget = $sisa_budget;
                $obj->qty_terpakai = $qty_terpakai;
                $obj->nominal_terpakai = $nominal_terpakai;
                $obj->total_terpakai = $total_terpakai;
                $obj->qty_overbudget = $qty_overbudget;
                $obj->nominal_overbudget = $nominal_overbudget;
                $obj->total_overbudget = $total_overbudget;
                $obj->custom_akomodasi = 0;

                $get_kasbon_akomodasi[] = $obj;
            }
        }

        foreach ($submitted_akomodasi as $item_id => $s_item) {
            if (!isset($processed_akomodasi_ids[$item_id])) {
                $get_kasbon_akomodasi[] = $s_item;
            }
        }

        // Item custom (tanpa referensi detail budgeting) ditambahkan sekali.
        foreach ($submitted_akomodasi_custom as $s_item) {
            $get_kasbon_akomodasi[] = $s_item;
        }

        // ----------------------------------------------------
        // Tipe 3: OTHERS
        // ----------------------------------------------------
        $this->db->select('a.*, IF(a.custom_others = 1, a.nm_item, b.nm_biaya) as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $get_kasbon_others_submitted = $this->db->get()->result();

        $submitted_others = [];
        foreach ($get_kasbon_others_submitted as $item) {
            if (empty($item->id_others)) {
                continue;
            }
            $submitted_others[(string) trim($item->id_others)] = $item;
        }

        $submitted_others_custom = [];
        foreach ($get_kasbon_others_submitted as $item) {
            if (empty($item->id_others)) {
                $submitted_others_custom[] = $item;
            }
        }

        $this->db->select('a.id_others, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.id_header !=', $id_kasbon);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_others');
        $get_prior_others = $this->db->get()->result();

        $prior_others_map = [];
        foreach ($get_prior_others as $p) {
            $prior_others_map[$p->id_others] = $p;
        }

        $this->db->select('c.id_others, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->join('kons_tr_spk_budgeting_others c', 'c.id = b.id_detail');
        $this->db->where('a.tipe', 3);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('c.id_others');
        $get_ovb_others_agg = $this->db->get()->result();

        $ovb_others_map = [];
        foreach ($get_ovb_others_agg as $o) {
            $ovb_others_map[$o->id_others] = $o;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting_others = $this->db->get()->result();

        $get_kasbon_others = [];
        $processed_others_ids = [];

        foreach ($get_budgeting_others as $b_item) {
            $item_id = (string) trim($b_item->id_others);
            $processed_others_ids[$item_id] = true;

            if (isset($submitted_others[$item_id])) {
                $get_kasbon_others[] = $submitted_others[$item_id];
            } else {
                $prior_qty = isset($prior_others_map[$item_id]) ? (float)$prior_others_map[$item_id]->ttl_qty_pengajuan : 0;
                $prior_total = isset($prior_others_map[$item_id]) ? (float)$prior_others_map[$item_id]->ttl_total_pengajuan : 0;

                $ovb_qty = isset($ovb_others_map[$item_id]) ? (float)$ovb_others_map[$item_id]->ttl_qty_tambahan : 0;
                $ovb_total = isset($ovb_others_map[$item_id]) ? (float)$ovb_others_map[$item_id]->total_budget_tambahan : 0;
                $ovb_nominal = ($ovb_qty > 0) ? ($ovb_total / $ovb_qty) : 0;

                $qty_estimasi = (float)$b_item->qty_final;
                $price_unit_estimasi = (float)$b_item->price_unit_final;
                $total_budget_estimasi = (float)$b_item->total_final;

                $qty_terpakai = $prior_qty;
                $nominal_terpakai = $price_unit_estimasi;
                $total_terpakai = $prior_total;

                $qty_overbudget = $ovb_qty;
                $nominal_overbudget = $ovb_nominal;
                $total_overbudget = $ovb_total;

                $sisa_budget = ($total_budget_estimasi - $total_terpakai + $total_overbudget);
                $aktual_terpakai = $qty_terpakai;

                $obj = new stdClass();
                $obj->id_header = $id_kasbon;
                $obj->id_spk_budgeting = $id_spk_budgeting;
                $obj->id_spk_penawaran = $b_item->id_spk_penawaran;
                $obj->id_penawaran = $b_item->id_penawaran;
                $obj->id_others = $item_id;
                $obj->id_item = $b_item->id_item;
                $obj->nm_item = $b_item->nm_item;
                $obj->nm_biaya = !empty($b_item->nm_biaya) ? $b_item->nm_biaya : $b_item->nm_item;
                $obj->qty_pengajuan = 0;
                $obj->nominal_pengajuan = 0;
                $obj->total_pengajuan = 0;
                $obj->qty_estimasi = $qty_estimasi;
                $obj->price_unit_estimasi = $price_unit_estimasi;
                $obj->total_budget_estimasi = $total_budget_estimasi;
                $obj->qty_budget_tambahan = $ovb_qty;
                $obj->budget_tambahan = $ovb_total;
                $obj->aktual_terpakai = $aktual_terpakai;
                $obj->sisa_budget = $sisa_budget;
                $obj->qty_terpakai = $qty_terpakai;
                $obj->nominal_terpakai = $nominal_terpakai;
                $obj->total_terpakai = $total_terpakai;
                $obj->qty_overbudget = $qty_overbudget;
                $obj->nominal_overbudget = $nominal_overbudget;
                $obj->total_overbudget = $total_overbudget;
                $obj->custom_others = 0;

                $get_kasbon_others[] = $obj;
            }
        }

        foreach ($submitted_others as $item_id => $s_item) {
            if (!isset($processed_others_ids[$item_id])) {
                $get_kasbon_others[] = $s_item;
            }
        }

        // Item custom (tanpa referensi detail budgeting) ditambahkan sekali.
        foreach ($submitted_others_custom as $s_item) {
            $get_kasbon_others[] = $s_item;
        }

        // ----------------------------------------------------
        // Tipe 4: LAB
        // ----------------------------------------------------
        $this->db->select('a.*, IF(a.custom_lab = 1, a.nm_item, b.isu_lingkungan) as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $get_kasbon_lab_submitted = $this->db->get()->result();

        $submitted_lab = [];
        foreach ($get_kasbon_lab_submitted as $item) {
            if (empty($item->id_lab)) {
                continue;
            }
            $submitted_lab[(string) trim($item->id_lab)] = $item;
        }

        $submitted_lab_custom = [];
        foreach ($get_kasbon_lab_submitted as $item) {
            if (empty($item->id_lab)) {
                $submitted_lab_custom[] = $item;
            }
        }

        $this->db->select('a.id_lab, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.id_header !=', $id_kasbon);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_lab');
        $get_prior_lab = $this->db->get()->result();

        $prior_lab_map = [];
        foreach ($get_prior_lab as $p) {
            $prior_lab_map[$p->id_lab] = $p;
        }

        $this->db->select('c.id_lab, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->join('kons_tr_spk_budgeting_lab c', 'c.id = b.id_detail');
        $this->db->where('a.tipe', 4);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('c.id_lab');
        $get_ovb_lab_agg = $this->db->get()->result();

        $ovb_lab_map = [];
        foreach ($get_ovb_lab_agg as $o) {
            $ovb_lab_map[$o->id_lab] = $o;
        }

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting_lab = $this->db->get()->result();

        $get_kasbon_lab = [];
        $processed_lab_ids = [];

        foreach ($get_budgeting_lab as $b_item) {
            $item_id = (string) trim($b_item->id_lab);
            $processed_lab_ids[$item_id] = true;

            if (isset($submitted_lab[$item_id])) {
                $get_kasbon_lab[] = $submitted_lab[$item_id];
            } else {
                $prior_qty = isset($prior_lab_map[$item_id]) ? (float)$prior_lab_map[$item_id]->ttl_qty_pengajuan : 0;
                $prior_total = isset($prior_lab_map[$item_id]) ? (float)$prior_lab_map[$item_id]->ttl_total_pengajuan : 0;

                $ovb_qty = isset($ovb_lab_map[$item_id]) ? (float)$ovb_lab_map[$item_id]->ttl_qty_tambahan : 0;
                $ovb_total = isset($ovb_lab_map[$item_id]) ? (float)$ovb_lab_map[$item_id]->total_budget_tambahan : 0;
                $ovb_nominal = ($ovb_qty > 0) ? ($ovb_total / $ovb_qty) : 0;

                $qty_estimasi = (float)$b_item->qty_final;
                $price_unit_estimasi = (float)$b_item->price_unit_final;
                $total_budget_estimasi = (float)$b_item->total_final;

                $qty_terpakai = $prior_qty;
                $nominal_terpakai = $price_unit_estimasi;
                $total_terpakai = $prior_total;

                $qty_overbudget = $ovb_qty;
                $nominal_overbudget = $ovb_nominal;
                $total_overbudget = $ovb_total;

                $sisa_budget = ($total_budget_estimasi - $total_terpakai + $total_overbudget);
                $aktual_terpakai = $qty_terpakai;

                $obj = new stdClass();
                $obj->id_header = $id_kasbon;
                $obj->id_spk_budgeting = $id_spk_budgeting;
                $obj->id_spk_penawaran = $b_item->id_spk_penawaran;
                $obj->id_penawaran = $b_item->id_penawaran;
                $obj->id_lab = $item_id;
                $obj->id_item = $b_item->id_item;
                $obj->nm_item = $b_item->nm_item;
                $obj->nm_biaya = !empty($b_item->nm_biaya) ? $b_item->nm_biaya : $b_item->nm_item;
                $obj->qty_pengajuan = 0;
                $obj->nominal_pengajuan = 0;
                $obj->total_pengajuan = 0;
                $obj->qty_estimasi = $qty_estimasi;
                $obj->price_unit_estimasi = $price_unit_estimasi;
                $obj->total_budget_estimasi = $total_budget_estimasi;
                $obj->qty_budget_tambahan = $ovb_qty;
                $obj->budget_tambahan = $ovb_total;
                $obj->aktual_terpakai = $aktual_terpakai;
                $obj->sisa_budget = $sisa_budget;
                $obj->qty_terpakai = $qty_terpakai;
                $obj->nominal_terpakai = $nominal_terpakai;
                $obj->total_terpakai = $total_terpakai;
                $obj->qty_overbudget = $qty_overbudget;
                $obj->nominal_overbudget = $nominal_overbudget;
                $obj->total_overbudget = $total_overbudget;
                $obj->custom_lab = 0;

                $get_kasbon_lab[] = $obj;
            }
        }

        foreach ($submitted_lab as $item_id => $s_item) {
            if (!isset($processed_lab_ids[$item_id])) {
                $get_kasbon_lab[] = $s_item;
            }
        }

        // Item custom (tanpa referensi detail budgeting) ditambahkan sekali.
        foreach ($submitted_lab_custom as $s_item) {
            $get_kasbon_lab[] = $s_item;
        }

        // ----------------------------------------------------
        // Tipe 5: SUBCONT TENAGA AHLI
        // ----------------------------------------------------
        $this->db->select('a.*, IF(a.custom_subcont_tenaga_ahli = 1, a.nm_item, b.nm_biaya) as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $get_kasbon_subcont_tenaga_ahli_submitted = $this->db->get()->result();

        $submitted_subcont_tenaga_ahli = [];
        foreach ($get_kasbon_subcont_tenaga_ahli_submitted as $item) {
            if (empty($item->id_subcont)) {
                continue;
            }
            $submitted_subcont_tenaga_ahli[(string) trim($item->id_subcont)] = $item;
        }

        $submitted_subcont_tenaga_ahli_custom = [];
        foreach ($get_kasbon_subcont_tenaga_ahli_submitted as $item) {
            if (empty($item->id_subcont)) {
                $submitted_subcont_tenaga_ahli_custom[] = $item;
            }
        }

        $this->db->select('a.id_subcont, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.id_header !=', $id_kasbon);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_subcont');
        $get_prior_subcont_tenaga_ahli = $this->db->get()->result();

        $prior_subcont_tenaga_ahli_map = [];
        foreach ($get_prior_subcont_tenaga_ahli as $p) {
            $prior_subcont_tenaga_ahli_map[$p->id_subcont] = $p;
        }

        $this->db->select('c.id_subcont, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->join('kons_tr_spk_budgeting_subcont_tenaga_ahli c', 'c.id = b.id_detail');
        $this->db->where('a.tipe', 5);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('c.id_subcont');
        $get_ovb_subcont_tenaga_ahli_agg = $this->db->get()->result();

        $ovb_subcont_tenaga_ahli_map = [];
        foreach ($get_ovb_subcont_tenaga_ahli_agg as $o) {
            $ovb_subcont_tenaga_ahli_map[$o->id_subcont] = $o;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting_subcont_tenaga_ahli = $this->db->get()->result();

        $get_kasbon_subcont_tenaga_ahli = [];
        $processed_subcont_tenaga_ahli_ids = [];

        foreach ($get_budgeting_subcont_tenaga_ahli as $b_item) {
            $item_id = (string) trim($b_item->id_subcont);
            $processed_subcont_tenaga_ahli_ids[$item_id] = true;

            if (isset($submitted_subcont_tenaga_ahli[$item_id])) {
                $get_kasbon_subcont_tenaga_ahli[] = $submitted_subcont_tenaga_ahli[$item_id];
            } else {
                $prior_qty = isset($prior_subcont_tenaga_ahli_map[$item_id]) ? (float)$prior_subcont_tenaga_ahli_map[$item_id]->ttl_qty_pengajuan : 0;
                $prior_total = isset($prior_subcont_tenaga_ahli_map[$item_id]) ? (float)$prior_subcont_tenaga_ahli_map[$item_id]->ttl_total_pengajuan : 0;

                $ovb_qty = isset($ovb_subcont_tenaga_ahli_map[$item_id]) ? (float)$ovb_subcont_tenaga_ahli_map[$item_id]->ttl_qty_tambahan : 0;
                $ovb_total = isset($ovb_subcont_tenaga_ahli_map[$item_id]) ? (float)$ovb_subcont_tenaga_ahli_map[$item_id]->total_budget_tambahan : 0;
                $ovb_nominal = ($ovb_qty > 0) ? ($ovb_total / $ovb_qty) : 0;

                $qty_estimasi = (float)$b_item->qty_final;
                $price_unit_estimasi = (float)$b_item->price_unit_final;
                $total_budget_estimasi = (float)$b_item->total_final;

                $qty_terpakai = $prior_qty;
                $nominal_terpakai = $price_unit_estimasi;
                $total_terpakai = $prior_total;

                $qty_overbudget = $ovb_qty;
                $nominal_overbudget = $ovb_nominal;
                $total_overbudget = $ovb_total;

                $sisa_budget = ($total_budget_estimasi - $total_terpakai + $total_overbudget);
                $aktual_terpakai = $qty_terpakai;

                $obj = new stdClass();
                $obj->id_header = $id_kasbon;
                $obj->id_spk_budgeting = $id_spk_budgeting;
                $obj->id_spk_penawaran = $b_item->id_spk_penawaran;
                $obj->id_penawaran = $b_item->id_penawaran;
                $obj->id_subcont = $item_id;
                $obj->id_item = $b_item->id_item;
                $obj->nm_item = $b_item->nm_item;
                $obj->nm_biaya = !empty($b_item->nm_biaya) ? $b_item->nm_biaya : $b_item->nm_item;
                $obj->qty_pengajuan = 0;
                $obj->nominal_pengajuan = 0;
                $obj->total_pengajuan = 0;
                $obj->qty_estimasi = $qty_estimasi;
                $obj->price_unit_estimasi = $price_unit_estimasi;
                $obj->total_budget_estimasi = $total_budget_estimasi;
                $obj->qty_budget_tambahan = $ovb_qty;
                $obj->budget_tambahan = $ovb_total;
                $obj->aktual_terpakai = $aktual_terpakai;
                $obj->sisa_budget = $sisa_budget;
                $obj->qty_terpakai = $qty_terpakai;
                $obj->nominal_terpakai = $nominal_terpakai;
                $obj->total_terpakai = $total_terpakai;
                $obj->qty_overbudget = $qty_overbudget;
                $obj->nominal_overbudget = $nominal_overbudget;
                $obj->total_overbudget = $total_overbudget;
                $obj->custom_subcont_tenaga_ahli = 0;

                $get_kasbon_subcont_tenaga_ahli[] = $obj;
            }
        }

        foreach ($submitted_subcont_tenaga_ahli as $item_id => $s_item) {
            if (!isset($processed_subcont_tenaga_ahli_ids[$item_id])) {
                $get_kasbon_subcont_tenaga_ahli[] = $s_item;
            }
        }

        // Item custom (tanpa referensi detail budgeting) ditambahkan sekali.
        foreach ($submitted_subcont_tenaga_ahli_custom as $s_item) {
            $get_kasbon_subcont_tenaga_ahli[] = $s_item;
        }

        // ----------------------------------------------------
        // Tipe 6: SUBCONT PERUSAHAAN
        // ----------------------------------------------------
        $this->db->select('a.*, IF(a.custom_subcont_perusahaan = 1, a.nm_item, b.nm_biaya) as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_kasbon);
        $this->db->where('a.sts', null);
        $get_kasbon_subcont_perusahaan_submitted = $this->db->get()->result();

        $submitted_subcont_perusahaan = [];
        foreach ($get_kasbon_subcont_perusahaan_submitted as $item) {
            if (empty($item->id_subcont)) {
                continue;
            }
            $submitted_subcont_perusahaan[(string) trim($item->id_subcont)] = $item;
        }

        $submitted_subcont_perusahaan_custom = [];
        foreach ($get_kasbon_subcont_perusahaan_submitted as $item) {
            if (empty($item->id_subcont)) {
                $submitted_subcont_perusahaan_custom[] = $item;
            }
        }

        $this->db->select('a.id_subcont, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.id_header !=', $id_kasbon);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_subcont');
        $get_prior_subcont_perusahaan = $this->db->get()->result();

        $prior_subcont_perusahaan_map = [];
        foreach ($get_prior_subcont_perusahaan as $p) {
            $prior_subcont_perusahaan_map[$p->id_subcont] = $p;
        }

        $this->db->select('c.id_subcont, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->join('kons_tr_spk_budgeting_subcont_perusahaan c', 'c.id = b.id_detail');
        $this->db->where('a.tipe', 6);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('c.id_subcont');
        $get_ovb_subcont_perusahaan_agg = $this->db->get()->result();

        $ovb_subcont_perusahaan_map = [];
        foreach ($get_ovb_subcont_perusahaan_agg as $o) {
            $ovb_subcont_perusahaan_map[$o->id_subcont] = $o;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting_subcont_perusahaan = $this->db->get()->result();

        $get_kasbon_subcont_perusahaan = [];
        $processed_subcont_perusahaan_ids = [];

        foreach ($get_budgeting_subcont_perusahaan as $b_item) {
            $item_id = (string) trim($b_item->id_subcont);
            $processed_subcont_perusahaan_ids[$item_id] = true;

            if (isset($submitted_subcont_perusahaan[$item_id])) {
                $get_kasbon_subcont_perusahaan[] = $submitted_subcont_perusahaan[$item_id];
            } else {
                $prior_qty = isset($prior_subcont_perusahaan_map[$item_id]) ? (float)$prior_subcont_perusahaan_map[$item_id]->ttl_qty_pengajuan : 0;
                $prior_total = isset($prior_subcont_perusahaan_map[$item_id]) ? (float)$prior_subcont_perusahaan_map[$item_id]->ttl_total_pengajuan : 0;

                $ovb_qty = isset($ovb_subcont_perusahaan_map[$item_id]) ? (float)$ovb_subcont_perusahaan_map[$item_id]->ttl_qty_tambahan : 0;
                $ovb_total = isset($ovb_subcont_perusahaan_map[$item_id]) ? (float)$ovb_subcont_perusahaan_map[$item_id]->total_budget_tambahan : 0;
                $ovb_nominal = ($ovb_qty > 0) ? ($ovb_total / $ovb_qty) : 0;

                $qty_estimasi = (float)$b_item->qty_final;
                $price_unit_estimasi = (float)$b_item->price_unit_final;
                $total_budget_estimasi = (float)$b_item->total_final;

                $qty_terpakai = $prior_qty;
                $nominal_terpakai = $price_unit_estimasi;
                $total_terpakai = $prior_total;

                $qty_overbudget = $ovb_qty;
                $nominal_overbudget = $ovb_nominal;
                $total_overbudget = $ovb_total;

                $sisa_budget = ($total_budget_estimasi - $total_terpakai + $total_overbudget);
                $aktual_terpakai = $qty_terpakai;

                $obj = new stdClass();
                $obj->id_header = $id_kasbon;
                $obj->id_spk_budgeting = $id_spk_budgeting;
                $obj->id_spk_penawaran = $b_item->id_spk_penawaran;
                $obj->id_penawaran = $b_item->id_penawaran;
                $obj->id_subcont = $item_id;
                $obj->id_item = $b_item->id_item;
                $obj->nm_item = $b_item->nm_item;
                $obj->nm_biaya = !empty($b_item->nm_biaya) ? $b_item->nm_biaya : $b_item->nm_item;
                $obj->qty_pengajuan = 0;
                $obj->nominal_pengajuan = 0;
                $obj->total_pengajuan = 0;
                $obj->qty_estimasi = $qty_estimasi;
                $obj->price_unit_estimasi = $price_unit_estimasi;
                $obj->total_budget_estimasi = $total_budget_estimasi;
                $obj->qty_budget_tambahan = $ovb_qty;
                $obj->budget_tambahan = $ovb_total;
                $obj->aktual_terpakai = $aktual_terpakai;
                $obj->sisa_budget = $sisa_budget;
                $obj->qty_terpakai = $qty_terpakai;
                $obj->nominal_terpakai = $nominal_terpakai;
                $obj->total_terpakai = $total_terpakai;
                $obj->qty_overbudget = $qty_overbudget;
                $obj->nominal_overbudget = $nominal_overbudget;
                $obj->total_overbudget = $total_overbudget;
                $obj->custom_subcont_perusahaan = 0;

                $get_kasbon_subcont_perusahaan[] = $obj;
            }
        }

        foreach ($submitted_subcont_perusahaan as $item_id => $s_item) {
            if (!isset($processed_subcont_perusahaan_ids[$item_id])) {
                $get_kasbon_subcont_perusahaan[] = $s_item;
            }
        }

        // Item custom (tanpa referensi detail budgeting) ditambahkan sekali.
        foreach ($submitted_subcont_perusahaan_custom as $s_item) {
            $get_kasbon_subcont_perusahaan[] = $s_item;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header c', 'c.id_request_ovb = a.id_request_ovb');
        $this->db->where('c.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('c.sts', '1');
        $get_ovb_akomodasi = $this->db->get()->result();

        $this->db->select('a.id_aktifitas, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_subcont = $this->db->get()->result();

        $data_overbudget_subcont = [];
        foreach ($get_ovb_subcont as $item_ovb_subcont) :
            $data_overbudget_subcont[$item_ovb_subcont->id_aktifitas] = [
                'qty_budget_tambahan' => $item_ovb_subcont->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_subcont->budget_tambahan,
                'pengajuan_budget' => $item_ovb_subcont->pengajuan_budget
            ];
        endforeach;

        $this->db->select('a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_others');
        $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_others c', 'c.id = a.id_detail');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_others = $this->db->get()->result();

        $data_overbudget_others = [];
        foreach ($get_ovb_others as $item_ovb_others) :
            $data_overbudget_others[$item_ovb_others->id_others] = [
                'qty_budget_tambahan' => $item_ovb_others->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_others->budget_tambahan,
                'pengajuan_budget' => $item_ovb_others->pengajuan_budget
            ];
        endforeach;

        $this->db->select('a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_lab');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_lab c', 'c.id = a.id_detail');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_lab = $this->db->get()->result();

        $data_overbudget_lab = [];
        foreach ($get_ovb_lab as $item_ovb_lab) :
            $data_overbudget_lab[$item_ovb_lab->id_lab] = [
                'qty_budget_tambahan' => $item_ovb_lab->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_lab->budget_tambahan,
                'pengajuan_budget' => $item_ovb_lab->pengajuan_budget
            ];
        endforeach;

        $this->db->select('a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_subcont_tenaga_ahli c', 'c.id = a.id_detail');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_subcont_tenaga_ahli = $this->db->get()->result();

        $data_overbudget_subcont_tenaga_ahli = [];
        foreach ($get_ovb_subcont_tenaga_ahli as $item_ovb_subcont_tenaga_ahli) :
            $data_overbudget_subcont_tenaga_ahli[$item_ovb_subcont_tenaga_ahli->id_subcont] = [
                'qty_budget_tambahan' => $item_ovb_subcont_tenaga_ahli->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_subcont_tenaga_ahli->budget_tambahan,
                'pengajuan_budget' => $item_ovb_subcont_tenaga_ahli->pengajuan_budget
            ];
        endforeach;

        $this->db->select('a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_subcont_perusahaan c', 'c.id = a.id_detail');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_subcont_perusahaan = $this->db->get()->result();

        $data_overbudget_subcont_perusahaan = [];
        foreach ($get_ovb_subcont_perusahaan as $item_ovb_subcont_perusahaan) :
            $data_overbudget_subcont_perusahaan[$item_ovb_subcont_perusahaan->id_subcont] = [
                'qty_budget_tambahan' => $item_ovb_subcont_perusahaan->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_subcont_perusahaan->budget_tambahan,
                'pengajuan_budget' => $item_ovb_subcont_perusahaan->pengajuan_budget
            ];
        endforeach;

        $get_header = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $id_kasbon, 'deleted_at' => NULL])->row();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $data = [
            'id_kasbon' => $id_kasbon,
            'id_spk_budgeting' => $id_spk_budgeting,
            'tipe' => $get_header->tipe,
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_kasbon_subcont' => $get_kasbon_subcont,
            'list_kasbon_subcont_custom' => $get_kasbon_subcont_custom,
            'list_kasbon_akomodasi' => $get_kasbon_akomodasi,
            'list_kasbon_others' => $get_kasbon_others,
            'list_kasbon_lab' => $get_kasbon_lab,
            'list_kasbon_subcont_tenaga_ahli' => $get_kasbon_subcont_tenaga_ahli,
            'list_kasbon_subcont_perusahaan' => $get_kasbon_subcont_perusahaan,
            'list_ovb_akomodasi' => $get_ovb_akomodasi,
            'data_overbudget_subcont' => $data_overbudget_subcont,
            'data_overbudget_others' => $data_overbudget_others,
            'data_overbudget_lab' => $data_overbudget_lab,
            'data_overbudget_subcont_tenaga_ahli' => $data_overbudget_subcont_tenaga_ahli,
            'data_overbudget_subcont_perusahaan' => $data_overbudget_subcont_perusahaan,
            'list_bukti_penggunaan' => $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header ?? $id_kasbon])->result(),
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $metode_pembayaran = '';
        if ($get_header->metode_pembayaran == '1') {
            $metode_pembayaran = 'Kasbon';
        }
        if ($get_header->metode_pembayaran == '2') {
            $metode_pembayaran = 'Direct Payment';
        }
        if ($get_header->metode_pembayaran == '3') {
            $metode_pembayaran = 'PO';
        }

        $this->template->set($data);
        $this->template->title('Approval Pengajuan ' . $metode_pembayaran);
        $this->template->render('approval_kasbon');
    }

    public function reject_kasbon()
    {
        // 1. Ambil input
        $id_kasbon     = $this->input->post('id_kasbon');
        $reject_reason = $this->input->post('reject_reason');

        // Validasi dasar: pastikan ID ada
        if (!$id_kasbon) {
            echo json_encode(['status' => 0, 'pesan' => 'ID Kasbon tidak ditemukan!']);
            return;
        }

        // 2. Mulai Transaksi
        $this->db->trans_begin();

        try {
            $data_update = [
                'sts'           => 2,
                'reject_reason' => $reject_reason,
                'rejected_by'   => $this->auth->user_id(),
                'rejected_date' => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ];

            // Jalankan update
            $this->db->update('kons_tr_req_kasbon_project', $data_update, [
                'id_kasbon' => $id_kasbon,
                'sts'       => 0 // Memastikan hanya data 'pending' yang bisa direject
            ]);

            $data_update_pengajuan = [
                'sts_reject'    => 1,
                'reject_reason' => $reject_reason,
                'rejected_by'   => $this->auth->user_id(),
                'rejected_date' => date('Y-m-d H:i:s')
            ];

            $this->db->update('kons_tr_kasbon_project_header', $data_update_pengajuan, [
                'id' => $id_kasbon
            ]);

            // // Cek apakah ada baris yang beneran ter-update
            // if ($this->db->affected_rows() == 0) {
            //     throw new Exception("Data tidak ditemukan atau sudah diproses sebelumnya.");
            // }

            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Kasbon berhasil direject!';

            echo json_encode([
                'status' => $valid,
                'pesan'  => $pesan
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => $e->getMessage()]);
        }

        // // 3. Final Check Transaksi
        // if ($this->db->trans_status() === FALSE) {
        //     $this->db->trans_rollback();
        //     $valid = 0;
        //     $pesan = 'Gagal menolak kasbon, silakan coba lagi nanti.';
        // } else {
        //     $this->db->trans_commit();
        //     $valid = 1;
        //     $pesan = 'Kasbon berhasil direject!';
        // }

        // echo json_encode([
        //     'status' => $valid,
        //     'pesan'  => $pesan
        // ]);
    }

    public function approve_kasbon()
    {
        $id_kasbon = $this->input->post('id_kasbon');

        $get_header_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $id_kasbon))->row();

        $get_user = $this->db->get_where('users', array('id_user' => $get_header_kasbon->created_by))->row();

        $nm_user = (!empty($get_user)) ? $get_user->nm_lengkap : '';

        $get_direktur_user = $this->db->get_where('users', array('id_user' => 48))->row();

        $data_insert_req_payment = [
            'no_doc' => $id_kasbon,
            'nama' => $nm_user,
            'tgl_doc' => date('Y-m-d'),
            'keperluan' => $get_header_kasbon->deskripsi,
            'tipe' => 'kasbon',
            'jumlah' => $get_header_kasbon->grand_total,
            'status' => 0,
            'created_by' => $get_user->username,
            'created_on' => date('Y-m-d H:i:s'),
            'ids' => $id_kasbon,
            'currency' => 'IDR'
        ];

        // $no_doc = '';
        // $newcode = '';
        // $data = $this->db->get_where(DBSF . '.ms_generate', array('tipe' => 'format_kasbon'))->row();
        // if ($data !== false) {
        //     if (stripos($data->info, 'YEAR', 0) !== false) {
        //         if ($data->info3 != date("Y")) {
        //             $years = date("Y");
        //             $number = 1;
        //             $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
        //         } else {
        //             $years = $data->info3;
        //             $number = ($data->info2 + 1);
        //             $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
        //         }
        //         $newcode = str_ireplace('XXXX', $newnumber, $data->info);
        //         $newcode = str_ireplace('YEAR', $years, $newcode);
        //         $newdata = array('info2' => $number, 'info3' => $years);
        //     } else {
        //         $number = ($data->info2 + 1);
        //         $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
        //         $newcode = str_ireplace('XXXX', $newnumber, $data->info);
        //         $newdata = array('info2' => $number);
        //     }
        //     $this->db->update(DBSF . '.ms_generate', $newdata, array('tipe' => 'format_kasbon'));

        //     $no_doc = $newcode;
        // } else {
        //     return false;
        // }

        $project = '';
        if ($get_header_kasbon->tipe == '1') :
            $project = 'Subcont';
        endif;
        if ($get_header_kasbon->tipe == '2') :
            $project = 'Akomodasi';
        endif;
        if ($get_header_kasbon->tipe == '3') :
            $project = 'Others';
        endif;
        if ($get_header_kasbon->tipe == '4') :
            $project = 'Lab';
        endif;
        if ($get_header_kasbon->tipe == '5') :
            $project = 'Subcont Tenaga Ahli';
        endif;
        if ($get_header_kasbon->tipe == '6') :
            $project = 'Subcont Perusahaan';
        endif;

        // $data_insert_sendigs_kasbon = [
        //     'no_doc' => $no_doc,
        //     'tgl_doc' => date('Y-m-d'),
        //     'departement' => '',
        //     'nama' => $nm_user,
        //     'jumlah_kasbon' => $get_header_kasbon->grand_total,
        //     'keperluan' => $get_header_kasbon->deskripsi,
        //     'doc_file' => $get_header_kasbon->dokument_link,
        //     'status' => 1,
        //     'created_by' => $nm_user,
        //     'created_on' => date('Y-m-d H:i:s'),
        //     'bank_id' => $get_header_kasbon->bank,
        //     'accnumber' => $get_header_kasbon->bank_number,
        //     'accname' => $get_header_kasbon->bank_account,
        //     'project' => $project,
        //     'approved_by' => $get_direktur_user->nm_lengkap,
        //     'approved_on' => date('Y-m-d H:i:s'),
        //     'keterangan' => $get_header_kasbon->deskripsi,
        //     'metode_pembayaran' => 1,
        //     'project_consultant' => 1,
        //     'no_kasbon_consultant' => $id_kasbon
        // ];

        $this->db->trans_begin();

        // if ($get_header_kasbon->metode_pembayaran == '1') {
        //     $insert_sendigs_kasbon = $this->otherdb->insert('tr_kasbon', $data_insert_sendigs_kasbon);
        //     if (!$insert_sendigs_kasbon) {
        //         $this->db->trans_rollback();

        //         print_r($this->db->last_query());
        //         exit;
        //     }
        // }

        // INSERT DIRECT PAYMENT
        // if ($get_header_kasbon->metode_pembayaran == '2') {

        //     $no_doc = $id_kasbon;

        //     $data_insert_direct_payment_sendigs = [
        //         'no_doc' => $no_doc,
        //         'tgl_doc' => date('Y-m-d'),
        //         'ids' => $id_kasbon,
        //         'id_spk_budgeting' => $get_header_kasbon->id_spk_budgeting,
        //         'id_spk_penawaran' => $get_header_kasbon->id_spk_penawaran,
        //         'id_penawaran' => $get_header_kasbon->id_penawaran,
        //         'tipe' => $get_header_kasbon->tipe,
        //         'deskripsi' => $get_header_kasbon->deskripsi,
        //         'grand_total' => $get_header_kasbon->grand_total,
        //         'bank' => $get_header_kasbon->bank,
        //         'bank_number' => $get_header_kasbon->bank_number,
        //         'bank_account' => $get_header_kasbon->bank_account,
        //         'metode_pembayaran' => 1,
        //         'sts' => 1,
        //         'created_by' => $this->auth->user_id(),
        //         'created_date' => date('Y-m-d H:i:s')
        //     ];

        //     $insert_direct_payment_sendigs = $this->otherdb->insert('tr_direct_payment', $data_insert_direct_payment_sendigs);
        //     if (!$insert_direct_payment_sendigs) {
        //         $this->db->trans_rollback();

        //         print_r($this->db->last_query());
        //         exit;
        //     }
        // }

        // if ($get_header_kasbon->metode_pembayaran == '3') {
        // }

        $get_created_kasbon = $this->db->get_where('users', ['id_user' => $get_header_kasbon->created_by])->row();
        $nm_user = (!empty($get_created_kasbon)) ? $get_created_kasbon->nm_lengkap : '';

        $arr_insert_req_payment = [
            'no_doc' => $get_header_kasbon->id,
            'nama' => $nm_user,
            'tgl_doc' => $get_header_kasbon->tgl,
            'keperluan' => $get_header_kasbon->deskripsi,
            'tipe' => 'kasbon',
            'jumlah' => $get_header_kasbon->grand_total,
            'status' => 0,
            'created_by' => $nm_user,
            'created_on' => date('Y-m-d H:i:s'),
            'ids' => $get_header_kasbon->id,
            'currency' => 'IDR'
        ];

        $insert_request_payment = $this->db->insert('request_payment', $arr_insert_req_payment);
        if (!$insert_request_payment) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req = $this->db->update('kons_tr_kasbon_project_header', [
            'sts' => 1,
            'approved_by' => $this->auth->user_id(),
            'approved_date' => date('Y-m-d H:i:s')
        ], ['id' => $id_kasbon]);
        if (!$update_req) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req = $this->db->update('kons_tr_req_kasbon_project', ['sts' => 1], ['id_kasbon' => $id_kasbon, 'sts' => 0]);
        if (!$update_req) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_header = $this->db->update('kons_tr_kasbon_project_header', [
            'sts' => 1,
            'approved_by' => $this->auth->user_id(),
            'approved_date' => date('Y-m-d H:i:s')
        ], ['id' => $id_kasbon]);
        if (!$update_req_header) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_subcont = $this->db->update('kons_tr_kasbon_project_subcont', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_subcont) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_akomodasi = $this->db->update('kons_tr_kasbon_project_akomodasi', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_akomodasi) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_others = $this->db->update('kons_tr_kasbon_project_others', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_others) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_lab = $this->db->update('kons_tr_kasbon_project_lab', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_lab) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_subcont_tenaga_ahli = $this->db->update('kons_tr_kasbon_project_subcont_tenaga_ahli', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_subcont_tenaga_ahli) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $update_req_subcont_perusahaan = $this->db->update('kons_tr_kasbon_project_subcont_perusahaan', ['sts' => 1], ['id_header' => $id_kasbon]);
        if (!$update_req_subcont_perusahaan) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been approved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }
}
