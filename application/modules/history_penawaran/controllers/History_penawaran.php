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
class History_penawaran extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'History_Penawaran.View';
    protected $addPermission      = 'History_Penawaran.Add';
    protected $managePermission = 'History_Penawaran.Manage';
    protected $deletePermission = 'History_Penawaran.Delete';

    protected $is_admin;

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model('History_penawaran/History_penawaran_model');
        date_default_timezone_set('Asia/Bangkok');

        $this->is_admin = $this->auth->is_admin();
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('History Quotation List');
        $this->template->render('index');
    }

    public function view_penawaran($id_history)
    {
        $get_penawaran = $this->db->get_where('kons_tr_penawaran_history', ['id_history' => $id_history])->row();

        $this->db->select('a.*, b.nm_aktifitas as nama_aktifitas, COUNT(c.id_chk_point) AS jml_check_point');
        $this->db->from('kons_tr_penawaran_aktifitas_history a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->join('kons_master_check_point c', 'c.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_history', $id_history);
        $this->db->group_by('a.id');
        $this->db->order_by('a.id', 'asc');
        $get_penawaran_aktifitas = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_akomodasi_history a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_history', $id_history);
        $get_penawaran_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_others_history a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_history', $id_history);
        $get_penawaran_others = $this->db->get()->result();

        $this->db->select('a.*, b.isu_lingkungan');
        $this->db->from('kons_tr_penawaran_lab_history a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_history', $id_history);
        $get_penawaran_lab = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli_history a');
        $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_history', $id_history);
        $get_penawaran_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan_history a');
        $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_history', $id_history);
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
        $this->db->where_in('a.id', ['EMP0010', 'EMP0029', 'EMP0031', 'EMP0170', 'EMP0246', 'EMP0035', 'EMP0001', 'EMP0257', 'EMP0173']);
        $get_marketing = $this->db->get()->result();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where_in('a.company_id', ['COM003', 'COM006', 'COM012']);
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
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
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

        $this->template->title('View Quotation');
        $this->template->set($data);
        $this->template->render('view_penawaran');
    }

    public function get_data_penawaran()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*, c.nama');
        $this->db->from('kons_tr_penawaran_history a');
        $this->db->join('customer b', 'b.id_customer = a.id_customer', 'left');
        $this->db->join('members c', 'c.id = a.id_marketing', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = a.id_paket', 'left');
        $this->db->join('kons_master_paket e', 'e.id_paket = d.id_paket', 'left');
        $this->db->where(1, 1);
        $this->db->where('a.deleted_by', null);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_quotation', $search['value'], 'both');
            $this->db->or_like('a.tgl_quotation', $search['value'], 'both');
            $this->db->or_like('c.nama', $search['value'], 'both');
            $this->db->or_like('e.nm_paket', $search['value'], 'both');
            $this->db->or_like('b.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.grand_total', str_replace(',', '', $search['value']), 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_history');

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->order_by('a.input_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $hasil = [];

        $no = ($start + 1);
        foreach ($get_data->result() as $item) {

            $option = '<a href="' . base_url('history_penawaran/view_penawaran/' . $item->id_history) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';


            // $get_marketing = $this->db->get_where('employee', ['id' => $item->id_marketing])->row();
            $nm_marketing = $item->nama;

            $this->db->select('a.*');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_paket);
            $get_package = $this->db->get()->row();

            $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

            $get_customers = $this->db->get_where('customer', ['id_customer' => $item->id_customer])->row();
            $nm_customer = (!empty($get_customers)) ? $get_customers->nm_customer : '';

            $hasil[] = [
                'no' => $no,
                'id_history' => $item->id_history,
                'id_quotation' => $item->id_quotation,
                'tgl_quotation' => $item->tgl_quotation,
                'nm_marketing' => ucfirst($nm_marketing),
                'nm_paket' => $nm_paket,
                'nm_customer' => $nm_customer,
                'grand_total' => number_format($item->grand_total),
                'revisi' => $item->revisi,
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
}
