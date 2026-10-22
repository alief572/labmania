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
class Approval_penawaran extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Approval_penawaran.View';
    protected $addPermission      = 'Approval_penawaran.Add';
    protected $managePermission = 'Approval_penawaran.Manage';
    protected $deletePermission = 'Approval_penawaran.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model('Approval_penawaran/Approval_penawaran_model');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('Approval Quotation');
        $this->template->render('index');
    }

    public function view_penawaran($id_penawaran)
    {
        $id_penawaran = urldecode($id_penawaran);
        $id_penawaran = str_replace('|', '/', $id_penawaran);

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $id_penawaran])->row();

        $this->db->select('a.*, b.nm_aktifitas as nama_aktifitas, COUNT(c.id_chk_point) AS jml_check_point');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->join('kons_master_check_point c', 'c.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $this->db->group_by('a.id');
        $this->db->order_by('a.id', 'asc');
        $get_penawaran_aktifitas = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_others = $this->db->get()->result();

        $this->db->select('a.*, b.isu_lingkungan');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('customer a');
        $this->db->where('a.nm_customer <>', '');
        $this->db->group_by('a.nm_customer');
        $get_customer = $this->db->get()->result();

        // $this->db->select('a.*');
        // $this->db->from('employee a');
        // $this->db->where('a.deleted', 'N');
        // $this->db->order_by('a.nm_karyawan', 'asc');
        // $get_marketing = $this->db->get()->result();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $get_marketing = $this->db->get()->result();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.flag_active', 'Y');
        $get_employees = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_konsultasi_header a');
        $get_package = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_company a');
        $get_company = $this->db->get()->result();

        $data = [
            'list_penawaran' => $get_penawaran,
            'list_penawaran_aktifitas' => $get_penawaran_aktifitas,
            'list_penawaran_akomodasi' => $get_penawaran_akomodasi,
            'list_penawaran_others' => $get_penawaran_others,
            'list_penawaran_lab' => $get_penawaran_lab,
            'list_customers' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_package' => $get_package,
            'list_aktifitas' => $get_aktifitas,
            'list_divisi' => $get_divisi,
            'list_employees' => $get_employees,
            'list_company' => $get_company
        ];

        $this->template->title('View Quotation');
        $this->template->set($data);
        $this->template->render('view_penawaran');
    }

    public function approval($id_penawaran)
    {
        $id_penawaran = urldecode($id_penawaran);
        $id_penawaran = str_replace('|', '/', $id_penawaran);

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $id_penawaran])->row();

        $this->db->select('a.*, b.nm_aktifitas as nama_aktifitas, COUNT(c.id_chk_point) AS jml_check_point');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->join('kons_master_check_point c', 'c.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $this->db->group_by('a.id');
        $this->db->order_by('a.id', 'asc');
        $get_penawaran_aktifitas = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_others = $this->db->get()->result();

        $this->db->select('a.*, b.isu_lingkungan');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_lab = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_penawaran_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('customer a');
        $this->db->where('a.nm_customer <>', '');
        $this->db->group_by('a.nm_customer');
        $get_customer = $this->db->get()->result();

        // $this->db->select('a.*');
        // $this->db->from('employee a');
        // $this->db->where('a.deleted', 'N');
        // $this->db->order_by('a.nm_karyawan', 'asc');
        // $get_marketing = $this->db->get()->result();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $get_marketing = $this->db->get()->result();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.flag_active', 'Y');
        $get_employees = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_konsultasi_header a');
        $get_package = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_company a');
        $get_company = $this->db->get()->result();

        $data = [
            'list_penawaran' => $get_penawaran,
            'list_penawaran_aktifitas' => $get_penawaran_aktifitas,
            'list_penawaran_akomodasi' => $get_penawaran_akomodasi,
            'list_penawaran_others' => $get_penawaran_others,
            'list_penawaran_lab' => $get_penawaran_lab,
            'list_penawaran_subcont_tenaga_ahli' => $get_penawaran_subcont_tenaga_ahli,
            'list_penawaran_subcont_perusahaan' => $get_penawaran_subcont_perusahaan,
            'list_customers' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_package' => $get_package,
            'list_aktifitas' => $get_aktifitas,
            'list_divisi' => $get_divisi,
            'list_employees' => $get_employees,
            'list_company' => $get_company
        ];

        $this->template->title('Approval Quotation');
        $this->template->set($data);
        $this->template->render('approval_penawaran');
    }

    public function get_data_penawaran()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $dbhr = $this->load->database('dbhr', true);

        $this->db->select('a.*, b.nm_customer, d.nm_paket');
        $this->db->from('kons_tr_penawaran a');
        $this->db->join('customer b', 'b.id_customer = a.id_customer', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = a.id_paket', 'left');
        $this->db->where('a.deleted_by', null);
        $this->db->where('a.sts_quot', 1);
        $this->db->where('a.sts_deal', null);
        if (!empty($search) && !empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('a.tgl_quotation', $search['value'], 'both');
            $this->db->or_like('a.id_quotation', $search['value'], 'both');
            $this->db->or_like('d.nm_paket', $search['value'], 'both');
            $this->db->or_like('b.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.grand_total', str_replace(',', '', $search['value']), 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $hasil = [];

        $no = 1;
        foreach ($get_data->result() as $item) {

            if ($item->sts_cust == 0) {
                $status_cust = '
                    <span class="btn btn-sm btn-warning" style="width: 100% !important;">
                        <b>New</b>
                    </span>
                ';
            } else {
                $status_cust = '
                    <span class="btn btn-sm btn-info" style="width: 100% !important;">
                        <b>Repeat</b>
                    </span>
                ';
            }

            if ($item->sts_quot == 1) {
                $status_quot = '
                    <span class="btn btn-sm btn-primary" style="width: 100% !important;">
                        <b>Waiting Approval</b>
                    </span>
                ';
            }
            if ($item->sts_quot == 2) {
                $status_quot = '
                    <span class="btn btn-sm btn-success" style="width: 100% !important;">
                        <b>Approved</b>
                    </span>
                ';
            }
            if ($item->sts_quot == 0) {
                $status_quot = '
                    <span class="btn btn-sm btn-danger" style="width: 100% !important;">
                        <b>Rejected</b>
                    </span>
                ';
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
                <ul class="dropdown-menu dropdown-menu-right" style="min-width: 150px; padding: 5px 0;">
            ';

            if ($this->viewPermission) {
                $option .= '
                    <li>
                        <a href="' . base_url('approval_penawaran/view_penawaran/' . urlencode(str_replace('/', '|', $item->id_quotation))) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-file text-info"></i> View
                        </a>
                    </li>
                ';
            }

            if ($this->managePermission) {
                $option .= '
                    <li>
                        <a href="' . base_url('approval_penawaran/approval/' . urlencode(str_replace('/', '|', $item->id_quotation))) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-check text-success"></i> Approval
                        </a>
                    </li>
                ';
            }

            $option .= '</ul>';

            // Get marketing name from HR database using separate connection
            $nm_marketing = '';
            if (!empty($item->id_marketing)) {
                $get_marketing = $dbhr->get_where('employees', ['id' => $item->id_marketing])->row();
                $nm_marketing = (!empty($get_marketing)) ? $get_marketing->name : '';
            }

            $nm_paket = (!empty($item->nm_paket)) ? $item->nm_paket : '';
            $nm_customer = (!empty($item->nm_customer)) ? $item->nm_customer : '';

            $hasil[] = [
                'no' => $no,
                'id_quotation' => $item->id_quotation,
                'tgl_quotation' => $item->tgl_quotation,
                'nm_marketing' => ucfirst($nm_marketing),
                'nm_paket' => $nm_paket,
                'nm_customer' => $nm_customer,
                'grand_total' => number_format($item->grand_total),
                'status_cust' => $status_cust,
                'status_quot' => $status_quot,
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

    public function approve_penawaran()
    {
        $id_penawaran = $this->input->post('id_penawaran');

        $this->db->trans_begin();

        $update_sts_penawaran = $this->db->update('kons_tr_penawaran', [
            'sts_quot' => 2,
            'approved_by' => $this->auth->user_id(),
            'approved_date' => date('Y-m-d H:i:s')
        ], ['id_quotation' => $id_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = "Please try again later !";
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = "Data has been approved !";
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function reject_penawaran()
    {
        $id_penawaran = $this->input->post('id_penawaran');
        $reject_reason = $this->input->post('reject_reason');

        $this->db->trans_begin();

        $update_sts_penawaran = $this->db->update('kons_tr_penawaran', ['sts_quot' => 0, 'reject_reason' => $reject_reason], ['id_quotation' => $id_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = "Please try again later !";
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = "Data has been rejected !";
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    private function render_status_penawaran_non_kons($item)
    {
        $status = '<span class="badge bg-blue">Waiting Approval</span>';
        if ($item->sts_quot == '2') {
            $status = '<span class="badge bg-red">Rejected</span>';
        }
        return $status;
    }

    private function render_action_non_kons($item)
    {
        $btn_approve = '<a href="' . base_url('approval_penawaran/approval_non_kons/' . $item->id_penawaran) . '" class="btn btn-sm btn-success" title="Approval Penawaran Non Konsultasi"><i class="fa fa-arrow-up"></i></a>';

        if (!has_permission($this->managePermission)) {
            $btn_approve = '';
        }

        return $btn_approve;
    }

    public function approval_non_kons($id_penawaran)
    {
        $data_penawaran = $this->Approval_penawaran_model->get_penawaran($id_penawaran);
        $data_detail_penawaran = $this->Approval_penawaran_model->get_penawaran_detail($id_penawaran);

        $list_divisi = $this->Approval_penawaran_model->list_divisi();
        $list_customer = $this->Approval_penawaran_model->list_customer();
        $list_company = $this->Approval_penawaran_model->list_company();
        $list_sales = $this->Approval_penawaran_model->list_employee();
        $list_employee = $this->Approval_penawaran_model->list_employee();

        $data = [
            'data_penawaran' => $data_penawaran,
            'data_detail_penawaran' => $data_detail_penawaran,
            'list_divisi' => $list_divisi,
            'list_customer' => $list_customer,
            'list_company' => $list_company,
            'list_sales' => $list_sales,
            'list_employee' => $list_employee,
        ];

        $this->template->title('Approval Penawaran Non Konsultasi');
        $this->template->set($data);
        $this->template->render('approval_penawaran_non');
    }

    public function reject_penawaran_non_kons()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        try {
            $arr_update = [
                'sts_quot' => '2',
                'reject_reason' => $post['reject_reason']
            ];

            $this->db->update('kons_tr_penawaran_non_konsultasi', $arr_update, ['id_penawaran' => $post['id_penawaran']]);

            $this->db->trans_commit();

            $this->output->set_status_header(200);

            echo json_encode([
                'msg' => 'Data has been rejected !'
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => "There's an error occured, please try again later !"
            ]);
        }
    }

    public function approve_penawaran_non_kons()
    {
        $post = $this->input->post();

        $this->db->trans_begin();
        try {
            $arr_update = [
                'sts_quot' => '1',
                'reject_reason' => '',
                'approved_by' => $this->auth->user_id(),
                'approved_date' => date('Y-m-d H:i:s')
            ];

            $this->db->update('kons_tr_penawaran_non_konsultasi', $arr_update, ['id_penawaran' => $post['id_penawaran']]);

            $this->db->trans_commit();

            $this->output->set_status_header(200);

            echo json_encode([
                'msg' => 'Data has been approved !'
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => "There's an error occured, please try again later !"
            ]);
        }
    }

    public function get_data_penawaran_non_konsultasi()
    {
        try {
            $draw = $this->input->get('draw');
            $length = $this->input->get('length');
            $start = $this->input->get('start');
            $search = $this->input->get('search')['value'];

            $this->db->select('a.*');
            $this->db->from('kons_tr_penawaran_non_konsultasi a');
            $this->db->where('a.deleted_by', null);
            $this->db->where('a.sts_quot <>', '1');
            $this->db->where('a.sts_deal <>', '1');

            $db_clone = clone $this->db;
            $count_all = $db_clone->count_all_results();

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('a.id_penawaran', $search, 'both');
                $this->db->or_like('a.tgl_quotation', $search, 'both');
                $this->db->or_like('a.nm_pic_penawaran', $search, 'both');
                $this->db->or_like('a.keterangan_penawaran', $search, 'both');
                $this->db->or_like('a.grand_total', $search, 'both');
                $this->db->group_end();
            }

            $db_clone = clone $this->db;
            $count_filtered = $db_clone->count_all_results();

            $this->db->order_by('a.input_date', 'desc');
            $this->db->limit($length, $start);

            $get_data = $this->db->get()->result();

            $no = (0 + $start);
            $hasil = [];
            foreach ($get_data as $item) {
                $no++;

                $status = $this->render_status_penawaran_non_kons($item);
                // $status = '';
                // $action = '';

                $action = $this->render_action_non_kons($item);

                $hasil[] = [
                    'no' => $no,
                    'id_quotation' => $item->id_penawaran,
                    'date' => $item->tgl_quotation,
                    'pic_penawaran' => $item->nm_pic_penawaran,
                    'penawaran' => $item->keterangan_penawaran,
                    'customer' => $item->nm_customer,
                    'grand_total' => number_format($item->grand_total),
                    'status_quot' => $status,
                    'action' => $action
                ];
            }

            $response = [
                'draw' => intval($draw),
                'recordsTotal' => $count_all,
                'recordsFiltered' => $count_filtered,
                'data' => $hasil
            ];

            $this->output->set_status_header(200);
            echo json_encode($response);
        } catch (Exception $e) {
            $this->output->set_status_header(200);

            $response = [
                'msg' => "There's an error occured, Please try again later !"
            ];

            $this->output->set_status_header(500);
            echo json_encode($response);
        }
    }
}
