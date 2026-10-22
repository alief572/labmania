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
class Approval_kasbon_overbudget extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Approval_Kasbon_Overbudget.View';
    protected $addPermission      = 'Approval_Kasbon_Overbudget.Add';
    protected $managePermission = 'Approval_Kasbon_Overbudget.Manage';
    protected $deletePermission = 'Approval_Kasbon_Overbudget.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model('Approval_kasbon_overbudget/Approval_kasbon_overbudget_model');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('Approval Kasbon Overbudget');
        $this->template->render('index');
    }

    public function detail()
    {
        $post = $this->input->post();

        $id = $post['id'];
        $tipe = $post['tipe'];

        if ($tipe == '1') {
            $this->db->select('a.id, a.id_request_ovb, a.id_aktifitas as id_item, a.nm_aktifitas as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '2') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '3') {
            $this->db->select('a.id, a.id_request_ovb, a.id_aktifitas as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '4') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '5') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '6') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }

        $this->template->render('detail_akomodasi', array('list_data' => $get_data));
    }

    public function approval()
    {
        $post = $this->input->post();

        $id = $post['id'];
        $tipe = $post['tipe'];

        if ($tipe == '1') {
            $this->db->select('a.id, a.id_request_ovb, a.id_aktifitas as id_item, a.nm_aktifitas as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '2') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_akomodasi_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '3') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '4') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '5') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        if ($tipe == '6') {
            $this->db->select('a.id, a.id_request_ovb, a.id_item as id_item, a.nm_item as nm_item, a.qty_budget_tambahan, a.budget_tambahan, a.pengajuan_budget, a.reason');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail a');
            $this->db->where('a.id_request_ovb', $id);
            $get_data = $this->db->get()->result_array();
        }
        $this->template->render('approval_akomodasi', array('list_data' => $get_data, 'id' => $id));
    }

    public function approve()
    {
        $id = $this->input->post('id');

        $this->db->trans_begin();

        $approved_data = [
            'sts'           => 1
        ];
        //penggunaan approved data untuk update semua tabel header, karena status approval ada di semua tabel header

        $this->db->update('kons_tr_kasbon_req_ovb_akomodasi_header', $approved_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_header', $approved_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_others_header', $approved_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_lab_header', $approved_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', $approved_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_perusahaan_header', $approved_data, ['id_request_ovb' => $id]);

        $check_1 = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_header', ['id_request_ovb' => $id])->row();
        if ($check_1) $this->_auto_inject_ovb_custom($id, 1);
        $check_2 = $this->db->get_where('kons_tr_kasbon_req_ovb_akomodasi_header', ['id_request_ovb' => $id])->row();
        if ($check_2) $this->_auto_inject_ovb_custom($id, 2);
        $check_3 = $this->db->get_where('kons_tr_kasbon_req_ovb_others_header', ['id_request_ovb' => $id])->row();
        if ($check_3) $this->_auto_inject_ovb_custom($id, 3);
        $check_4 = $this->db->get_where('kons_tr_kasbon_req_ovb_lab_header', ['id_request_ovb' => $id])->row();
        if ($check_4) $this->_auto_inject_ovb_custom($id, 4);
        $check_5 = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', ['id_request_ovb' => $id])->row();
        if ($check_5) $this->_auto_inject_ovb_custom($id, 5);
        $check_6 = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_perusahaan_header', ['id_request_ovb' => $id])->row();
        if ($check_6) $this->_auto_inject_ovb_custom($id, 6);


        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $msg = 'Overbudget has been Approved !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    private function _auto_inject_ovb_custom($id_request_ovb, $tipe)
    {
        if ($tipe == 1) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_aktifitas' => '',
                        'nm_aktifitas' => $d->nm_aktifitas,
                        'mandays_subcont_final' => $d->qty_budget_tambahan,
                        'total_aktifitas_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'mandays_rate_subcont_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_aktifitas', $insert_data);
            }
        } else if ($tipe == 2) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_akomodasi_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_akomodasi_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_akomodasi' => '',
                        'id_item' => '',
                        'nm_item' => $d->nm_item,
                        'qty_final' => $d->qty_budget_tambahan,
                        'total_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'price_unit_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_akomodasi', $insert_data);
            }
        } else if ($tipe == 3) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_others_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_others_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_others' => '',
                        'id_item' => '',
                        'nm_item' => $d->nm_item,
                        'qty_final' => $d->qty_budget_tambahan,
                        'total_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'price_unit_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_others', $insert_data);
            }
        } else if ($tipe == 4) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_lab_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_lab_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_lab' => '',
                        'id_item' => '',
                        'nm_item' => $d->nm_item,
                        'qty_final' => $d->qty_budget_tambahan,
                        'total_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'price_unit_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_lab', $insert_data);
            }
        } else if ($tipe == 5) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_subcont' => '',
                        'id_item' => '',
                        'nm_item' => $d->nm_item,
                        'qty_final' => $d->qty_budget_tambahan,
                        'total_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'price_unit_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_subcont_tenaga_ahli', $insert_data);
            }
        } else if ($tipe == 6) {
            $header = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_perusahaan_header', ['id_request_ovb' => $id_request_ovb])->row();
            $details = $this->db->get_where('kons_tr_kasbon_req_ovb_subcont_perusahaan_detail', ['id_request_ovb' => $id_request_ovb, 'id_detail' => ''])->result();
            if(!empty($details)) {
                $insert_data = [];
                foreach($details as $d) {
                    $insert_data[] = [
                        'id_spk_budgeting' => $header->id_spk_budgeting,
                        'id_spk_penawaran' => $header->id_spk_penawaran,
                        'id_penawaran' => $header->id_penawaran,
                        'id_subcont' => '',
                        'id_item' => '',
                        'nm_item' => $d->nm_item,
                        'qty_final' => $d->qty_budget_tambahan,
                        'total_final' => $d->budget_tambahan * $d->qty_budget_tambahan,
                        'price_unit_final' => $d->budget_tambahan,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
                if(!empty($insert_data)) $this->db->insert_batch('kons_tr_spk_budgeting_subcont_perusahaan', $insert_data);
            }
        }
    }

    public function reject()
    {
        $id = $this->input->post('id');
        $reject_reason = $this->input->post('reject_reason');

        $this->db->trans_begin();

        $reject_data = array('sts' => 2, 'reject_reason' => $reject_reason);
        $this->db->update('kons_tr_kasbon_req_ovb_akomodasi_header', $reject_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_header', $reject_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_others_header', $reject_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_lab_header', $reject_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', $reject_data, ['id_request_ovb' => $id]);
        $this->db->update('kons_tr_kasbon_req_ovb_subcont_perusahaan_header', $reject_data, ['id_request_ovb' => $id]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $msg = 'Overbudget has been Rejected !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    public function get_data_overbudget()
    {
        $this->Approval_kasbon_overbudget_model->get_data_overbudget();
    }
}
