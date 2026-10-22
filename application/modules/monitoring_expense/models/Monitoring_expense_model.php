<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Monitoring_expense_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Monitoring_Expense_Report.Add');
        $this->ENABLE_MANAGE  = has_permission('Monitoring_Expense_Report.Manage');
        $this->ENABLE_VIEW    = has_permission('Monitoring_Expense_Report.View');
        $this->ENABLE_DELETE  = has_permission('Monitoring_Expense_Report.Delete');
    }

    public function get_data_expense() {
        $draw = $this->input->post('draw');
        $length = $this->input->post('length');
        $start = $this->input->post('start');
        $search = $this->input->post('search');

        $this->db->select('a.id, a.id_header, a.total_expense_report, a.total_kasbon, a.selisih, a.tipe, f.tanggal_pembayaran, f.id as no_payment, f.nilai_bayar, e.nm_lengkap,d.nm_customer, c.id_spk_penawaran, c.tipe');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->join('request_payment b', 'b.no_doc = a.id', 'left');
        $this->db->join('kons_tr_kasbon_project_header c', 'c.id = a.id_header');
        $this->db->join('kons_tr_spk_penawaran d', 'd.id_spk_penawaran = c.id_spk_penawaran', 'left');
        $this->db->join('users e', 'e.id_user = a.created_by', 'left');
        $this->db->join('payment_approve f', 'f.no_doc = a.id', 'left');
        if(!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('c.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('d.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.total_expense_report', $search['value'], 'both');
            $this->db->or_like('e.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.id', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.id, a.id_header, a.total_expense_report, a.total_kasbon, a.selisih, a.tipe, f.tanggal_pembayaran, f.id as no_payment, f.nilai_bayar, e.nm_lengkap, d.nm_customer, c.id_spk_penawaran, c.tipe');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->join('request_payment b', 'b.no_doc = a.id', 'left');
        $this->db->join('kons_tr_kasbon_project_header c', 'c.id = a.id_header');
        $this->db->join('kons_tr_spk_penawaran d', 'd.id_spk_penawaran = c.id_spk_penawaran', 'left');
        $this->db->join('users e', 'e.id_user = a.created_by', 'left');
        $this->db->join('payment_approve f', 'f.no_doc = a.id', 'left');
        if(!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('c.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('d.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.total_expense_report', $search['value'], 'both');
            $this->db->or_like('e.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.id', 'desc');

        $get_data_all = $this->db->get();

        $no = (0 + $start);

        $hasil = [];

        foreach($get_data->result_array() as $item) :
            $no++;

            $status = '<button type="button" class="btn btn-danger btn-sm">Not Processed</button>';

            $tipe_kasbon = '';
            if($item['tipe'] == '1') {
                $tipe_kasbon = 'Kasbon Subcont';
            }
            if($item['tipe'] == '2') {
                $tipe_kasbon = 'Kasbon Akomodasi';
            }
            if($item['tipe'] == '3') {
                $tipe_kasbon = 'Kasbon Others';
            }
            
            $keperluan = $item['nm_customer'].', '.$item['id_spk_penawaran'].', '.$tipe_kasbon;

            $action = '<a href="'.base_url('approval_request_payment/print_expense/'.str_replace('/', '|', $item['id'])).'" class="btn btn-sm btn-info" title="Print Expense"><i class="fa fa-print"></i></a>';

            $hasil[] = [
                'no' => $no,
                'tanggal_payment' => $item['tanggal_pembayaran'],
                'no_payment' => $item['no_payment'],
                'keperluan' => $keperluan,
                'nilai_pengajuan' => number_format($item['total_kasbon'], 2),
                'nilai_expense' => number_format($item['nilai_bayar'], 2),
                'pic' => ucfirst($item['nm_lengkap']),
                'status' => $status,
                'action' => $action
            ];
        endforeach;

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }
}
