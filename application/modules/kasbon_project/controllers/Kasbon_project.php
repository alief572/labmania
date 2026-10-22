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
class Kasbon_project extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Kasbon_Project.View';
    protected $addPermission      = 'Kasbon_Project.Add';
    protected $managePermission = 'Kasbon_Project.Manage';
    protected $deletePermission = 'Kasbon_Project.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Pengajuan');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Kasbon_project/Kasbon_project_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Pengajuan');
        $this->template->render('index');
    }

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*, b.nm_sales, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.sts', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_budgeting', $search['value'], 'both');
            $this->db->or_like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('c.nm_paket', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.create_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, b.nm_sales, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.sts', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_budgeting', $search['value'], 'both');
            $this->db->or_like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('c.nm_paket', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.create_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = (0 + $start);
        foreach ($get_data->result() as $item) {

            $this->db->select('a.id');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->group_start();
            $this->db->where('a.sts', '');
            $this->db->or_where('a.sts', null);
            $this->db->group_end();
            $get_req = $this->db->get();

            $total_budgeting = 0;

            $sql_total_budget = '
                SELECT
                    a.total_final as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_akomodasi a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    a.total_final as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_others a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    (a.mandays_subcont_final * a.mandays_rate_subcont_final) as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_aktifitas a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    a.total_final as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_lab a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    a.total_final as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_subcont_tenaga_ahli a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    a.total_final as total_subcont_perusahaan
                FROM
                    kons_tr_spk_budgeting_subcont_perusahaan a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
            ';

            $get_total_budget = $this->db->query($sql_total_budget)->result();
            foreach ($get_total_budget as $item_budget) {
                $total_budgeting += ($item_budget->total_akomodasi + $item_budget->total_others + $item_budget->total_subcont + $item_budget->total_lab + $item_budget->total_subcont_tenaga_ahli + $item_budget->total_subcont_perusahaan);
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }



            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_others_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon_others = $this->db->get()->result();

            foreach ($get_ovb_kasbon_others as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_lab_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon_lab = $this->db->get()->result();

            foreach ($get_ovb_kasbon_lab as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon_subcont_tenaga_ahli = $this->db->get()->result();

            foreach ($get_ovb_kasbon_subcont_tenaga_ahli as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon_subcont_perusahaan = $this->db->get()->result();

            foreach ($get_ovb_kasbon_subcont_perusahaan as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $total_kasbon = 0;

            $sql_total_kasbon = '
                SELECT
                    a.total_pengajuan as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL
                
                UNION ALL

                SELECT
                    0 as total_subcont, 
                    a.total_pengajuan as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_akomodasi a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    a.total_pengajuan as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_others a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    a.total_pengajuan as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_lab a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    a.total_pengajuan as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont_tenaga_ahli a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    a.total_pengajuan as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont_perusahaan a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL
            ';

            $get_total_kasbon  = $this->db->query($sql_total_kasbon)->result();
            foreach ($get_total_kasbon as $item_kasbon) {
                $total_kasbon += ($item_kasbon->total_subcont + $item_kasbon->total_akomodasi + $item_kasbon->total_others + $item_kasbon->total_lab + $item_kasbon->total_subcont_tenaga_ahli + $item_kasbon->total_subcont_perusahaan);
            }

            $total_kasbon_nd = 0;

            $sql_total_kasbon_nd = '
                SELECT
                    a.total_pengajuan as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL
                
                UNION ALL

                SELECT
                    0 as total_subcont, 
                    a.total_pengajuan as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_akomodasi a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    a.total_pengajuan as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_others a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    a.total_pengajuan as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_lab a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    a.total_pengajuan as total_subcont_tenaga_ahli,
                    0 as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont_tenaga_ahli a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli,
                    a.total_pengajuan as total_subcont_perusahaan
                FROM
                    kons_tr_kasbon_project_subcont_perusahaan a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '" AND
                    a.deleted_at IS NULL
            ';

            $get_total_kasbon_nd  = $this->db->query($sql_total_kasbon_nd)->result();
            foreach ($get_total_kasbon_nd as $item_kasbon_nd) {
                $total_kasbon_nd += ($item_kasbon_nd->total_subcont + $item_kasbon_nd->total_akomodasi + $item_kasbon_nd->total_others + $item_kasbon_nd->total_lab + $item_kasbon_nd->total_subcont_tenaga_ahli + $item_kasbon_nd->total_subcont_perusahaan);
            }

            // $this->db->select('a.budget_tambahan');
            // $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            // $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
            // $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);

            // $get_ovb_akomodasi_nd = $this->db->get()->result();

            // foreach ($get_ovb_akomodasi_nd as $item_ovb_akomodasi_nd) {
            //     $total_kasbon_nd += $item_ovb_akomodasi_nd->budget_tambahan;
            // }

            $valid_show = 1;
            // if ($get_req->num_rows() > 0) {
            //     $valid_show = 0;
            // }
            if ($total_kasbon > 0 || $total_kasbon_nd > 0) {
                if ($total_kasbon >= $total_budgeting) {
                    $valid_show = 0;
                }
            }
            // if ($total_kasbon >= $total_budgeting) {
            //     $valid_show = 0;
            // }

            // if ($valid_show == 1) {
            $no++;

            $status = '<span class="badge bg-yellow">Draft</span>';

            $this->db->select('a.id');
            $this->db->from('kons_tr_kasbon_project_header a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.deleted_at IS NULL');
            $this->db->where('a.sts', null);
            $count_waiting_detail = $this->db->get()->num_rows();

            if ($count_waiting_detail > 0) {
                $status = '<span class="badge bg-blue">Waiting Approval</span>';
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.sts', 0);
            $this->db->limit(1, 0);
            $get_req_kasbon = $this->db->get()->row();

            $reject_reason = '';
            if (!empty($get_req_kasbon)) {
                if ($get_req_kasbon->sts == '1' && $total_kasbon >= $total_budgeting) {
                    $status = '<span class="badge bg-green">Approved</span>';
                }
                if ($get_req_kasbon->sts == '2') {
                    $status = '<span class="badge bg-red">Rejected</span>';
                }
            }

            $option = '<a href="' . base_url('kasbon_project/view_kasbon/' . urlencode(str_replace('/', '|', $item->id_spk_budgeting))) . '" class="btn btn-sm btn-info" title="View Kasbon"><i class="fa fa-eye"></i></a>';

            $btn_edit = '<a href="' . base_url('kasbon_project/add_kasbon/' . urlencode(str_replace('/', '|', $item->id_spk_budgeting))) . '" class="btn btn-sm btn-primary" style="margin-left: 0.5rem;" title="Process Kasbon"><i class="fa fa-pencil"></i></a>';
            // if (!empty($get_req_kasbon)) {
            //     if ($get_req_kasbon->sts == '1' && $total_kasbon >= $total_budgeting) {
            //         $btn_edit = '';
            //     }
            //     if ($get_req_kasbon->sts == '2') {
            //         $btn_edit = '';
            //     }
            // }

            $btn_req_app = '';
            if ($total_kasbon_nd > 0) {
                $btn_req_app = '<button type="button" class="btn btn-sm btn-warning req_approval" data-id_spk_budgeting="' . $item->id_spk_budgeting . '" title="Request Approval" style="margin-left: 0.5rem;"><i class="fa fa-arrow-up"></i></button>';
            }
            // if (!empty($get_req_kasbon)) {
            //     if ($get_req_kasbon->sts == '1' && $total_kasbon >= $total_budgeting) {
            //         $btn_req_app = '';
            //     }
            //     if ($get_req_kasbon->sts == '2') {
            //         $btn_req_app = '';
            //     }
            // }

            // if (!empty($get_req_kasbon)) {
            //     if ($get_req_kasbon->sts == 0) {
            //         $status = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            //         $btn_req_app = '';
            //         $btn_edit = '';
            //     }
            // }

            $option .= $btn_edit;


            $spk_paket = '<span style="font-weight: 700; color: #333;">' . $item->id_spk_penawaran . '</span>';
            if (!empty($item->nm_paket)) {
                $spk_paket .= '<br><span class="text-muted" style="font-size: 12px;"><i class="fa fa-briefcase"></i> ' . $item->nm_paket . '</span>';
            }

            $pic_team = '<div><i class="fa fa-user-circle text-primary"></i> <b>PL:</b> ' . ucfirst($item->nm_project_leader) . '</div>';
            if (!empty($item->nm_sales)) {
                $pic_team .= '<div class="text-muted" style="font-size: 12px;"><i class="fa fa-user text-muted"></i> <b>Sales:</b> ' . ucfirst($item->nm_sales) . '</div>';
            }

            if (!empty($reject_reason)) {
                $status .= '<br><small class="text-danger"><i class="fa fa-info-circle"></i> ' . $reject_reason . '</small>';
            }

            $hasil[] = [
                'no' => $no,
                'spk_paket' => $spk_paket,
                'nm_customer' => $item->nm_customer,
                'pic_team' => $pic_team,
                'status' => $status,
                'option' => $option
            ];
            // }
        }

        $no_all = 0;
        foreach ($get_data_all->result() as $item) {
            $this->db->select('a.id');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->group_start();
            $this->db->where('a.sts', '');
            $this->db->or_where('a.sts', null);
            $this->db->group_end();
            $get_req = $this->db->get();

            $total_budgeting = 0;

            $sql_total_budget = '
                SELECT
                    a.total_final as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_spk_budgeting_akomodasi a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    a.total_final as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_spk_budgeting_others a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    (a.mandays_subcont_final * a.mandays_rate_subcont_final) as total_subcont,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_spk_budgeting_aktifitas a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    a.total_final as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_spk_budgeting_lab a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                 UNION ALL

                SELECT
                    0 as total_akomodasi, 
                    0 as total_others, 
                    0 as total_subcont,
                    0 as total_lab,
                    a.total_final as total_subcont_tenaga_ahli
                FROM
                    kons_tr_spk_budgeting_subcont_tenaga_ahli a
                WHERE
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
            ';

            $get_total_budget = $this->db->query($sql_total_budget)->result();
            foreach ($get_total_budget as $item_budget) {
                $total_budgeting += ($item_budget->total_akomodasi + $item_budget->total_others + $item_budget->total_subcont + $item_budget->total_lab + $item_budget->total_subcont_tenaga_ahli);
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_subcont_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_others_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_lab_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $this->db->select('a.budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('b.sts', 1);
            $get_ovb_kasbon = $this->db->get()->result();

            foreach ($get_ovb_kasbon as $item_ovb) {
                $total_budgeting += $item_ovb->budget_tambahan;
            }

            $total_kasbon = 0;

            $sql_total_kasbon = '
                SELECT
                    a.total_pengajuan as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_subcont a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
                
                UNION ALL

                SELECT
                    0 as total_subcont, 
                    a.total_pengajuan as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_akomodasi a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    a.total_pengajuan as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_others a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    a.total_pengajuan as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_lab a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    a.total_pengajuan as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_subcont_tenaga_ahli a
                WHERE
                    a.sts = "1" AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
            ';

            $get_total_kasbon  = $this->db->query($sql_total_kasbon)->result();
            foreach ($get_total_kasbon as $item_kasbon) {
                $total_kasbon += ($item_kasbon->total_subcont + $item_kasbon->total_akomodasi + $item_kasbon->total_others + $item_kasbon->total_lab + $item_kasbon->total_subcont_tenaga_ahli);
            }

            $total_kasbon_nd = 0;

            $sql_total_kasbon_nd = '
                SELECT
                    a.total_pengajuan as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_subcont a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
                
                UNION ALL

                SELECT
                    0 as total_subcont, 
                    a.total_pengajuan as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_akomodasi a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    a.total_pengajuan as total_others,
                    0 as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_others a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    a.total_pengajuan as total_lab,
                    0 as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_lab a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"

                UNION ALL

                SELECT
                    0 as total_subcont, 
                    0 as total_akomodasi, 
                    0 as total_others,
                    0 as total_lab,
                    a.total_pengajuan as total_subcont_tenaga_ahli
                FROM
                    kons_tr_kasbon_project_subcont_tenaga_ahli a
                WHERE
                    a.sts IS NULL AND
                    a.id_spk_budgeting = "' . $item->id_spk_budgeting . '"
            ';

            $get_total_kasbon_nd  = $this->db->query($sql_total_kasbon_nd)->result();
            foreach ($get_total_kasbon_nd as $item_kasbon_nd) {
                $total_kasbon_nd += ($item_kasbon_nd->total_subcont + $item_kasbon_nd->total_akomodasi + $item_kasbon_nd->total_others + $item_kasbon_nd->total_lab + $item_kasbon_nd->total_subcont_tenaga_ahli);
            }

            // $this->db->select('a.budget_tambahan');
            // $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            // $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
            // $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);

            // $get_ovb_akomodasi_nd = $this->db->get()->result();

            // foreach ($get_ovb_akomodasi_nd as $item_ovb_akomodasi_nd) {
            //     $total_kasbon_nd += $item_ovb_akomodasi_nd->budget_tambahan;
            // }

            $valid_show = 1;
            // if ($get_req->num_rows() > 0) {
            //     $valid_show = 0;
            // }
            if ($total_kasbon > 0 || $total_kasbon_nd > 0) {
                if ($total_kasbon >= $total_budgeting) {
                    $valid_show = 0;
                }
            }
            // if ($total_kasbon >= $total_budgeting) {
            //     $valid_show = 0;
            // }

            if ($valid_show == 1) {
                $no_all++;
            }
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $no_all,
            'recordsFiltered' => $no_all,
            'data' => $hasil
        ]);
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

    /**
     * Public wrapper so views (add/edit/view kasbon) can render the "tim asal" badge
     * exactly the same way the listing does. Returns only the outside-team badge HTML
     * (empty string if the user is part of the SPK team).
     */
    public function render_outside_team_badge($nm_pembuat, $employee_id, $spk_team_info)
    {
        $is_in_team = false;
        $emp_id = !empty($employee_id) ? trim((string)$employee_id) : '';
        if (!empty($emp_id) && !empty($spk_team_info['team_employee_ids']) && in_array($emp_id, $spk_team_info['team_employee_ids'])) {
            $is_in_team = true;
        } else if (empty($emp_id) && !empty($spk_team_info['team_names'])) {
            $pembuat_name = strtolower(trim($nm_pembuat));
            if (in_array($pembuat_name, $spk_team_info['team_names'])) {
                $is_in_team = true;
            }
        }

        if ($is_in_team) {
            return '';
        }

        $current_spk = !empty($spk_team_info['id_spk_penawaran']) ? $spk_team_info['id_spk_penawaran'] : '';
        $origin_spk = $this->_get_user_origin_spk($emp_id, $nm_pembuat, $current_spk);

        $tim_asal_text = '';
        if (!empty($origin_spk)) {
            $tim_asal_text = ' (tim asal: ' . htmlspecialchars($origin_spk) . ')';
        }

        return '<div class="tag-outside" style="margin-top: 5px;"><i class="fa fa-exclamation-triangle"></i> '
            . htmlspecialchars($nm_pembuat) . ' bukan bagian dari tim SPK ini' . $tim_asal_text . '</div>';
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

    public function get_data_kasbon_akomodasi()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 2);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_akomodasi = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 2);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_akomodasi_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_akomodasi_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_akomodasi->result() as $item) {
            $sts = '<div class="badge bg-yellow">Draft</div>';

            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_kasbon', $item->id);
            $check_req = $this->db->get()->row();

            $sts = '<div class="badge bg-yellow">Draft</div>';

            if (!empty($check_req)) {
                if ($check_req->sts == 0) {
                    $sts = '<div class="badge bg-blue">Waiting Approval</div>';
                }
            } else {
                $sts = '<div class="badge bg-blue">Waiting Approval</div>';
            }

            if ($item->sts == '1') {
                $sts = '<div class="badge bg-green">Approved</div>';
            }

            $reject_reason = !empty($item->reject_reason) ? trim($item->reject_reason) : '';
            if (empty($reject_reason) && !empty($check_req->reject_reason)) {
                $reject_reason = trim($check_req->reject_reason);
            }

            if (!empty($check_req) && $check_req->sts == 2) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            } else if ($item->sts_reject_manage == 1) {
                $sts = '<div class="badge bg-red">Rejected by Direktur</div>';
            } else if ($item->sts_reject == 1) {
                if (!empty($check_req) && $check_req->sts == 1) {
                    $sts = '<div class="badge bg-red">Rejected by Finance</div>';
                } else {
                    $sts = '<div class="badge bg-red">Rejected</div>';
                }
            } else if (!empty($reject_reason)) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            }

            $check_payment = $this->db->get_where('payment_approve', array('no_doc' => $item->id, 'status' => 2))->row();
            if (!empty($check_payment)) {
                $sts = '<button type="button" class="btn btn-sm btn-success">Paid</button>';
            }

            if (!empty($reject_reason)) {
                $sts .= '<div class="reject-note" style="font-size:11px; color:#e64949; margin-top:4px; display:flex; align-items:flex-start; gap:4px; max-width:200px; text-align:left; line-height:1.3;"><i class="fa fa-info-circle" style="margin-top:2px; flex-shrink:0;"></i> <span title="' . htmlspecialchars($reject_reason) . '">' . htmlspecialchars($reject_reason) . '</span></div>';
            }

            $tipe_pengajuan = '';
            if ($item->metode_pembayaran == '1') {
                $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
            }
            if ($item->metode_pembayaran == '2') {
                $tipe_pengajuan = '<div class="badge bg-green">Direct Payment</div>';
            }
            if ($item->metode_pembayaran == '3') {
                $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
            }

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                    <a href="' . base_url('kasbon_project/view_kasbon_akomodasi/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                        <div class="col-12 dropdown-item">
                        <b>
                            <i class="fa fa-eye"></i>
                        </b>
                        </div>
                    </a>
                    <span style="font-weight: 500"> View </span>
                </div>
            ';

            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_akomodasi" style="color: #000000" data-id="' . $item->id . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';

                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/edit_kasbon_akomodasi/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-pencil"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Edit </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'total' => number_format($item->grand_total, 2),
                'tipe' => $tipe_pengajuan,
                'request_by' => $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info),
                'status' => $sts,
                'date' => date('d F Y', strtotime($item->tgl)),
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_akomodasi_all->num_rows(),
            'recordsFiltered' => $get_kasbon_akomodasi_all->num_rows(),
            'data' => $hasil
        ]);
    }


    public function get_data_kasbon_others()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 3);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_others = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 3);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_others_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_others_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_others->result() as $item) {
            $sts = '<div class="badge bg-yellow">Draft</div>';

            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_kasbon', $item->id);
            $check_req = $this->db->get()->row();

            $sts = '<div class="badge bg-yellow">Draft</div>';

            if (!empty($check_req)) {
                if ($check_req->sts == 0) {
                    $sts = '<div class="badge bg-blue">Waiting Approval</div>';
                }
            } else {
                $sts = '<div class="badge bg-blue">Waiting Approval</div>';
            }

            if ($item->sts == '1') {
                $sts = '<div class="badge bg-green">Approved</div>';
            }

            $reject_reason = !empty($item->reject_reason) ? trim($item->reject_reason) : '';
            if (empty($reject_reason) && !empty($check_req->reject_reason)) {
                $reject_reason = trim($check_req->reject_reason);
            }

            if (!empty($check_req) && $check_req->sts == 2) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            } else if ($item->sts_reject_manage == 1) {
                $sts = '<div class="badge bg-red">Rejected by Direktur</div>';
            } else if ($item->sts_reject == 1) {
                if (!empty($check_req) && $check_req->sts == 1) {
                    $sts = '<div class="badge bg-red">Rejected by Finance</div>';
                } else {
                    $sts = '<div class="badge bg-red">Rejected</div>';
                }
            } else if (!empty($reject_reason)) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            }

            $check_payment = $this->db->get_where('payment_approve', array('no_doc' => $item->id, 'status' => 2))->row();
            if (!empty($check_payment)) {
                $sts = '<button type="button" class="btn btn-sm btn-success">Paid</button>';
            }

            if (!empty($reject_reason)) {
                $sts .= '<div class="reject-note" style="font-size:11px; color:#e64949; margin-top:4px; display:flex; align-items:flex-start; gap:4px; max-width:200px; text-align:left; line-height:1.3;"><i class="fa fa-info-circle" style="margin-top:2px; flex-shrink:0;"></i> <span title="' . htmlspecialchars($reject_reason) . '">' . htmlspecialchars($reject_reason) . '</span></div>';
            }

            $tipe_pengajuan = '';
            if ($item->metode_pembayaran == '1') {
                $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
            }
            if ($item->metode_pembayaran == '2') {
                $tipe_pengajuan = '<div class="badge bg-green">Direct Payment</div>';
            }
            if ($item->metode_pembayaran == '3') {
                $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
            }

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                    <a href="' . base_url('kasbon_project/view_kasbon_others/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                        <div class="col-12 dropdown-item">
                        <b>
                            <i class="fa fa-eye"></i>
                        </b>
                        </div>
                    </a>
                    <span style="font-weight: 500"> View </span>
                </div>
            ';

            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_others" style="color: #000000" data-id="' . $item->id . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';

                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/edit_kasbon_others/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-pencil"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Edit </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $item_date = (!empty($item->tgl)) ? $item->tgl : $item->created_date;

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'total' => number_format($item->grand_total, 2),
                'tipe' => $tipe_pengajuan,
                'request_by' => $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info),
                'status' => $sts,
                'date' => date('d F Y', strtotime($item_date)),
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_others_all->num_rows(),
            'recordsFiltered' => $get_kasbon_others_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_kasbon_lab()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 4);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_lab = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 4);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_lab_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_lab_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_lab->result() as $item) {
            $sts = '<div class="badge bg-yellow">Draft</div>';

            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_kasbon', $item->id);
            $check_req = $this->db->get()->row();

            $sts = '<div class="badge bg-yellow">Draft</div>';

            if (!empty($check_req)) {
                if ($check_req->sts == 0) {
                    $sts = '<div class="badge bg-blue">Waiting Approval</div>';
                }
            } else {
                $sts = '<div class="badge bg-blue">Waiting Approval</div>';
            }

            if ($item->sts == '1') {
                $sts = '<div class="badge bg-green">Approved</div>';
            }

            $reject_reason = !empty($item->reject_reason) ? trim($item->reject_reason) : '';
            if (empty($reject_reason) && !empty($check_req->reject_reason)) {
                $reject_reason = trim($check_req->reject_reason);
            }

            if (!empty($check_req) && $check_req->sts == 2) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            } else if ($item->sts_reject_manage == 1) {
                $sts = '<div class="badge bg-red">Rejected by Direktur</div>';
            } else if ($item->sts_reject == 1) {
                if (!empty($check_req) && $check_req->sts == 1) {
                    $sts = '<div class="badge bg-red">Rejected by Finance</div>';
                } else {
                    $sts = '<div class="badge bg-red">Rejected</div>';
                }
            } else if (!empty($reject_reason)) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            }

            $check_payment = $this->db->get_where('payment_approve', array('no_doc' => $item->id, 'status' => 2))->row();
            if (!empty($check_payment)) {
                $sts = '<button type="button" class="btn btn-sm btn-success">Paid</button>';
            }

            if (!empty($reject_reason)) {
                $sts .= '<div class="reject-note" style="font-size:11px; color:#e64949; margin-top:4px; display:flex; align-items:flex-start; gap:4px; max-width:200px; text-align:left; line-height:1.3;"><i class="fa fa-info-circle" style="margin-top:2px; flex-shrink:0;"></i> <span title="' . htmlspecialchars($reject_reason) . '">' . htmlspecialchars($reject_reason) . '</span></div>';
            }

            $tipe_pengajuan = '';
            if ($item->metode_pembayaran == '1') {
                $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
            }
            if ($item->metode_pembayaran == '2') {
                $tipe_pengajuan = '<div class="badge bg-green">Direct Payment</div>';
            }
            if ($item->metode_pembayaran == '3') {
                $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
            }

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                    <a href="' . base_url('kasbon_project/view_kasbon_lab/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                        <div class="col-12 dropdown-item">
                        <b>
                            <i class="fa fa-eye"></i>
                        </b>
                        </div>
                    </a>
                    <span style="font-weight: 500"> View </span>
                </div>
            ';

            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_lab" style="color: #000000" data-id="' . $item->id . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';

                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/edit_kasbon_lab/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-pencil"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Edit </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $item_date = (!empty($item->tgl)) ? $item->tgl : $item->created_date;

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'total' => number_format($item->grand_total, 2),
                'tipe' => $tipe_pengajuan,
                'request_by' => $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info),
                'status' => $sts,
                'date' => date('d F Y', strtotime($item_date)),
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_lab_all->num_rows(),
            'recordsFiltered' => $get_kasbon_lab_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_kasbon_subcont_tenaga_ahli()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 5);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_subcont_tenaga_ahli = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 5);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_subcont_tenaga_ahli_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_subcont_tenaga_ahli_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_subcont_tenaga_ahli->result() as $item) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_kasbon', $item->id);
            $check_req = $this->db->get()->row();

            $sts = '<div class="badge bg-yellow">Draft</div>';

            if (!empty($check_req)) {
                if ($check_req->sts == 0) {
                    $sts = '<div class="badge bg-blue">Waiting Approval</div>';
                }
            } else {
                $sts = '<div class="badge bg-blue">Waiting Approval</div>';
            }

            if ($item->sts == '1') {
                $sts = '<div class="badge bg-green">Approved</div>';
            }

            $reject_reason = !empty($item->reject_reason) ? trim($item->reject_reason) : '';
            if (empty($reject_reason) && !empty($check_req->reject_reason)) {
                $reject_reason = trim($check_req->reject_reason);
            }

            if (!empty($check_req) && $check_req->sts == 2) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            } else if ($item->sts_reject_manage == 1) {
                $sts = '<div class="badge bg-red">Rejected by Direktur</div>';
            } else if ($item->sts_reject == 1) {
                if (!empty($check_req) && $check_req->sts == 1) {
                    $sts = '<div class="badge bg-red">Rejected by Finance</div>';
                } else {
                    $sts = '<div class="badge bg-red">Rejected</div>';
                }
            } else if (!empty($reject_reason)) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            }

            $check_payment = $this->db->get_where('payment_approve', array('no_doc' => $item->id, 'status' => 2))->row();
            if (!empty($check_payment)) {
                $sts = '<button type="button" class="btn btn-sm btn-success">Paid</button>';
            }

            if (!empty($reject_reason)) {
                $sts .= '<div class="reject-note" style="font-size:11px; color:#e64949; margin-top:4px; display:flex; align-items:flex-start; gap:4px; max-width:200px; text-align:left; line-height:1.3;"><i class="fa fa-info-circle" style="margin-top:2px; flex-shrink:0;"></i> <span title="' . htmlspecialchars($reject_reason) . '">' . htmlspecialchars($reject_reason) . '</span></div>';
            }

            $tipe_pengajuan = '';
            if ($item->metode_pembayaran == '1') {
                $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
            }
            if ($item->metode_pembayaran == '2') {
                $tipe_pengajuan = '<div class="badge bg-green">Direct Payment</div>';
            }
            if ($item->metode_pembayaran == '3') {
                $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
            }

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                    <a href="' . base_url('kasbon_project/view_kasbon_subcont_tenaga_ahli/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                        <div class="col-12 dropdown-item">
                        <b>
                            <i class="fa fa-eye"></i>
                        </b>
                        </div>
                    </a>
                    <span style="font-weight: 500"> View </span>
                </div>
            ';

            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont_tenaga_ahli" style="color: #000000" data-id="' . $item->id . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';

                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont_tenaga_ahli/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-pencil"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Edit </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $item_date = (!empty($item->tgl)) ? $item->tgl : $item->created_date;

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'total' => number_format($item->grand_total, 2),
                'tipe' => $tipe_pengajuan,
                'request_by' => $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info),
                'status' => $sts,
                'date' => date('d F Y', strtotime($item_date)),
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_subcont_tenaga_ahli_all->num_rows(),
            'recordsFiltered' => $get_kasbon_subcont_tenaga_ahli_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_kasbon_subcont_perusahaan()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $spk_team_info = $this->_get_spk_team_data($id_spk_budgeting);

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 6);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_subcont_perusahaan = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat, b.employee_id');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 6);
        $this->db->where('a.deleted_at IS NULL');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_subcont_perusahaan_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_subcont_perusahaan_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_subcont_perusahaan->result() as $item) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_req_kasbon_project a');
            $this->db->where('a.id_kasbon', $item->id);
            $check_req = $this->db->get()->row();

            $sts = '<div class="badge bg-yellow">Draft</div>';

            if (!empty($check_req)) {
                if ($check_req->sts == 0) {
                    $sts = '<div class="badge bg-blue">Waiting Approval</div>';
                }
            } else {
                $sts = '<div class="badge bg-blue">Waiting Approval</div>';
            }

            if ($item->sts == '1') {
                $sts = '<div class="badge bg-green">Approved</div>';
            }

            $reject_reason = !empty($item->reject_reason) ? trim($item->reject_reason) : '';
            if (empty($reject_reason) && !empty($check_req->reject_reason)) {
                $reject_reason = trim($check_req->reject_reason);
            }

            if (!empty($check_req) && $check_req->sts == 2) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            } else if ($item->sts_reject_manage == 1) {
                $sts = '<div class="badge bg-red">Rejected by Direktur</div>';
            } else if ($item->sts_reject == 1) {
                if (!empty($check_req) && $check_req->sts == 1) {
                    $sts = '<div class="badge bg-red">Rejected by Finance</div>';
                } else {
                    $sts = '<div class="badge bg-red">Rejected</div>';
                }
            } else if (!empty($reject_reason)) {
                $sts = '<div class="badge bg-red">Rejected</div>';
            }

            $check_payment = $this->db->get_where('payment_approve', array('no_doc' => $item->id, 'status' => 2))->row();
            if (!empty($check_payment)) {
                $sts = '<button type="button" class="btn btn-sm btn-success">Paid</button>';
            }

            if (!empty($reject_reason)) {
                $sts .= '<div class="reject-note" style="font-size:11px; color:#e64949; margin-top:4px; display:flex; align-items:flex-start; gap:4px; max-width:200px; text-align:left; line-height:1.3;"><i class="fa fa-info-circle" style="margin-top:2px; flex-shrink:0;"></i> <span title="' . htmlspecialchars($reject_reason) . '">' . htmlspecialchars($reject_reason) . '</span></div>';
            }

            $tipe_pengajuan = '';
            if ($item->metode_pembayaran == '1') {
                $tipe_pengajuan = '<div class="badge bg-green">Kasbon</div>';
            }
            if ($item->metode_pembayaran == '2') {
                $tipe_pengajuan = '<div class="badge bg-green">Direct Payment</div>';
            }
            if ($item->metode_pembayaran == '3') {
                $tipe_pengajuan = '<div class="badge bg-red">PO</div>';
            }

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                    <a href="' . base_url('kasbon_project/view_kasbon_subcont_perusahaan/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                        <div class="col-12 dropdown-item">
                        <b>
                            <i class="fa fa-eye"></i>
                        </b>
                        </div>
                    </a>
                    <span style="font-weight: 500"> View </span>
                </div>
            ';

            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont_perusahaan" style="color: #000000" data-id="' . $item->id . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';

                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont_perusahaan/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-pencil"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Edit </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $item_date = (!empty($item->tgl)) ? $item->tgl : $item->created_date;

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'total' => number_format($item->grand_total, 2),
                'tipe' => $tipe_pengajuan,
                'request_by' => $this->_format_request_by_cell($item->nm_pembuat, $item->employee_id, $spk_team_info),
                'status' => $sts,
                'date' => date('d F Y', strtotime($item_date)),
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_subcont_perusahaan_all->num_rows(),
            'recordsFiltered' => $get_kasbon_subcont_perusahaan_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_akomodasi()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '2');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '2');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_akomodasi/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_akomodasi" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'nm_pembuat' => $item['nm_pembuat'],
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_others()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '3');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '3');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_others/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_others" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'nm_pembuat' => $item['nm_pembuat'],
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_lab()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '4');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '4');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_lab/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_lab" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'nm_pembuat' => $item['nm_pembuat'],
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_subcont_tenaga_ahli()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '5');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '5');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_subcont_tenaga_ahli/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_subcont_tenaga_ahli" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'nm_pembuat' => $item['nm_pembuat'],
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_subcont_perusahaan()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '6');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, IF(b.nm_lengkap IS NOT NULL, b.nm_lengkap, a.created_by) as nm_pembuat');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->join('users b', 'b.id_user = a.created_by', 'left');
        $this->db->where('a.tipe', '6');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->or_like('b.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_subcont_perusahaan/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_subcont_perusahaan" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'nm_pembuat' => $item['nm_pembuat'],
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_ovb_subcont()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_header a');
        $this->db->where('a.tipe', '1');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_header a');
        $this->db->where('a.tipe', '1');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_request_ovb', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_request_ovb');
        $this->db->order_by('a.created_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = ($start + 1);

        foreach ($get_data->result_array() as $item) {

            $this->db->select('IF(SUM(a.budget_tambahan * a.qty_budget_tambahan) IS NULL, 0, SUM(a.budget_tambahan * a.qty_budget_tambahan)) as amount');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
            $this->db->where('a.id_request_ovb', $item['id_request_ovb']);
            $get_amount = $this->db->get()->row_array();

            $option = '
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-accent text-primary dropdown-toggle"
                        title="Actions"
                        data-toggle="dropdown"
                        id="dropdownMenu' . $no . '"
                        aria-expanded="false">
                        <i class="fa fa-cogs"></i> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
            ';

            $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('kasbon_project/view_request_budget_subcont/' . urlencode(str_replace('/', '|', $item['id_request_ovb']))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

            if ($item['sts'] !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_ovb_subcont" style="color: #000000" data-id_request_ovb="' . $item['id_request_ovb'] . '">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-trash"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Delete </span>
                    </div>
                ';
            }

            $option .= '</div>';

            if ($view == 'view') {
                $option = '';
            }

            $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == '1') {
                $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == '2') {
                $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }


            $hasil[] = [
                'no' => $no,
                'id_request_ovb' => $item['id_request_ovb'],
                'amount' => number_format($get_amount['amount'], 2),
                'sts' => $sts,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function add_kasbon($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $budget_subcont = 0;
        $this->db->select('a.mandays_subcont_final, a.mandays_rate_subcont_final');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont = $this->db->get()->result();

        foreach ($get_budget_subcont as $item) {
            $budget_subcont += ($item->mandays_rate_subcont_final * $item->mandays_subcont_final);
        }

        $this->db->select('SUM(a.estimasi_total) as budget_subcont_custom');
        $this->db->from('kons_tr_kasbon_custom_ovb_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_custom = $this->db->get()->row();

        $budget_subcont += $get_budget_subcont_custom->budget_subcont_custom;

        $this->db->select('SUM(a.qty_budget_tambahan * a.budget_tambahan) as ovb_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('b.tipe', 1);
        $this->db->where('b.sts', 1);
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $get_overbudget_subcont = $this->db->get()->row();

        $budget_subcont += $get_overbudget_subcont->ovb_subcont;

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_ovb_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_subcont = $this->db->get()->row();

        $budget_subcont += $get_custom_subcont->ttl;

        $this->db->select('SUM(a.total_final) as budget_akomodasi');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_akomodasi = $this->db->get()->row();
        $budget_akomodasi = $get_budget_akomodasi->budget_akomodasi;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_akomodasi');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.tipe', 2);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_akomodasi = $this->db->get()->row();
        $budget_akomodasi += $get_budget_ovb_akomodasi->total_ovb_akomodasi;

        $this->db->select('SUM(a.estimasi_total) as ttl_custom');
        $this->db->from('kons_tr_kasbon_custom_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_custom_akomodasi = $this->db->get()->row();
        $budget_akomodasi += $get_budget_custom_akomodasi->ttl_custom;

        $this->db->select('SUM(a.total_final) as budget_others');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_others = $this->db->get()->row();
        $budget_others = $get_budget_others->budget_others;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_others');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.tipe', 3);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_others = $this->db->get()->row();

        $budget_others += $get_budget_ovb_others->total_ovb_others;

        $this->db->select('SUM(a.estimasi_total) as ttl_custom');
        $this->db->from('kons_tr_kasbon_custom_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_others = $this->db->get()->row();

        $budget_others += $get_custom_others->ttl_custom;

        $this->db->select('SUM(a.total_final) as budget_lab');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_lab = $this->db->get()->row();
        $budget_lab = $get_budget_lab->budget_lab;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_lab');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.tipe', 4);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_lab = $this->db->get()->row();

        $budget_lab += $get_budget_ovb_lab->total_ovb_lab;

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_lab = $this->db->get()->row();

        $budget_lab += $get_custom_lab->ttl;

        $this->db->select('SUM(a.total_final) as budget_subcont_tenaga_ahli');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_tenaga_ahli = $this->db->get()->row();
        $budget_subcont_tenaga_ahli = $get_budget_subcont_tenaga_ahli->budget_subcont_tenaga_ahli;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_subcont_tenaga_ahli');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.tipe', 5);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_subcont_tenaga_ahli = $this->db->get()->row();

        $budget_subcont_tenaga_ahli += $get_budget_ovb_subcont_tenaga_ahli->total_ovb_subcont_tenaga_ahli;

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_subcont_tenaga_ahli = $this->db->get()->row();

        $budget_subcont_tenaga_ahli += $get_custom_subcont_tenaga_ahli->ttl;

        $this->db->select('SUM(a.total_final) as budget_subcont_perusahaan');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_perusahaan = $this->db->get()->row();
        $budget_subcont_perusahaan = $get_budget_subcont_perusahaan->budget_subcont_perusahaan;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_subcont_perusahaan');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.tipe', 6);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_subcont_perusahaan = $this->db->get()->row();

        $budget_subcont_perusahaan += $get_budget_ovb_subcont_perusahaan->total_ovb_subcont_perusahaan;

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_subcont_perusahaan = $this->db->get()->row();

        $budget_subcont_perusahaan += $get_custom_subcont_perusahaan->ttl;

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont = $this->db->get()->result();

        $nilai_kasbon_aktual = 0;
        foreach ($get_kasbon_subcont as $item) {
            $nilai_kasbon_aktual += $item->total_pengajuan;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_akomodasi = $this->db->get()->result();

        $nilai_kasbon_aktual_akomodasi = 0;
        foreach ($get_kasbon_akomodasi as $item) {
            $nilai_kasbon_aktual_akomodasi += $item->total_pengajuan;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_others = $this->db->get()->result();

        $nilai_kasbon_aktual_others = 0;
        foreach ($get_kasbon_others as $item) {
            $nilai_kasbon_aktual_others += $item->total_pengajuan;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_lab = $this->db->get()->result();

        $nilai_kasbon_aktual_lab = 0;
        foreach ($get_kasbon_lab as $item) {
            $nilai_kasbon_aktual_lab += $item->total_pengajuan;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont_tenaga_ahli = $this->db->get()->result();

        $nilai_kasbon_aktual_subcont_tenaga_ahli = 0;
        foreach ($get_kasbon_subcont_tenaga_ahli as $item) {
            $nilai_kasbon_aktual_subcont_tenaga_ahli += $item->total_pengajuan;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont_perusahaan = $this->db->get()->result();

        $nilai_kasbon_aktual_subcont_perusahaan = 0;
        foreach ($get_kasbon_subcont_perusahaan as $item) {
            $nilai_kasbon_aktual_subcont_perusahaan += $item->total_pengajuan;
        }

        $data = [
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'budget_subcont' => $budget_subcont,
            'budget_akomodasi' => $budget_akomodasi,
            'budget_others' => $budget_others,
            'budget_lab' => $budget_lab,
            'budget_subcont_tenaga_ahli' => $budget_subcont_tenaga_ahli,
            'budget_subcont_perusahaan' => $budget_subcont_perusahaan,
            'list_kasbon_subcont' => $get_kasbon_subcont,
            'nilai_kasbon_aktual' => $nilai_kasbon_aktual,
            'nilai_kasbon_aktual_akomodasi' => $nilai_kasbon_aktual_akomodasi,
            'nilai_kasbon_aktual_others' => $nilai_kasbon_aktual_others,
            'nilai_kasbon_aktual_lab' => $nilai_kasbon_aktual_lab,
            'nilai_kasbon_aktual_subcont_tenaga_ahli' => $nilai_kasbon_aktual_subcont_tenaga_ahli,
            'nilai_kasbon_aktual_subcont_perusahaan' => $nilai_kasbon_aktual_subcont_perusahaan
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan');
        $this->template->render('add');
    }

    public function view_kasbon($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $budget_subcont = 0;
        $this->db->select('a.mandays_subcont_final, a.mandays_rate_subcont_final');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont = $this->db->get()->result();

        foreach ($get_budget_subcont as $item) {
            $budget_subcont += ($item->mandays_rate_subcont_final * $item->mandays_subcont_final);
        }

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('a.tipe', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_subcont = $this->db->get()->row();
        $budget_subcont += $get_budget_ovb_subcont->total_ovb_subcont;

        $this->db->select('SUM(a.total_final) as budget_akomodasi');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_akomodasi = $this->db->get()->row();
        $budget_akomodasi = $get_budget_akomodasi->budget_akomodasi;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_akomodasi');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('a.tipe', 2);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_akomodasi = $this->db->get()->row();
        $budget_akomodasi += $get_budget_ovb_akomodasi->total_ovb_akomodasi;

        $this->db->select('SUM(a.total_final) as budget_others');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_others = $this->db->get()->row();
        $budget_others = $get_budget_others->budget_others;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_others');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('a.tipe', 3);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_others = $this->db->get()->row();
        $budget_others += $get_budget_ovb_others->total_ovb_others;

        $this->db->select('SUM(a.total_final) as budget_lab');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_lab = $this->db->get()->row();
        $budget_lab = $get_budget_lab->budget_lab;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_lab');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('a.tipe', 4);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_lab = $this->db->get()->row();
        $budget_lab += $get_budget_ovb_lab->total_ovb_lab;

        $this->db->select('SUM(a.total_final) as budget_subcont_tenaga_ahli');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_tenaga_ahli = $this->db->get()->row();
        $budget_subcont_tenaga_ahli = $get_budget_subcont_tenaga_ahli->budget_subcont_tenaga_ahli;

        $this->db->select('SUM(b.budget_tambahan) as total_ovb_subcont_tenaga_ahli');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('a.tipe', 5);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_ovb_subcont_tenaga_ahli = $this->db->get()->row();
        $budget_subcont_tenaga_ahli += $get_budget_ovb_subcont_tenaga_ahli->total_ovb_subcont_tenaga_ahli;

        $this->db->select('SUM(a.total_final) as budget_subcont_perusahaan');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_perusahaan = $this->db->get()->row();
        $budget_subcont_perusahaan = $get_budget_subcont_perusahaan->budget_subcont_perusahaan;

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont = $this->db->get()->result();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_subcont as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->total_pengajuan;
            }
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_akomodasi = $this->db->get()->result();

        $nilai_kasbon_on_proses_akomodasi = 0;
        foreach ($get_kasbon_akomodasi as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses_akomodasi += $item->total_pengajuan;
            }
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_others = $this->db->get()->result();

        $nilai_kasbon_on_proses_others = 0;
        foreach ($get_kasbon_others as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses_others += $item->total_pengajuan;
            }
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_lab = $this->db->get()->result();

        $nilai_kasbon_on_proses_lab = 0;
        foreach ($get_kasbon_lab as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses_lab += $item->total_pengajuan;
            }
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont_tenaga_ahli = $this->db->get()->result();

        $nilai_kasbon_on_proses_subcont_tenaga_ahli = 0;
        foreach ($get_kasbon_subcont_tenaga_ahli as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses_subcont_tenaga_ahli += $item->total_pengajuan;
            }
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_by IS NULL');
        $get_kasbon_subcont_perusahaan = $this->db->get()->result();

        $nilai_kasbon_on_proses_subcont_perusahaan = 0;
        foreach ($get_kasbon_subcont_perusahaan as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses_subcont_perusahaan += $item->total_pengajuan;
            }
        }


        $data = [
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'budget_subcont' => $budget_subcont,
            'budget_akomodasi' => $budget_akomodasi,
            'budget_others' => $budget_others,
            'budget_lab' => $budget_lab,
            'budget_subcont_tenaga_ahli' => $budget_subcont_tenaga_ahli,
            'budget_subcont_perusahaan' => $budget_subcont_perusahaan,
            'list_kasbon_subcont' => $get_kasbon_subcont,
            'nilai_kasbon_on_proses' => $nilai_kasbon_on_proses,
            'nilai_kasbon_on_proses_akomodasi' => $nilai_kasbon_on_proses_akomodasi,
            'nilai_kasbon_on_proses_others' => $nilai_kasbon_on_proses_others,
            'nilai_kasbon_on_proses_lab' => $nilai_kasbon_on_proses_lab,
            'nilai_kasbon_on_proses_subcont_tenaga_ahli' => $nilai_kasbon_on_proses_subcont_tenaga_ahli,
            'nilai_kasbon_on_proses_subcont_perusahaan' => $nilai_kasbon_on_proses_subcont_perusahaan
        ];

        $this->template->set($data);
        $this->template->render('view');
    }

    public function view_request_budget_akomodasi($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_akomodasi');
    }

    public function view_request_budget_subcont($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_subcont');
    }

    public function view_request_budget_others($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_others');
    }

    public function view_request_budget_lab($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_lab');
    }

    public function view_request_budget_subcont_tenaga_ahli($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_subcont_tenaga_ahli');
    }

    public function view_request_budget_subcont_perusahaan($id_request_ovb)
    {
        $id_request_ovb = urldecode($id_request_ovb);
        $id_request_ovb = str_replace('|', '/', $id_request_ovb);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb_header = $this->db->get()->row_array();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_data_ovb_header['id_spk_budgeting']);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_request_ovb', $id_request_ovb);
        $get_data_ovb = $this->db->get()->result_array();

        $data = [
            'id_spk_budgeting' => $get_data_ovb_header['id_spk_budgeting'],
            'list_budgeting' => $get_budgeting,
            'list_data_ovb' => $get_data_ovb,
            'reject_reason' => $get_data_ovb_header['reject_reason'],
        ];

        $this->template->set($data);
        $this->template->render('view_request_budget_subcont_perusahaan');
    }

    public function add_kasbon_subcont($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.mandays_rate_subcont_final >', 0);
        $get_data_subcont = $this->db->get()->result();

        $this->db->select('a.id_aktifitas, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('a.id_aktifitas');
        $get_kasbon_subcont = $this->db->get()->result();

        $data_kasbon_subcont = [];
        foreach ($get_kasbon_subcont as $item) :
            $data_kasbon_subcont[$item->id_aktifitas] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        endforeach;

        $data_overbudget_subcont = [];

        $this->db->select('a.id_aktifitas, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $get_ovb_subcont = $this->db->get()->result();

        foreach ($get_ovb_subcont as $item_ovb_subcont) :
            $data_overbudget_subcont[$item_ovb_subcont->id_aktifitas] = [
                'qty_budget_tambahan' => $item_ovb_subcont->qty_budget_tambahan,
                'budget_tambahan' => $item_ovb_subcont->budget_tambahan,
                'pengajuan_budget' => $item_ovb_subcont->pengajuan_budget
            ];
        endforeach;

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_ovb_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_ovb_subcont_custom = $this->db->get()->result();

        $data_kasbon_custom = [];

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.custom_subcont', '1');
        $get_kasbon_custom = $this->db->get()->result();

        foreach ($get_kasbon_custom as $item) :
            $data_kasbon_custom[$item->id_aktifitas] = [
                'id_spk_budgeting' => $item->id_spk_budgeting,
                'id_spk_penawaran' => $item->id_spk_penawaran,
                'id_penawaran' => $item->id_penawaran,
                'qty_pengajuan' => $item->qty_pengajuan,
                'total_pengajuan' => $item->total_pengajuan
            ];
        endforeach;

        $data = [
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_subcont' => $get_data_subcont,
            'data_kasbon_subcont' => $data_kasbon_subcont,
            'data_overbudget_subcont' => $data_overbudget_subcont,
            'data_ovb_subcont_custom' => $get_ovb_subcont_custom,
            'data_kasbon_custom' => $data_kasbon_custom,
            'metode_pembayaran' => $metode_pembayaran
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont');
        $this->template->render('add_kasbon_subcont');
    }

    public function edit_kasbon_subcont($id_kasbon_subcont)
    {
        $id_kasbon_subcont = urldecode($id_kasbon_subcont);
        $id_kasbon_subcont = str_replace('|', '/', $id_kasbon_subcont);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_kasbon_subcont);
        $this->db->where('a.deleted_at IS NULL');
        $get_kasbon_subcont = $this->db->get()->row();

        if (empty($get_kasbon_subcont)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_kasbon_subcont->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $get_kasbon_subcont->id_spk_budgeting);
        $this->db->where('a.mandays_rate_subcont_final >', 0);
        $get_data_subcont = $this->db->get()->result();

        $this->db->select('a.id_aktifitas, a.qty_pengajuan, a.nominal_pengajuan, a.total_pengajuan, a.aktual_terpakai, a.sisa_budget, SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan, a.qty_estimasi, a.price_unit_estimasi, a.total_budget_estimasi, a.qty_terpakai, a.nominal_terpakai, a.total_terpakai, a.qty_overbudget, a.nominal_overbudget, a.total_overbudget');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $get_kasbon_subcont->id_spk_budgeting);
        $this->db->group_by('a.id_aktifitas');
        $get_kasbon_subcont2 = $this->db->get()->result();

        $data_kasbon_subcont = [];
        foreach ($get_kasbon_subcont2 as $item) {
            $data_kasbon_subcont[$item->id_aktifitas] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan,
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'qty_estimasi' => $item->qty_estimasi,
                'nominal_estimasi' => $item->price_unit_estimasi,
                'total_estimasi' => $item->total_budget_estimasi,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $this->db->select('a.id_header, a.id_spk_budgeting, a.id_spk_penawaran, a.id_penawaran, a.id_aktifitas, a.nm_aktifitas, a.qty_pengajuan, a.nominal_pengajuan, a.total_pengajuan, a.qty_estimasi, a.price_unit_estimasi, a.total_budget_estimasi, a.aktual_terpakai, a.sisa_budget');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_header', $id_kasbon_subcont);
        $this->db->where('a.custom_subcont', '1');
        $get_data_subcont_custom = $this->db->get()->result();

        $data = [
            'id_spk_budgeting' => $get_kasbon_subcont->id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_subcont' => $get_data_subcont,
            'data_kasbon_subcont' => $data_kasbon_subcont,
            'data_kasbon_subcont2' => $get_kasbon_subcont2,
            'data_kasbon_subcont_custom' => $get_data_subcont_custom,
            'header' => $get_kasbon_subcont
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont');
        $this->template->render('edit_kasbon_subcont');
    }

    public function view_kasbon_subcont($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $this->db->where('a.mandays_rate_subcont_final >', 0);
        $get_data_subcont = $this->db->get()->result();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_header', $id_header);
        $get_kasbon_subcont = $this->db->get()->result();

        // print_r($this->db->last_query());
        // exit;

        $data_list_kasbon_subcont = [];

        foreach ($get_kasbon_subcont as $item) {
            $data_list_kasbon_subcont[$item->id_aktifitas] = [
                'nm_aktifitas' => $item->nm_aktifitas,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'qty_pengajuan' => $item->qty_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'qty_estimasi' => $item->qty_estimasi,
                'price_unit_estimasi' => $item->price_unit_estimasi,
                'total_budgeting_estimasi' => $item->total_budget_estimasi,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $this->db->select('a.id, a.id_header, a.id_spk_budgeting, a.id_spk_penawaran, a.id_penawaran, a.qty_pengajuan, a.nominal_pengajuan, a.total_pengajuan, a.qty_estimasi, a.price_unit_estimasi, a.total_budget_estimasi, a.aktual_terpakai, a.sisa_budget, a.nm_aktifitas, a.id_aktifitas');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_header', $id_header);
        $this->db->where('a.custom_subcont', '1');
        $get_data_subcont_custom = $this->db->get()->result();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_subcont,
            'data_list_kasbon_subcont' => $data_list_kasbon_subcont,
            'data_list_kasbon_subcont_custom' => $get_data_subcont_custom
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont');
        $this->template->render('view_kasbon_subcont');
    }

    public function add_kasbon_akomodasi($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_akomodasi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_akomodasi_custom = $this->db->get()->result();

        $this->db->select('a.id_akomodasi ,SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_akomodasi');
        $get_kasbon_akomodasi = $this->db->get()->result();

        $data_kasbon_akomodasi = [];
        foreach ($get_kasbon_akomodasi as $item) {
            $data_kasbon_akomodasi[$item->id_akomodasi] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        }

        $this->db->select('b.id_detail ,b.id_item, SUM(b.budget_tambahan) as total_budget_tambahan, SUM(b.qty_budget_tambahan) as ttl_qty_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.tipe', 2);
        $this->db->where('a.sts', 1);
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->group_by('b.id');
        $get_ovb_akomodasi = $this->db->get()->result_array();

        $data_ovb_akomodasi = [];
        foreach ($get_ovb_akomodasi as $item) {
            $data_ovb_akomodasi[$item['id_detail']] = [
                'total_budget_tambahan' => $item['total_budget_tambahan'],
                'ttl_qty_tambahan' => $item['ttl_qty_tambahan']
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $current_user = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row();

        $data = [
            'metode_pembayaran' => $metode_pembayaran,
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_akomodasi' => $get_data_akomodasi,
            'list_akomodasi_custom' => $get_data_akomodasi_custom,
            'data_kasbon_akomodasi' => $data_kasbon_akomodasi,
            'data_ovb_akomodasi' => $data_ovb_akomodasi,
            'list_users' => $list_users,
            'current_user' => $current_user,
            'spk_team_info' => $this->_get_spk_team_data($id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Akomodasi');
        $this->template->render('add_kasbon_akomodasi');
    }

    public function view_kasbon_akomodasi($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $data_budget_tambahan = [];

        $this->db->select('a.id, a.id_item, SUM(a.budget_tambahan) as ttl_budget_tambahan, SUM(a.qty_budget_tambahan) as ttl_qty_budget_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('b.id_spk_budgeting', $get_header->id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $this->db->group_by('a.id');
        $get_data_ovb = $this->db->get()->result();

        foreach ($get_data_ovb as $item) {
            $data_budget_tambahan[$item->id] = [
                'budget_tambahan' => $item->ttl_budget_tambahan,
                'qty_budget_tambahan' => $item->ttl_qty_budget_tambahan
            ];
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.custom_akomodasi', 0);
        $this->db->where('a.id_header', $id_header);
        $get_kasbon_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.custom_akomodasi', 1);
        $this->db->where('a.id_header', $id_header);
        $get_kasbon_akomodasi_custom = $this->db->get()->result();

        // print_r($this->db->last_query());
        // exit;

        $data_list_kasbon_akomodasi = [];

        foreach ($get_kasbon_akomodasi as $item) {
            $data_list_kasbon_akomodasi[$item->id_akomodasi] = [
                'nm_biaya' => $item->nm_biaya,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'qty_pengajuan' => $item->qty_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'qty_estimasi' => $item->qty_estimasi,
                'price_unit_estimasi' => $item->price_unit_estimasi,
                'total_budgeting_estimasi' => $item->total_budget_estimasi,
                'qty_budget_tambahan' => $item->qty_budget_tambahan,
                'budget_tambahan' => $item->budget_tambahan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget
            ];
        }

        $data_list_kasbon_akomodasi_custom = [];

        foreach ($get_kasbon_akomodasi_custom as $item) {
            $data_list_kasbon_akomodasi_custom[$item->id_akomodasi] = [
                'nm_biaya' => $item->nm_item,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'qty_pengajuan' => $item->qty_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'qty_estimasi' => $item->qty_estimasi,
                'price_unit_estimasi' => $item->price_unit_estimasi,
                'total_budgeting_estimasi' => $item->total_budget_estimasi,
                'qty_budget_tambahan' => $item->qty_budget_tambahan,
                'budget_tambahan' => $item->budget_tambahan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget
            ];
        }

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($get_header->id_spk_budgeting);

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_akomodasi' => $get_data_akomodasi,
            'list_data_kasbon' => $get_kasbon_akomodasi,
            'list_data_kasbon_custom' => $get_kasbon_akomodasi_custom,
            'list_budget_tambahan' => $data_budget_tambahan,
            'data_list_kasbon_akomodasi' => $data_list_kasbon_akomodasi,
            'data_list_kasbon_akomodasi_custom' => $data_list_kasbon_akomodasi_custom,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Akomodasi');
        $this->template->render('view_kasbon_akomodasi');
    }

    public function edit_kasbon_akomodasi($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_header', $id_header);
        $get_data_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $data_budget_tambahan = [];

        $this->db->select('a.id, a.id_item, SUM(a.budget_tambahan) as ttl_budget_tambahan, SUM(a.qty_budget_tambahan) as ttl_qty_budget_tambahan');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_header b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('b.id_spk_budgeting', $get_header->id_spk_budgeting);
        $this->db->where('b.sts', '1');
        $this->db->group_by('a.id_item');
        $get_data_ovb = $this->db->get()->result();

        foreach ($get_data_ovb as $item) {
            $data_budget_tambahan[$item->id] = [
                'budget_tambahan' => $item->ttl_budget_tambahan,
                'qty_budget_tambahan' => $item->ttl_qty_budget_tambahan
            ];
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_budgeting_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.deleted_at IS NULL');
        $this->db->where('a.id_header', $id_header);
        $get_kasbon_akomodasi = $this->db->get()->result();

        // print_r($this->db->last_query());
        // exit;

        $data_list_kasbon_akomodasi = [];

        foreach ($get_kasbon_akomodasi as $item) {
            $data_list_kasbon_akomodasi[$item->id_akomodasi] = [
                'nm_biaya' => $item->nm_biaya,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'qty_pengajuan' => $item->qty_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'qty_estimasi' => $item->qty_estimasi,
                'price_unit_estimasi' => $item->price_unit_estimasi,
                'total_budgeting_estimasi' => $item->total_budget_estimasi,
                'qty_budget_tambahan' => $item->qty_budget_tambahan,
                'budget_tambahan' => $item->budget_tambahan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_akomodasi' => $get_data_budgeting_akomodasi,
            'list_data_kasbon' => $get_data_akomodasi,
            'list_budget_tambahan' => $data_budget_tambahan,
            'data_list_kasbon_akomodasi' => $data_list_kasbon_akomodasi,
            'list_users' => $list_users,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $this->_get_spk_team_data($get_header->id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Akomodasi');
        $this->template->render('edit_kasbon_akomodasi');
    }

    public function add_kasbon_others($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_others = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_others_custom = $this->db->get()->result();

        $this->db->select('a.id_others ,SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_others');
        $get_kasbon_others = $this->db->get()->result();

        $data_kasbon_others = [];
        foreach ($get_kasbon_others as $item) {
            $data_kasbon_others[$item->id_others] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        }

        $this->db->select('a.id_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_others');
        $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_others c', 'c.id = a.id_detail', 'left');
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

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $current_user = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row();

        $data = [
            'metode_pembayaran' => $metode_pembayaran,
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_others' => $get_data_others,
            'list_others_custom' => $get_data_others_custom,
            'data_kasbon_others' => $data_kasbon_others,
            'data_overbudget_others' => $data_overbudget_others,
            'list_users' => $list_users,
            'current_user' => $current_user,
            'spk_team_info' => $this->_get_spk_team_data($id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Others');
        $this->template->render('add_kasbon_others');
    }

    public function add_kasbon_lab($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_lab_custom = $this->db->get()->result();

        $this->db->select('a.id_lab ,SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_lab');
        $get_kasbon_lab = $this->db->get()->result();

        $data_kasbon_lab = [];
        foreach ($get_kasbon_lab as $item) {
            $data_kasbon_lab[$item->id_lab] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        }

        $this->db->select('a.id_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_lab');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_lab c', 'c.id = a.id_detail', 'left');
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

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $current_user = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row();

        $data = [
            'metode_pembayaran' => $metode_pembayaran,
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_lab' => $get_data_lab,
            'list_lab_custom' => $get_data_lab_custom,
            'data_kasbon_lab' => $data_kasbon_lab,
            'data_overbudget_lab' => $data_overbudget_lab,
            'list_users' => $list_users,
            'current_user' => $current_user,
            'spk_team_info' => $this->_get_spk_team_data($id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Lab');
        $this->template->render('add_kasbon_lab');
    }

    public function add_kasbon_subcont_tenaga_ahli($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_subcont_tenaga_ahli_custom = $this->db->get()->result();
        // print_r($this->db->last_query());
        // exit;

        $this->db->select('a.id_subcont ,SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_subcont');
        $get_kasbon_subcont_tenaga_ahli = $this->db->get()->result();

        $data_kasbon_subcont_tenaga_ahli = [];
        foreach ($get_kasbon_subcont_tenaga_ahli as $item) {
            $data_kasbon_subcont_tenaga_ahli[$item->id_subcont] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        }

        $this->db->select('a.id_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_subcont_tenaga_ahli c', 'c.id = a.id_detail', 'left');
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

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $current_user = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row();

        $data = [
            'metode_pembayaran' => $metode_pembayaran,
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_subcont_tenaga_ahli' => $get_data_subcont_tenaga_ahli,
            'list_subcont_tenaga_ahli_custom' => $get_data_subcont_tenaga_ahli_custom,
            'data_kasbon_subcont_tenaga_ahli' => $data_kasbon_subcont_tenaga_ahli,
            'data_overbudget_subcont_tenaga_ahli' => $data_overbudget_subcont_tenaga_ahli,
            'list_users' => $list_users,
            'current_user' => $current_user,
            'spk_team_info' => $this->_get_spk_team_data($id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Others');
        $this->template->render('add_kasbon_subcont_tenaga_ahli');
    }

    public function add_kasbon_subcont_perusahaan($id_spk_budgeting, $metode_pembayaran = 1)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_data_subcont_perusahaan_custom = $this->db->get()->result();
        // print_r($this->db->last_query());
        // exit;

        $this->db->select('a.id_subcont ,SUM(a.qty_pengajuan) as ttl_qty_pengajuan, SUM(a.total_pengajuan) as ttl_total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.deleted_at IS NULL');
        $this->db->group_by('a.id_subcont');
        $get_kasbon_subcont_perusahaan = $this->db->get()->result();

        $data_kasbon_subcont_perusahaan = [];
        foreach ($get_kasbon_subcont_perusahaan as $item) {
            $data_kasbon_subcont_perusahaan[$item->id_subcont] = [
                'ttl_qty_pengajuan' => $item->ttl_qty_pengajuan,
                'ttl_total_pengajuan' => $item->ttl_total_pengajuan
            ];
        }

        $this->db->select('a.id_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, c.id_subcont');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_header b', 'b.id_request_ovb = a.id_request_ovb', 'left');
        $this->db->join('kons_tr_spk_budgeting_subcont_perusahaan c', 'c.id = a.id_detail', 'left');
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

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $current_user = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row();

        $data = [
            'metode_pembayaran' => $metode_pembayaran,
            'id_spk_budgeting' => $id_spk_budgeting,
            'list_budgeting' => $get_budgeting,
            'list_subcont_perusahaan' => $get_data_subcont_perusahaan,
            'list_subcont_perusahaan_custom' => $get_data_subcont_perusahaan_custom,
            'data_kasbon_subcont_perusahaan' => $data_kasbon_subcont_perusahaan,
            'data_overbudget_subcont_perusahaan' => $data_overbudget_subcont_perusahaan,
            'list_users' => $list_users,
            'current_user' => $current_user,
            'spk_team_info' => $this->_get_spk_team_data($id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont Perusahaan');
        $this->template->render('add_kasbon_subcont_perusahaan');
    }

    public function view_kasbon_others($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_others = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_others a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_others_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_others] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($get_header->id_spk_budgeting);

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_others' => $get_data_others,
            'list_data_others_custom' => $get_data_others_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Others');
        $this->template->render('view_kasbon_others');
    }

    public function view_kasbon_lab($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_lab a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_lab_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_lab] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($get_header->id_spk_budgeting);

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_lab' => $get_data_lab,
            'list_data_lab_custom' => $get_data_lab_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Lab');
        $this->template->render('view_kasbon_lab');
    }

    public function view_kasbon_subcont_tenaga_ahli($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_tenaga_ahli_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_subcont] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($get_header->id_spk_budgeting);

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_subcont_tenaga_ahli' => $get_data_subcont_tenaga_ahli,
            'list_data_subcont_tenaga_ahli_custom' => $get_data_subcont_tenaga_ahli_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont Tenaga Ahli');
        $this->template->render('view_kasbon_subcont_tenaga_ahli');
    }

    public function view_kasbon_subcont_perusahaan($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_perusahaan_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_subcont] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $spk_team_info = $this->_get_spk_team_data($get_header->id_spk_budgeting);

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_subcont_perusahaan' => $get_data_subcont_perusahaan,
            'list_data_subcont_perusahaan_custom' => $get_data_subcont_perusahaan_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $spk_team_info
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont Perusahaan');
        $this->template->render('view_kasbon_subcont_perusahaan');
    }

    public function edit_kasbon_others($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_others = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_others a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_others_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.deleted_at IS NULL');
        $this->db->where('a.id_header', $id_header);
        // $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_others] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_others' => $get_data_others,
            'list_data_others_custom' => $get_data_others_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_users' => $list_users,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $this->_get_spk_team_data($get_header->id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Others');
        $this->template->render('edit_kasbon_others');
    }

    public function edit_kasbon_lab($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_lab a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_lab_custom = $this->db->get()->result();

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        // $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_lab] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_lab' => $get_data_lab,
            'list_data_lab_custom' => $get_data_lab_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_users' => $list_users,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $this->_get_spk_team_data($get_header->id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Lab');
        $this->template->render('edit_kasbon_lab');
    }

    public function edit_kasbon_subcont_tenaga_ahli($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_tenaga_ahli_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        // $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_subcont] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_subcont_tenaga_ahli' => $get_data_subcont_tenaga_ahli,
            'list_data_subcont_tenaga_ahli_custom' => $get_data_subcont_tenaga_ahli_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_users' => $list_users,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $this->_get_spk_team_data($get_header->id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont Tenaga Ahli');
        $this->template->render('edit_kasbon_subcont_tenaga_ahli');
    }

    public function edit_kasbon_subcont_perusahaan($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        $get_header = $this->db->get()->row();

        if (empty($get_header)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->select('a.*, b.nm_sales, b.id_sales, b.nm_konsultan_1, b.id_konsultan_1, b.nm_konsultan_2, b.id_konsultan_2, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $get_header->id_spk_budgeting);
        $get_data_subcont_perusahaan_custom = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya as nm_biaya');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_header', $id_header);
        $this->db->where('a.deleted_at IS NULL');
        // $this->db->group_by('a.id_item');
        $get_data_kasbon = $this->db->get()->result();

        $list_arr_kasbon = [];
        foreach ($get_data_kasbon as $item) {
            $list_arr_kasbon[$item->id_subcont] = [
                'qty_pengajuan' => $item->qty_pengajuan,
                'nominal_pengajuan' => $item->nominal_pengajuan,
                'total_pengajuan' => $item->total_pengajuan,
                'aktual_terpakai' => $item->aktual_terpakai,
                'sisa_budget' => $item->sisa_budget,
                'qty_terpakai' => $item->qty_terpakai,
                'nominal_terpakai' => $item->nominal_terpakai,
                'total_terpakai' => $item->total_terpakai,
                'qty_overbudget' => $item->qty_overbudget,
                'nominal_overbudget' => $item->nominal_overbudget,
                'total_overbudget' => $item->total_overbudget
            ];
        }

        $list_users = $this->db->select('id_user, nm_lengkap, username, employee_id')
            ->from('users')
            ->where('st_aktif', 1)
            ->where('deleted', 0)
            ->where('employee_id IS NOT NULL', null, false)
            ->where("TRIM(employee_id) !=", '')
            ->order_by('nm_lengkap', 'asc')
            ->get()->result();

        $list_bukti_penggunaan = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id_header_kasbon' => $id_header])->result();

        $creator_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();

        $data = [
            'header' => $get_header,
            'list_budgeting' => $get_budgeting,
            'list_data_kasbon' => $get_data_kasbon,
            'list_data_subcont_perusahaan' => $get_data_subcont_perusahaan,
            'list_data_subcont_perusahaan_custom' => $get_data_subcont_perusahaan_custom,
            'list_arr_kasbon' => $list_arr_kasbon,
            'list_users' => $list_users,
            'list_bukti_penggunaan' => $list_bukti_penggunaan,
            'creator_user' => $creator_user,
            'spk_team_info' => $this->_get_spk_team_data($get_header->id_spk_budgeting)
        ];

        $this->template->set($data);
        $this->template->title('Pengajuan Subcont Perusahaan');
        $this->template->render('edit_kasbon_subcont_perusahaan');
    }

    public function add_request_budget_akomodasi($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_akomodasi = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_akomodasi' => $get_list_kasbon_akomodasi
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_akomodasi');
    }

    private function _validate_mandatory_kasbon($post, $items_key = 'dt')
    {
        $empty_fields = [];
        $deskripsi = isset($post['deskripsi']) ? trim($post['deskripsi']) : '';
        $kasbon_bank = isset($post['kasbon_bank']) ? trim($post['kasbon_bank']) : '';
        $kasbon_bank_number = isset($post['kasbon_bank_number']) ? trim($post['kasbon_bank_number']) : '';
        $kasbon_bank_account = isset($post['kasbon_bank_account']) ? trim($post['kasbon_bank_account']) : '';

        if (empty($deskripsi)) {
            $empty_fields[] = 'Deskripsi / Keterangan';
        }
        if (empty($kasbon_bank)) {
            $empty_fields[] = 'Bank';
        }
        if (empty($kasbon_bank_number)) {
            $empty_fields[] = 'Bank Number (Nomor Rekening)';
        }
        if (empty($kasbon_bank_account)) {
            $empty_fields[] = 'Account Name (Nama Pemilik Rekening)';
        }

        $grand_total = 0;
        if (!empty($post[$items_key]) && is_array($post[$items_key])) {
            foreach ($post[$items_key] as $item) {
                if (isset($item['total_pengajuan'])) {
                    $grand_total += (float)(str_replace(',', '', $item['total_pengajuan']));
                }
            }
        }

        if ($grand_total <= 0) {
            $empty_fields[] = 'Item Pengajuan (minimal 1 item harus diisi nominal > 0)';
        }

        return $empty_fields;
    }

    public function save_kasbon_subcont()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = FALSE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        //     // If upload fails, display error
        //     $error = array('error' => $this->upload->display_errors());
        //     // print_r($error);
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }


        $this->db->trans_begin();

        $id_header = $this->Kasbon_project_model->generate_id_kasbon_project();

        $grand_total = 0;

        $data_insert_detail = [];

        $no = 1;
        if (isset($post['detail_subcont'])) {
            foreach ($post['detail_subcont'] as $item) {
                if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan']) > 0) {

                    $custom_subcont = (isset($item['custom_subcont'])) ? $item['custom_subcont'] : 0;

                    $data_insert_detail[] = [
                        'id_header' => $id_header,
                        'id_spk_budgeting' => $post['id_spk_budgeting'],
                        'id_spk_penawaran' => $post['id_spk_penawaran'],
                        'id_penawaran' => $post['id_penawaran'],
                        'id_aktifitas' => $item['id_aktifitas'],
                        'nm_aktifitas' => $item['nm_aktifitas'],
                        'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                        'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                        'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                        'qty_estimasi' => $item['qty_estimasi'],
                        'price_unit_estimasi' => $item['price_unit_estimasi'],
                        'total_budget_estimasi' => $item['total_estimasi'],
                        'aktual_terpakai' => $item['aktual_terpakai'],
                        'sisa_budget' => $item['sisa_budget'],
                        'qty_terpakai' => $item['qty_terpakai'],
                        'nominal_terpakai' => $item['nominal_terpakai'],
                        'total_terpakai' => $item['total_terpakai'],
                        'qty_overbudget' => $item['qty_overbudget'],
                        'nominal_overbudget' => $item['nominal_overbudget'],
                        'total_overbudget' => $item['total_overbudget'],
                        'custom_subcont' => $custom_subcont,
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];

                    $grand_total += (str_replace(',', '', $item['total_pengajuan']));

                    $no++;
                }
            }
        }

        // if (isset($post['subcont_custom'])) {
        //     foreach ($post['subcont_custom'] as $item) {
        //         if (str_replace(',', '', $item['qty_budget']) > 0 && str_replace(',', '', $item['nominal_budget'])) {

        //             $data_insert_detail[] = [
        //                 'id_header' => $id_header,
        //                 'id_spk_budgeting' => $post['id_spk_budgeting'],
        //                 'id_spk_penawaran' => $post['id_spk_penawaran'],
        //                 'id_penawaran' => $post['id_penawaran'],
        //                 'id_aktifitas' => $item['id'],
        //                 'nm_aktifitas' => $item['nm_item'],
        //                 'qty_pengajuan' => str_replace(',', '', $item['qty_budget']),
        //                 'nominal_pengajuan' => str_replace(',', '', $item['nominal_budget']),
        //                 'total_pengajuan' => str_replace(',', '', $item['total_budget']),
        //                 'qty_estimasi' => $item['estimasi_qty'],
        //                 'price_unit_estimasi' => $item['price_unit_estimasi'],
        //                 'total_budget_estimasi' => $item['total_estimasi'],
        //                 'aktual_terpakai' => $item['aktual_terpakai'],
        //                 'sisa_budget' => $item['sisa_budget'],
        //                 'qty_terpakai' => $item['qty_terpakai'],
        //                 'nominal_terpakai' => $item['nominal_terpakai'],
        //                 'total_terpakai' => $item['total_terpakai'],
        //                 'qty_overbudget' => $item['qty_overbudget'],
        //                 'nominal_overbudget' => $item['nominal_overbudget'],
        //                 'total_overbudget' => $item['total_overbudget'],
        //                 'created_by' => $this->auth->user_id(),
        //                 'created_date' => date('Y-m-d H:i:s'),
        //                 'custom_subcont' => 1
        //             ];

        //             $grand_total += (str_replace(',', '', $item['total_budget']));

        //             $no++;
        //         }
        //     }
        // }

        $data_insert_header = [
            'id' => $id_header,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 1,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_kasbon_header = $this->db->insert('kons_tr_kasbon_project_header', $data_insert_header);
        if (!$insert_kasbon_header) {
            $this->db->trans_rollback();
            print_r($this->db->error($insert_kasbon_header));
            exit;
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_subcont', $data_insert_detail);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($data_insert_detail);
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_subcont()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = FALSE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        // $upload_po = $post['dokument_link'];
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }

        $this->db->trans_begin();

        $reset_kasbon_subcont = $this->db->delete('kons_tr_kasbon_project_subcont', ['id_header' => $post['id']]);

        $update_header = $this->db->update('kons_tr_kasbon_project_header', [
            'sts_reject' => null,
            'reject_reason' => null,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ], [
            'id' => $post['id']
        ]);

        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $data_insert_detail = [];

        $total_subcont = 0;
        if (isset($post['detail_subcont'])) {
            foreach ($post['detail_subcont'] as $item) {
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $qty_estimasi = str_replace(',', '', $item['qty_estimasi']);
                $price_unit_estimasi = str_replace(',', '', $item['price_unit_estimasi']);
                $total_estimasi = str_replace(',', '', $item['total_budget_estimasi']);

                $custom_subcont = (isset($item['custom_subcont'])) ? $item['custom_subcont'] : 0;

                if ($qty_pengajuan > 0 && $nominal_pengajuan > 0) {
                    $data_insert_detail[] = [
                        'id_header' => $post['id'],
                        'id_spk_budgeting' => $post['id_spk_budgeting'],
                        'id_spk_penawaran' => $post['id_spk_penawaran'],
                        'id_penawaran' => $post['id_penawaran'],
                        'id_aktifitas' => $item['id_aktifitas'],
                        'nm_aktifitas' => $item['nm_aktifitas'],
                        'qty_pengajuan' => $qty_pengajuan,
                        'nominal_pengajuan' => $nominal_pengajuan,
                        'total_pengajuan' => $total_pengajuan,
                        'qty_estimasi' => $qty_estimasi,
                        'price_unit_estimasi' => $price_unit_estimasi,
                        'total_budget_estimasi' => $total_estimasi,
                        'aktual_terpakai' => $item['aktual_terpakai'],
                        'sisa_budget' => $item['sisa_budget'],
                        'qty_terpakai' => $item['qty_terpakai'],
                        'nominal_terpakai' => $item['price_unit_terpakai'],
                        'total_terpakai' => $item['total_budget_terpakai'],
                        'qty_overbudget' => $item['qty_overbudget'],
                        'nominal_overbudget' => $item['price_unit_overbudget'],
                        'total_overbudget' => $item['total_budget_overbudget'],
                        'custom_subcont' => $custom_subcont,
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];

                    $total_subcont += $total_pengajuan;
                }
            }
        }

        // if (isset($post['subcont_custom'])) :
        //     if (!empty($post['subcont_custom'])) :
        //         foreach ($post['subcont_custom'] as $item) :
        //             $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
        //             $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
        //             $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

        //             $qty_estimasi = str_replace(',', '', $item['qty_estimasi']);
        //             $price_unit_estimasi = str_replace(',', '', $item['price_unit_estimasi']);
        //             $total_estimasi = str_replace(',', '', $item['total_budget_estimasi']);

        //             if ($qty_pengajuan > 0 && $nominal_pengajuan > 0) {
        //                 $data_insert_detail[] = [
        //                     'id_header' => $post['id'],
        //                     'id_spk_budgeting' => $post['id_spk_budgeting'],
        //                     'id_spk_penawaran' => $post['id_spk_penawaran'],
        //                     'id_penawaran' => $post['id_penawaran'],
        //                     'id_aktifitas' => $item['id_aktifitas'],
        //                     'nm_aktifitas' => $item['nm_aktifitas'],
        //                     'qty_pengajuan' => $qty_pengajuan,
        //                     'nominal_pengajuan' => $nominal_pengajuan,
        //                     'total_pengajuan' => $total_pengajuan,
        //                     'qty_estimasi' => $qty_estimasi,
        //                     'price_unit_estimasi' => $price_unit_estimasi,
        //                     'total_budget_estimasi' => $total_estimasi,
        //                     'aktual_terpakai' => $item['aktual_terpakai'],
        //                     'sisa_budget' => $item['sisa_budget'],
        //                     'qty_terpakai' => $item['qty_terpakai'],
        //                     'nominal_terpakai' => $item['price_unit_terpakai'],
        //                     'total_terpakai' => $item['total_budget_terpakai'],
        //                     'qty_overbudget' => $item['qty_overbudget'],
        //                     'nominal_overbudget' => $item['price_unit_overbudget'],
        //                     'total_overbudget' => $item['total_budget_overbudget'],
        //                     'created_by' => $this->auth->user_id(),
        //                     'created_date' => date('Y-m-d H:i:s'),
        //                     'custom_subcont' => 1
        //                 ];

        //                 $total_subcont += $total_pengajuan;
        //             }
        //         endforeach;
        //     endif;
        // endif;

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_subcont', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }

            $update_header = $this->db->update('kons_tr_kasbon_project_header', array('grand_total' => $total_subcont), array('id' => $post['id']));
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been updated !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_kasbon_akomodasi()
    {
        $post = $this->input->post();
        if (empty($post)) {
            $json = file_get_contents('php://input');
            $post = json_decode($json, true);
        }

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_akomodasi');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = FALSE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        //     // If upload fails, display error
        //     $error = array('error' => $this->upload->display_errors());
        //     // print_r($error);
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }


        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['detail_akomodasi'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $created_by_user = (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id();

        $data_header = [
            'id' => $id,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 2,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'sts_req_payment' => '',
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $created_by_user,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_header = $this->db->insert('kons_tr_kasbon_project_header', $data_header);
        if (!$insert_header) {
            $this->db->trans_rollback();

            print($this->db->error($insert_header));
            exit;
        }

        $data_insert = [];

        $no = 1;
        foreach ($post['detail_akomodasi'] as $x => $item) {
            if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan'])) {
                $custom_akomodasi = (isset($item['custom_akomodasi'])) ? $item['custom_akomodasi'] : 0;
                $data_insert[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_akomodasi' => $item['id_akomodasi'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                    'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                    'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_estimasi'],
                    'qty_budget_tambahan' => $item['qty_budget_tambahan'],
                    'budget_tambahan' => $item['budget_tambahan'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['nominal_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_akomodasi' => $custom_akomodasi,
                    'created_by' => $created_by_user,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $no++;
            }
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_akomodasi', $data_insert);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($this->db->last_query());
            exit;
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_akomodasi()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'dt');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = FALSE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        //     // If upload fails, display error
        //     $error = array('error' => $this->upload->display_errors());
        //     // print_r($error);
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }


        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['dt'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $reset_kasbon_subcont = $this->db->delete('kons_tr_kasbon_project_akomodasi', ['id_header' => $post['id_header']]);

        $data_update_header = [
            'sts_reject' => null,
            'reject_reason' => null,
            'grand_total' => $grand_total,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($post['request_by'])) {
            $data_update_header['created_by'] = $post['request_by'];
        }

        $update_header = $this->db->update('kons_tr_kasbon_project_header', $data_update_header, [
            'id' => $post['id_header']
        ]);

        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        $data_insert_detail = [];

        if (isset($post['dt'])) {
            foreach ($post['dt'] as $item) {
                if (!isset($item['qty_pengajuan'])) {
                    continue;
                }
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $qty_estimasi = str_replace(',', '', $item['qty_estimasi']);
                $price_unit_estimasi = str_replace(',', '', $item['price_unit_estimasi']);
                $total_estimasi = str_replace(',', '', $item['total_estimasi']);


                if ($qty_pengajuan > 0 && $nominal_pengajuan > 0) {
                    $data_insert_detail[] = [
                        'id_header' => $post['id_header'],
                        'id_spk_budgeting' => $post['id_spk_budgeting'],
                        'id_spk_penawaran' => $post['id_spk_penawaran'],
                        'id_penawaran' => $post['id_penawaran'],
                        'id_akomodasi' => $item['id_akomodasi'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_pengajuan' => $qty_pengajuan,
                        'nominal_pengajuan' => $nominal_pengajuan,
                        'total_pengajuan' => $total_pengajuan,
                        'qty_estimasi' => $qty_estimasi,
                        'price_unit_estimasi' => $price_unit_estimasi,
                        'total_budget_estimasi' => $total_estimasi,
                        'budget_tambahan' => $item['budget_tambahan'],
                        'aktual_terpakai' => $item['aktual_terpakai'],
                        'sisa_budget' => $item['sisa_budget'],
                        'qty_terpakai' => $item['qty_terpakai'],
                        'nominal_terpakai' => $item['nominal_terpakai'],
                        'total_terpakai' => $item['total_terpakai'],
                        'qty_overbudget' => $item['qty_overbudget'],
                        'nominal_overbudget' => $item['nominal_overbudget'],
                        'total_overbudget' => $item['total_overbudget'],
                        'custom_akomodasi' => $item['custom_akomodasi'],
                        'created_by' => (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_akomodasi', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }

        $this->_upload_bukti_penggunaan($post['id_header']);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_kasbon_others()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_others');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        //     // If upload fails, display error
        //     $error = array('error' => $this->upload->display_errors());
        //     // print_r($error);
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }


        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['detail_others'] as $item) {
            $grand_total += (str_replace(',', '', $item['total_pengajuan']));
        }

        $created_by_user = (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id();

        $data_insert_header = [
            'id' => $id,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 3,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $created_by_user,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_header = $this->db->insert('kons_tr_kasbon_project_header', $data_insert_header);
        if (!$insert_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($insert_header));
            exit;
        }

        $data_insert = [];

        $no = 1;
        foreach ($post['detail_others'] as $item) {
            if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan'])) {

                $custom_others = (isset($item['custom_others'])) ? $item['custom_others'] : 0;

                $data_insert[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_others' => $item['id_others'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                    'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                    'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['price_unit_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_others' => $custom_others,
                    'created_by' => $created_by_user,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $no++;
            }
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_others', $data_insert);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($this->db->last_query());
            exit;
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_kasbon_lab()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_lab');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['detail_lab'] as $item) {
            $grand_total += (str_replace(',', '', $item['total_pengajuan']));
        }

        $created_by_user = (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id();

        $data_insert_header = [
            'id' => $id,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 4,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $created_by_user,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_header = $this->db->insert('kons_tr_kasbon_project_header', $data_insert_header);
        if (!$insert_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($insert_header));
            exit;
        }

        $data_insert = [];

        $no = 1;
        foreach ($post['detail_lab'] as $item) {
            if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan'])) {

                $custom_lab = (isset($item['custom_lab'])) ? $item['custom_lab'] : 0;

                $data_insert[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_lab' => $item['id_lab'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                    'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                    'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['price_unit_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_lab' => $custom_lab,
                    'created_by' => $created_by_user,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $no++;
            }
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_lab', $data_insert);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($this->db->last_query());
            exit;
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_kasbon_subcont_tenaga_ahli()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont_tenaga_ahli');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['detail_subcont_tenaga_ahli'] as $item) {
            $grand_total += (str_replace(',', '', $item['total_pengajuan']));
        }

        $created_by_user = (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id();

        $data_insert_header = [
            'id' => $id,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 5,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $created_by_user,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_header = $this->db->insert('kons_tr_kasbon_project_header', $data_insert_header);
        if (!$insert_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($insert_header));
            exit;
        }

        $data_insert = [];

        $no = 1;
        foreach ($post['detail_subcont_tenaga_ahli'] as $item) {
            if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan'])) {

                $custom_subcont_tenaga_ahli = (isset($item['custom_subcont_tenaga_ahli'])) ? $item['custom_subcont_tenaga_ahli'] : 0;

                $data_insert[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_subcont' => $item['id_subcont'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                    'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                    'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['price_unit_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_subcont_tenaga_ahli' => $custom_subcont_tenaga_ahli,
                    'created_by' => $created_by_user,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $no++;
            }
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_subcont_tenaga_ahli', $data_insert);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($this->db->last_query());
            exit;
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_kasbon_subcont_perusahaan()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont_perusahaan');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $this->db->trans_begin();

        $id = $this->Kasbon_project_model->generate_id_kasbon_project($post['metode_pembayaran']);

        $grand_total = 0;
        foreach ($post['detail_subcont_perusahaan'] as $item) {
            $grand_total += (str_replace(',', '', $item['total_pengajuan']));
        }

        $created_by_user = (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id();

        $data_insert_header = [
            'id' => $id,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 6,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'grand_total' => $grand_total,
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'metode_pembayaran' => $post['metode_pembayaran'],
            'created_by' => $created_by_user,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $insert_header = $this->db->insert('kons_tr_kasbon_project_header', $data_insert_header);
        if (!$insert_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($insert_header));
            exit;
        }

        $data_insert = [];

        $no = 1;
        foreach ($post['detail_subcont_perusahaan'] as $item) {
            if (str_replace(',', '', $item['qty_pengajuan']) > 0 && str_replace(',', '', $item['nominal_pengajuan'])) {
                $custom_subcont_perusahaan = (isset($item['custom_subcont_perusahaan'])) ? $item['custom_subcont_perusahaan'] : 0;

                $data_insert[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_subcont' => $item['id_subcont'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => str_replace(',', '', $item['qty_pengajuan']),
                    'nominal_pengajuan' => str_replace(',', '', $item['nominal_pengajuan']),
                    'total_pengajuan' => str_replace(',', '', $item['total_pengajuan']),
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['price_unit_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_subcont_perusahaan' => $custom_subcont_perusahaan,
                    'created_by' => $created_by_user,
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $no++;
            }
        }

        $insert_kasbon_subcont = $this->db->insert_batch('kons_tr_kasbon_project_subcont_perusahaan', $data_insert);
        if (!$insert_kasbon_subcont) {
            $this->db->trans_rollback();
            print_r($this->db->last_query());
            exit;
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_others()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_others');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        // $files = $_FILES['kasbon_document'];
        // $file_count = count($files['name']);

        // $_FILES['kasbon_document']['name'] = $files['name'];
        // $_FILES['kasbon_document']['type'] = $files['type'];
        // $_FILES['kasbon_document']['tmp_name'] = $files['tmp_name'];
        // $_FILES['kasbon_document']['error'] = $files['error'];
        // $_FILES['kasbon_document']['size'] = $files['size'];

        // if (!$this->upload->do_upload('kasbon_document')) {
        //     $upload_po = $post['dokument_link'];
        // } else {
        //     $data_upload_po = $this->upload->data();
        //     $upload_po = 'uploads/kasbon_project/' . $data_upload_po['file_name'];
        // }

        $grand_total = 0;
        foreach ($post['detail_others'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $this->db->trans_begin();

        $id = $post['id_header'];

        $this->db->delete('kons_tr_kasbon_project_others', ['id_header' => $id]);

        $data_update_header = [
            'sts_reject' => null,
            'reject_reason' => null,
            'grand_total' => $grand_total,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($post['request_by'])) {
            $data_update_header['created_by'] = $post['request_by'];
        }

        $data_insert_detail = [];

        if (isset($post['detail_others'])) {
            foreach ($post['detail_others'] as $item) {
                if (!isset($item['qty_pengajuan'])) {
                    continue;
                }
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $custom_others = (isset($item['custom_others'])) ? $item['custom_others'] : 0;

                $data_insert_detail[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_others' => $item['id_others'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => $qty_pengajuan,
                    'nominal_pengajuan' => $nominal_pengajuan,
                    'total_pengajuan' => $total_pengajuan,
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_budget_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['nominal_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_others' => $custom_others,
                    'created_by' => (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $update_header = $this->db->update('kons_tr_kasbon_project_header', $data_update_header, ['id' => $id]);
        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($update_header));
            exit;
        }

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_others', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->error($insert_detail));
                exit;
            }
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been updated !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_lab()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_lab');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $grand_total = 0;
        foreach ($post['detail_lab'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $this->db->trans_begin();

        $id = $post['id_header'];

        $this->db->delete('kons_tr_kasbon_project_lab', ['id_header' => $id]);

        $data_update_header = [
            'sts_reject' => null,
            'reject_reason' => null,
            'grand_total' => $grand_total,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($post['request_by'])) {
            $data_update_header['created_by'] = $post['request_by'];
        }

        $data_insert_detail = [];

        if (isset($post['detail_lab'])) {
            foreach ($post['detail_lab'] as $item) {
                if (!isset($item['qty_pengajuan'])) {
                    continue;
                }
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $custom_lab = (isset($item['custom_lab'])) ? $item['custom_lab'] : 0;

                $data_insert_detail[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_lab' => $item['id_lab'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => $qty_pengajuan,
                    'nominal_pengajuan' => $nominal_pengajuan,
                    'total_pengajuan' => $total_pengajuan,
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_budget_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['nominal_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_lab' => $custom_lab,
                    'created_by' => (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $update_header = $this->db->update('kons_tr_kasbon_project_header', $data_update_header, ['id' => $id]);
        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($update_header));
            exit;
        }

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_lab', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->error($insert_detail));
                exit;
            }
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been updated !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_subcont_tenaga_ahli()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont_tenaga_ahli');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $grand_total = 0;
        foreach ($post['detail_subcont_tenaga_ahli'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $this->db->trans_begin();

        $id = $post['id_header'];

        $this->db->delete('kons_tr_kasbon_project_subcont_tenaga_ahli', ['id_header' => $id]);

        $data_update_header = [
            'sts_reject' => null,
            'reject_reason' => null,
            'grand_total' => $grand_total,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($post['request_by'])) {
            $data_update_header['created_by'] = $post['request_by'];
        }

        $data_insert_detail = [];

        if (isset($post['detail_subcont_tenaga_ahli'])) {
            foreach ($post['detail_subcont_tenaga_ahli'] as $item) {
                if (!isset($item['qty_pengajuan'])) {
                    continue;
                }
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $custom_subcont_tenaga_ahli = (isset($item['custom_subcont_tenaga_ahli'])) ? $item['custom_subcont_tenaga_ahli'] : 0;

                $data_insert_detail[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_subcont' => $item['id_subcont'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => $qty_pengajuan,
                    'nominal_pengajuan' => $nominal_pengajuan,
                    'total_pengajuan' => $total_pengajuan,
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_budget_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['nominal_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_subcont_tenaga_ahli' => $custom_subcont_tenaga_ahli,
                    'created_by' => (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $update_header = $this->db->update('kons_tr_kasbon_project_header', $data_update_header, ['id' => $id]);
        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($update_header));
            exit;
        }

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_subcont_tenaga_ahli', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->error($insert_detail));
                exit;
            }
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been updated !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function update_kasbon_subcont_perusahaan()
    {
        $post = $this->input->post();

        $validation_errors = $this->_validate_mandatory_kasbon($post, 'detail_subcont_perusahaan');
        if (!empty($validation_errors)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Inputan mandatory belum lengkap: ' . implode(', ', $validation_errors)
            ]);
            return;
        }

        $config['upload_path'] = './uploads/kasbon_project/'; //path folder
        $config['allowed_types'] = 'gif|jpg|png|jpeg|bmp|pdf|webp'; //type yang dapat diakses bisa anda sesuaikan
        $config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
        $config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
        $config['remove_spaces'] = TRUE; // Remove spaces from the file name.

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        $upload_po = '';

        $grand_total = 0;
        foreach ($post['detail_subcont_perusahaan'] as $item) {
            if (isset($item['total_pengajuan'])) {
                $grand_total += (str_replace(',', '', $item['total_pengajuan']));
            }
        }

        $this->db->trans_begin();

        $id = $post['id_header'];

        $this->db->delete('kons_tr_kasbon_project_subcont_perusahaan', ['id_header' => $id]);

        $data_update_header = [
            'sts_reject' => null,
            'reject_reason' => null,
            'grand_total' => $grand_total,
            'deskripsi' => $post['deskripsi'],
            'tgl' => $post['tgl'],
            'dokument_link' => $upload_po,
            'bank' => $post['kasbon_bank'],
            'bank_number' => $post['kasbon_bank_number'],
            'bank_account' => $post['kasbon_bank_account'],
            'updated_by' => $this->auth->user_id(),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        if (!empty($post['request_by'])) {
            $data_update_header['created_by'] = $post['request_by'];
        }

        $data_insert_detail = [];

        if (isset($post['detail_subcont_perusahaan'])) {
            foreach ($post['detail_subcont_perusahaan'] as $item) {
                if (!isset($item['qty_pengajuan'])) {
                    continue;
                }
                $qty_pengajuan = str_replace(',', '', $item['qty_pengajuan']);
                $nominal_pengajuan = str_replace(',', '', $item['nominal_pengajuan']);
                $total_pengajuan = str_replace(',', '', $item['total_pengajuan']);

                $custom_subcont_perusahaan = (isset($item['custom_subcont_perusahaan'])) ? $item['custom_subcont_perusahaan'] : 0;

                $data_insert_detail[] = [
                    'id_header' => $id,
                    'id_spk_budgeting' => $post['id_spk_budgeting'],
                    'id_spk_penawaran' => $post['id_spk_penawaran'],
                    'id_penawaran' => $post['id_penawaran'],
                    'id_subcont' => $item['id_subcont'],
                    'id_item' => $item['id_item'],
                    'nm_item' => $item['nm_item'],
                    'qty_pengajuan' => $qty_pengajuan,
                    'nominal_pengajuan' => $nominal_pengajuan,
                    'total_pengajuan' => $total_pengajuan,
                    'qty_estimasi' => $item['qty_estimasi'],
                    'price_unit_estimasi' => $item['price_unit_estimasi'],
                    'total_budget_estimasi' => $item['total_budget_estimasi'],
                    'aktual_terpakai' => $item['aktual_terpakai'],
                    'sisa_budget' => $item['sisa_budget'],
                    'qty_terpakai' => $item['qty_terpakai'],
                    'nominal_terpakai' => $item['nominal_terpakai'],
                    'total_terpakai' => $item['total_terpakai'],
                    'qty_overbudget' => $item['qty_overbudget'],
                    'nominal_overbudget' => $item['nominal_overbudget'],
                    'total_overbudget' => $item['total_overbudget'],
                    'custom_subcont_perusahaan' => $custom_subcont_perusahaan,
                    'created_by' => (!empty($post['request_by'])) ? $post['request_by'] : $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $update_header = $this->db->update('kons_tr_kasbon_project_header', $data_update_header, ['id' => $id]);
        if (!$update_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($update_header));
            exit;
        }

        if (!empty($data_insert_detail)) {
            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_project_subcont_perusahaan', $data_insert_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r($this->db->error($insert_detail));
                exit;
            }
        }

        $this->_upload_bukti_penggunaan($id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been updated !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_kasbon_subcont()
    {
        $id = $this->input->post('id');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_subcont', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function del_kasbon_akomodasi()
    {
        $id = $this->input->post('id');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_akomodasi', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function del_kasbon_others()
    {
        $id = $this->input->post('id_kasbon_others');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_others', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function del_kasbon_lab()
    {
        $id = $this->input->post('id_kasbon_lab');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_lab', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function del_kasbon_subcont_tenaga_ahli()
    {
        $id = $this->input->post('id_kasbon_subcont_tenaga_ahli');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_subcont_tenaga_ahli', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function del_kasbon_subcont_perusahaan()
    {
        $id = $this->input->post('id_kasbon_subcont_perusahaan');
        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Validate record exists and not already deleted
        $record = $this->db->get_where('kons_tr_kasbon_project_header', [
            'id' => $id,
            'deleted_at' => NULL
        ])->row();

        if (empty($record)) {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
            return;
        }

        $this->db->trans_start();

        // Soft delete header
        $this->db->where('id', $id);
        $this->db->update('kons_tr_kasbon_project_header', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        // Soft delete detail
        $this->db->where('id_header', $id);
        $this->db->update('kons_tr_kasbon_project_subcont_perusahaan', [
            'deleted_at' => $now,
            'deleted_by' => $user_id
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode(['status' => $valid, 'pesan' => $pesan]);
    }

    public function paid_kasbon_subcont()
    {
        $id_kasbon_subcont = $this->input->post('id_kasbon_subcont');

        $this->db->trans_begin();

        $this->db->update('kons_tr_kasbon_project_subcont', ['sts' => 1], ['id_kasbon_subcont' => $id_kasbon_subcont]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been paid !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function paid_kasbon_akomodasi()
    {
        $id_kasbon_akomodasi = $this->input->post('id_kasbon_akomodasi');

        $this->db->trans_begin();

        $this->db->update('kons_tr_kasbon_project_akomodasi', ['sts' => 1], ['id_kasbon_akomodasi' => $id_kasbon_akomodasi]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been paid !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function paid_kasbon_others()
    {
        $id_kasbon_others = $this->input->post('id_kasbon_others');

        $this->db->trans_begin();

        $this->db->update('kons_tr_kasbon_project_others', ['sts' => 1], ['id_kasbon_others' => $id_kasbon_others]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been paid !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function hitung_all_budget_on_process()
    {
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');

        $nilai_budget_subcont = 0;
        $nilai_budget_akomodasi = 0;
        $nilai_budget_others = 0;
        $nilai_budget_lab = 0;
        $nilai_budget_subcont_tenaga_ahli = 0;
        $nilai_budget_subcont_perusahaan = 0;

        $this->db->select('a.total_aktifitas_final');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_subcont = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_ovb_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_subcont_custom = $this->db->get()->row();

        $this->db->select('a.total_final');
        $this->db->from('kons_tr_spk_budgeting_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_akomodasi = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_akomodasi_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_akomodasi = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_akomodasi_custom = $this->db->get()->row();

        $this->db->select('a.total_final');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_others = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_others_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_others_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_others = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_others = $this->db->get()->row();

        $this->db->select('a.total_final');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_lab = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_lab_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_lab_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_lab = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_lab = $this->db->get()->row();

        $this->db->select('a.total_final');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_subcont_tenaga_ahli = $this->db->get()->row();

        $this->db->select('a.total_final');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_budget_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('(b.qty_budget_tambahan * b.budget_tambahan) as ttl');
        $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_header a');
        $this->db->join('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail b', 'b.id_request_ovb = a.id_request_ovb');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', 1);
        $get_ovb_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('SUM(a.estimasi_total) as ttl');
        $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_custom_subcont_perusahaan = $this->db->get()->row();

        foreach ($get_budget_subcont as $item_subcont) :
            $nilai_budget_subcont += $item_subcont->total_aktifitas_final;
        endforeach;

        foreach ($get_ovb_subcont as $item_ovb_subcont) {
            $nilai_budget_subcont += $item_ovb_subcont->ttl;
        }

        $nilai_budget_subcont += $get_subcont_custom->ttl;

        foreach ($get_budget_akomodasi as $item_akomodasi) :
            $nilai_budget_akomodasi += $item_akomodasi->total_final;
        endforeach;

        foreach ($get_ovb_akomodasi as $item_ovb_akomodasi) {
            $nilai_budget_akomodasi += $item_ovb_akomodasi->ttl;
        }

        $nilai_budget_akomodasi += $get_akomodasi_custom->ttl;

        foreach ($get_budget_others as $item_others) :
            $nilai_budget_others += $item_others->total_final;
        endforeach;

        foreach ($get_ovb_others as $item_ovb_others) {
            $nilai_budget_others += $item_ovb_others->ttl;
        }

        $nilai_budget_others += $get_custom_others->ttl;

        foreach ($get_budget_lab as $item_lab) :
            $nilai_budget_lab += $item_lab->total_final;
        endforeach;

        foreach ($get_ovb_lab as $item_ovb_lab) {
            $nilai_budget_lab += $item_ovb_lab->ttl;
        }

        $nilai_budget_lab += $get_custom_lab->ttl;

        foreach ($get_budget_subcont_tenaga_ahli as $item_subcont_tenaga_ahli) :
            $nilai_budget_subcont_tenaga_ahli += $item_subcont_tenaga_ahli->total_final;
        endforeach;

        foreach ($get_ovb_subcont_tenaga_ahli as $item_ovb_subcont_tenaga_ahli) {
            $nilai_budget_subcont_tenaga_ahli += $item_ovb_subcont_tenaga_ahli->ttl;
        }

        $nilai_budget_subcont_tenaga_ahli += $get_custom_subcont_tenaga_ahli->ttl;

        foreach ($get_budget_subcont_perusahaan as $item_subcont_perusahaan) :
            $nilai_budget_subcont_perusahaan += $item_subcont_perusahaan->total_final;
        endforeach;

        foreach ($get_ovb_subcont_perusahaan as $item_ovb_subcont_perusahaan) {
            $nilai_budget_subcont_perusahaan += $item_ovb_subcont_perusahaan->ttl;
        }

        $nilai_budget_subcont_perusahaan += $get_custom_subcont_perusahaan->ttl;

        $nilai_budget_subcont_on_process = 0;
        $nilai_budget_akomodasi_on_process = 0;
        $nilai_budget_others_on_process = 0;
        $nilai_budget_lab_on_process = 0;
        $nilai_budget_subcont_tenaga_ahli_on_process = 0;
        $nilai_budget_subcont_perusahaan_on_process = 0;

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_subcont_on_process = $this->db->get()->result();

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_akomodasi a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_akomodasi_on_process = $this->db->get()->result();

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_others a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_others_on_process = $this->db->get()->result();

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_lab a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_lab_on_process = $this->db->get()->result();

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_subcont_tenaga_ahli_on_process = $this->db->get()->result();

        $this->db->select('a.total_pengajuan');
        $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_nilai_budget_subcont_perusahaan_on_process = $this->db->get()->result();

        foreach ($get_nilai_budget_subcont_on_process as $item) {
            $nilai_budget_subcont_on_process += $item->total_pengajuan;
        }
        foreach ($get_nilai_budget_akomodasi_on_process as $item) {
            $nilai_budget_akomodasi_on_process += $item->total_pengajuan;
        }
        foreach ($get_nilai_budget_others_on_process as $item) {
            $nilai_budget_others_on_process += $item->total_pengajuan;
        }
        foreach ($get_nilai_budget_lab_on_process as $item) {
            $nilai_budget_lab_on_process += $item->total_pengajuan;
        }
        foreach ($get_nilai_budget_subcont_tenaga_ahli_on_process as $item) {
            $nilai_budget_subcont_tenaga_ahli_on_process += $item->total_pengajuan;
        }
        foreach ($get_nilai_budget_subcont_perusahaan_on_process as $item) {
            $nilai_budget_subcont_perusahaan_on_process += $item->total_pengajuan;
        }

        echo json_encode([
            'nilai_budget_subcont' => $nilai_budget_subcont,
            'nilai_budget_akomodasi' => $nilai_budget_akomodasi,
            'nilai_budget_others' => $nilai_budget_others,
            'nilai_budget_lab' => $nilai_budget_lab,
            'nilai_budget_subcont_tenaga_ahli' => $nilai_budget_subcont_tenaga_ahli,
            'nilai_budget_subcont_perusahaan' => $nilai_budget_subcont_perusahaan,
            'nilai_budget_subcont_aktual' => $nilai_budget_subcont_on_process,
            'nilai_budget_akomodasi_aktual' => $nilai_budget_akomodasi_on_process,
            'nilai_budget_others_aktual' => $nilai_budget_others_on_process,
            'nilai_budget_lab_aktual' => $nilai_budget_lab_on_process,
            'nilai_budget_subcont_tenaga_ahli_aktual' => $nilai_budget_subcont_tenaga_ahli_on_process,
            'nilai_budget_subcont_perusahaan_aktual' => $nilai_budget_subcont_perusahaan_on_process
        ]);
    }

    public function save_request_budget_akomodasi()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_akomodasi();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 2,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_akomodasi'])) {
            foreach ($post['req_akomodasi'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_akomodasi_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->error($insert_header));
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_akomodasi_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_ovb_akomodasi()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $del_request_ovb_header = $this->db->delete('kons_tr_kasbon_req_ovb_akomodasi_header', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_header));
            exit;
        }

        $del_request_ovb_detail = $this->db->delete('kons_tr_kasbon_req_ovb_akomodasi_detail', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_detail) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_detail));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_ovb_subcont()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $del_request_ovb_header = $this->db->delete('kons_tr_kasbon_req_ovb_subcont_header', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_header));
            exit;
        }

        $del_request_ovb_detail = $this->db->delete('kons_tr_kasbon_req_ovb_subcont_detail', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_detail) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_detail));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_ovb_others()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $del_request_ovb_header = $this->db->delete('kons_tr_kasbon_req_ovb_others_header', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_header));
            exit;
        }

        $del_request_ovb_detail = $this->db->delete('kons_tr_kasbon_req_ovb_others_detail', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_detail) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_detail));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_ovb_lab()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $del_request_ovb_header = $this->db->delete('kons_tr_kasbon_req_ovb_lab_header', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_header));
            exit;
        }

        $del_request_ovb_detail = $this->db->delete('kons_tr_kasbon_req_ovb_lab_detail', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_detail) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_detail));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_ovb_subcont_tenaga_ahli()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $del_request_ovb_header = $this->db->delete('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_header));
            exit;
        }

        $del_request_ovb_detail = $this->db->delete('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail', ['id_request_ovb' => $id_request_ovb]);
        if (!$del_request_ovb_detail) {
            $this->db->trans_rollback();

            print_r($this->db->error($del_request_ovb_detail));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function approval_req_ovb()
    {
        $id_request_ovb = $this->input->post('id_request_ovb');

        $this->db->trans_begin();

        $app_request_ovb_header = $this->db->update('kons_tr_kasbon_req_ovb_akomodasi_header', ['sts' => 1], ['id_request_ovb' => $id_request_ovb]);
        if (!$app_request_ovb_header) {
            $this->db->trans_rollback();

            print_r($this->db->error($app_request_ovb_header));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function req_approval_kasbon()
    {
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');

        $this->db->trans_begin();

        $data_insert = [];

        $this->db->select('a.id as id_header');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.sts', null);
        $get_kasbon_header = $this->db->get()->result();

        foreach ($get_kasbon_header as $item) {
            $data_insert[] = [
                'id_spk_budgeting' => $id_spk_budgeting,
                'id_kasbon' => $item->id_header,
                'created_by' => $this->auth->user_id(),
                'created_date' => date('Y-m-d H:i:s')
            ];
        }

        $insert_req = $this->db->insert_batch('kons_tr_req_kasbon_project', $data_insert);
        if (!$insert_req) {
            $this->db->trans_rollback();

            print_r($this->db->error($insert_req));
            exit;
        }

        $update_kasbon_header = $this->db->update('kons_tr_kasbon_project_header', ['sts_reject' => null, 'sts_reject_manage' => null, 'reject_reason' => null], ['id_spk_budgeting' => $id_spk_budgeting, 'sts' => null]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been requested approval !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function req_approve_kasbon()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $update_req = $this->db->update('kons_tr_kasbon_project_header', ['sts_req' => 1], ['id' => $post['id']]);
        if (!$update_req) {
            $this->db->trans_rollback();

            print_r($this->db->error($update_req));
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please, try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been moved to Waiting Approval !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function add_request_budget_subcont($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_budgeting_aktifitas a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_subcont = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_subcont' => $get_list_kasbon_subcont
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_subcont');
    }

    public function save_request_budget_subcont()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_subcont();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 1,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_subcont'])) {
            foreach ($post['req_subcont'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_aktifitas' => $item['id_aktifitas'],
                        'nm_aktifitas' => $item['nm_aktifitas'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_subcont_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->last_query());
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_subcont_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function add_request_budget_others($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_others = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_others' => $get_list_kasbon_others
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_others');
    }

    public function add_request_budget_lab($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_lab a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_lab = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_akomodasi' => $get_list_kasbon_lab
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_lab');
    }

    public function add_request_budget_subcont_tenaga_ahli($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_subcont_tenaga_ahli = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_akomodasi' => $get_list_kasbon_subcont_tenaga_ahli
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_subcont_tenaga_ahli');
    }

    public function add_request_budget_subcont_perusahaan($id_spk_budgeting)
    {
        $id_spk_budgeting = urldecode($id_spk_budgeting);
        $id_spk_budgeting = str_replace('|', '/', $id_spk_budgeting);

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_spk_budgeting = $this->db->get()->row();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $get_list_kasbon_subcont_perusahaan = $this->db->get()->result();

        $data = [
            'list_budgeting' => $get_list_spk_budgeting,
            'list_kasbon_akomodasi' => $get_list_kasbon_subcont_perusahaan
        ];

        $this->template->set($data);
        $this->template->render('add_request_budget_subcont_perusahaan');
    }

    public function save_request_budget_others()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_others();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 3,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_others'])) {
            foreach ($post['req_others'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_others_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->error($insert_header));
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_others_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_request_budget_lab()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_lab();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 4,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_lab'])) {
            foreach ($post['req_lab'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_lab_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->error($insert_header));
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_lab_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_request_budget_subcont_tenaga_ahli()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_subcont_tenaga_ahli();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 5,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_subcont_tenaga_ahli'])) {
            foreach ($post['req_subcont_tenaga_ahli'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->error($insert_header));
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function save_request_budget_subcont_perusahaan()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        $id_request_ovb = $this->Kasbon_project_model->generate_id_req_ovb_subcont_perusahaan();

        $data_header = [
            'id_request_ovb' => $id_request_ovb,
            'id_spk_budgeting' => $post['id_spk_budgeting'],
            'id_spk_penawaran' => $post['id_spk_penawaran'],
            'id_penawaran' => $post['id_penawaran'],
            'tipe' => 6,
            'created_by' => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s')
        ];

        $data_detail = [];
        if (isset($post['req_subcont_perusahaan'])) {
            foreach ($post['req_subcont_perusahaan'] as $item) {
                $qty_budget_tambahan = str_replace(',', '', $item['qty_budget_tambahan']);
                $budget_tambahan = str_replace(',', '', $item['budget_tambahan']);
                if ($qty_budget_tambahan > 0 || $budget_tambahan > 0) {
                    $data_detail[] = [
                        'id_request_ovb' => $id_request_ovb,
                        'id_detail' => $item['id_detail'],
                        'id_item' => $item['id_item'],
                        'nm_item' => $item['nm_item'],
                        'qty_estimasi' => str_replace(',', '', $item['qty_estimasi']),
                        'price_unit_estimasi' => str_replace(',', '', $item['price_unit_estimasi']),
                        'total_budget_estimasi' => str_replace(',', '', $item['total_budget']),
                        'qty_budget_tambahan' => str_replace(',', '', $item['qty_budget_tambahan']),
                        'budget_tambahan' => str_replace(',', '', $item['budget_tambahan']),
                        'pengajuan_budget' => str_replace(',', '', $item['pengajuan_new_budget']),
                        'reason' => $item['reason'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        if (!empty($data_detail)) {
            $insert_header = $this->db->insert('kons_tr_kasbon_req_ovb_subcont_perusahaan_header', $data_header);
            if (!$insert_header) {
                $this->db->trans_rollback();

                print_r('Query Header - ' . $this->db->error($insert_header));
                exit;
            }

            $insert_detail = $this->db->insert_batch('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail', $data_detail);
            if (!$insert_detail) {
                $this->db->trans_rollback();

                print_r('Query Detail - ' . $this->db->error($insert_detail));
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been saved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function history_approval()
    {
        $id_kasbon = $this->input->get('id_kasbon', true);

        $get_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $id_kasbon])->row();

        if (empty($get_kasbon)) {
            echo json_encode([
                'status' => 0,
                'pesan' => 'Data not found!'
            ]);
            return;
        }

        $created_by_name = get_name('users', 'nm_lengkap', 'id_user', $get_kasbon->created_by);
        $approved_by_name = !empty($get_kasbon->approved_by) ? get_name('users', 'nm_lengkap', 'id_user', $get_kasbon->approved_by) : '';

        // Tipe label mapping
        $tipe_labels = [
            1 => 'Subcont',
            2 => 'Akomodasi',
            3 => 'Others',
            4 => 'Lab',
            5 => 'Subcont Tenaga Ahli',
            6 => 'Subcont Perusahaan'
        ];
        $tipe_label = isset($tipe_labels[$get_kasbon->tipe]) ? $tipe_labels[$get_kasbon->tipe] : '-';

        // Build timeline steps
        $steps = [];

        // Step 1: Submitted
        $steps[] = [
            'icon' => 'fa-paper-plane',
            'color' => '#17a2b8',
            'title' => 'Submitted',
            'user' => ucfirst($created_by_name),
            'date' => date('d M Y, H:i', strtotime($get_kasbon->created_date)),
            'remark' => '',
            'done' => true
        ];

        // Step 2: Approval
        if ($get_kasbon->sts == '1') {
            $steps[] = [
                'icon' => 'fa-check-circle',
                'color' => '#28a745',
                'title' => 'Approved',
                'user' => ucfirst($approved_by_name),
                'date' => !empty($get_kasbon->approved_date) ? date('d M Y, H:i', strtotime($get_kasbon->approved_date)) : '-',
                'remark' => '',
                'done' => true
            ];
        } elseif ($get_kasbon->sts_reject == '1') {
            $steps[] = [
                'icon' => 'fa-times-circle',
                'color' => '#dc3545',
                'title' => 'Rejected',
                'user' => ucfirst($approved_by_name),
                'date' => !empty($get_kasbon->approved_date) ? date('d M Y, H:i', strtotime($get_kasbon->approved_date)) : '-',
                'remark' => !empty($get_kasbon->reject_reason) ? $get_kasbon->reject_reason : '',
                'done' => true
            ];
        } else {
            $steps[] = [
                'icon' => 'fa-clock-o',
                'color' => '#ffc107',
                'title' => 'Waiting Approval',
                'user' => '',
                'date' => '',
                'remark' => '',
                'done' => false
            ];
        }

        // Build HTML timeline
        $html = '<div style="padding: 15px 20px;">';

        // Header info
        $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 15px 20px; margin-bottom: 25px; color: #fff;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">';
        $html .= '<div>';
        $html .= '<div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">ID Kasbon</div>';
        $html .= '<div style="font-size: 15px; font-weight: 600;">' . $get_kasbon->id . '</div>';
        $html .= '</div>';
        $html .= '<div style="text-align: center;">';
        $html .= '<div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Deskripsi</div>';
        $html .= '<div style="font-size: 14px; font-weight: 500;">' . $get_kasbon->deskripsi . '</div>';
        $html .= '</div>';
        $html .= '<div style="text-align: right;">';
        $html .= '<div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Tipe</div>';
        $html .= '<div style="font-size: 14px; font-weight: 500;">' . $tipe_label . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Timeline
        $html .= '<div style="position: relative; padding-left: 40px;">';

        // Vertical line
        $html .= '<div style="position: absolute; left: 15px; top: 5px; bottom: 5px; width: 2px; background: #e9ecef;"></div>';

        $total_steps = count($steps);
        foreach ($steps as $i => $step) {
            $is_last = ($i === $total_steps - 1);
            $opacity = $step['done'] ? '1' : '0.5';

            $html .= '<div style="position: relative; margin-bottom: ' . ($is_last ? '0' : '25px') . '; opacity: ' . $opacity . ';">';

            // Circle dot
            $html .= '<div style="position: absolute; left: -33px; top: 3px; width: 28px; height: 28px; border-radius: 50%; background: ' . $step['color'] . '; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1;">';
            $html .= '<i class="fa ' . $step['icon'] . '" style="color: #fff; font-size: 12px;"></i>';
            $html .= '</div>';

            // Content card
            $html .= '<div style="background: #fff; border: 1px solid #e9ecef; border-left: 3px solid ' . $step['color'] . '; border-radius: 6px; padding: 12px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">';

            // Title row
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">';
            $html .= '<span style="font-weight: 600; font-size: 14px; color: ' . $step['color'] . ';">' . $step['title'] . '</span>';
            if (!empty($step['date'])) {
                $html .= '<span style="font-size: 12px; color: #6c757d;"><i class="fa fa-calendar-o" style="margin-right: 4px;"></i>' . $step['date'] . '</span>';
            }
            $html .= '</div>';

            // User
            if (!empty($step['user'])) {
                $html .= '<div style="font-size: 13px; color: #495057;"><i class="fa fa-user-o" style="margin-right: 6px; color: #6c757d;"></i>' . $step['user'] . '</div>';
            }

            // Remark
            if (!empty($step['remark'])) {
                $html .= '<div style="margin-top: 8px; padding: 8px 12px; background: #fff3cd; border-radius: 4px; font-size: 12px; color: #856404; border: 1px solid #ffeaa7;">';
                $html .= '<i class="fa fa-comment-o" style="margin-right: 6px;"></i><strong>Reason:</strong> ' . $step['remark'];
                $html .= '</div>';
            }

            $html .= '</div>'; // end content card
            $html .= '</div>'; // end step
        }

        $html .= '</div>'; // end timeline
        $html .= '</div>'; // end wrapper

        echo json_encode([
            'status' => 1,
            'result' => $html
        ]);
    }

    private function _upload_bukti_penggunaan($id_header)
    {
        $target_dir = './uploads/kasbon_project_bukti_penggunaan/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
            chmod($target_dir, 0777);
        }

        $data_bukti = [];
        if (isset($_FILES['bukti_penggunaan']) && !empty($_FILES['bukti_penggunaan']['name'])) {
            $files = $_FILES['bukti_penggunaan'];
            $file_count = is_array($files['name']) ? count($files['name']) : 1;

            $config = [
                'upload_path'   => $target_dir,
                'allowed_types' => 'gif|jpg|png|jpeg|bmp|pdf|webp',
                'max_size'      => 100000,
                'encrypt_name'  => TRUE,
                'remove_spaces' => TRUE
            ];

            $this->load->library('upload', $config);

            if (is_array($files['name'])) {
                for ($i = 0; $i < $file_count; $i++) {
                    if (empty($files['name'][$i])) {
                        continue;
                    }

                    $_FILES['bukti_file']['name']     = $files['name'][$i];
                    $_FILES['bukti_file']['type']     = $files['type'][$i];
                    $_FILES['bukti_file']['tmp_name'] = $files['tmp_name'][$i];
                    $_FILES['bukti_file']['error']    = $files['error'][$i];
                    $_FILES['bukti_file']['size']     = $files['size'][$i];

                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('bukti_file')) {
                        $data_upload = $this->upload->data();
                        $data_bukti[] = [
                            'id_header_kasbon' => $id_header,
                            'upload_file'      => 'uploads/kasbon_project_bukti_penggunaan/' . $data_upload['file_name'],
                            'created_by'       => $this->auth->user_id(),
                            'created_date'     => date('Y-m-d H:i:s')
                        ];
                    }
                }
            } else {
                if (!empty($files['name'])) {
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('bukti_penggunaan')) {
                        $data_upload = $this->upload->data();
                        $data_bukti[] = [
                            'id_header_kasbon' => $id_header,
                            'upload_file'      => 'uploads/kasbon_project_bukti_penggunaan/' . $data_upload['file_name'],
                            'created_by'       => $this->auth->user_id(),
                            'created_date'     => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }

            if (!empty($data_bukti)) {
                $this->db->insert_batch('kons_tr_kasbon_project_bukti_penggunaan', $data_bukti);
            }
        }

        return $data_bukti;
    }

    public function del_bukti_penggunaan()
    {
        $id = $this->input->post('id');

        $get_data = $this->db->get_where('kons_tr_kasbon_project_bukti_penggunaan', ['id' => $id])->row();
        if (!empty($get_data)) {
            if (file_exists('./' . $get_data->upload_file) && is_file('./' . $get_data->upload_file)) {
                unlink('./' . $get_data->upload_file);
            }
            $del = $this->db->delete('kons_tr_kasbon_project_bukti_penggunaan', ['id' => $id]);
            if ($del) {
                echo json_encode(['status' => 1, 'pesan' => 'File bukti berhasil dihapus !']);
            } else {
                echo json_encode(['status' => 0, 'pesan' => 'Gagal menghapus file bukti !']);
            }
        } else {
            echo json_encode(['status' => 0, 'pesan' => 'Data tidak ditemukan !']);
        }
    }
}
