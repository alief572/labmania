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
class Penawaran extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Penawaran.View';
    protected $addPermission      = 'Penawaran.Add';
    protected $managePermission = 'Penawaran.Manage';
    protected $deletePermission = 'Penawaran.Delete';

    protected $is_admin;

    protected $dbhr;

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model('Penawaran/Penawaran_model');
        date_default_timezone_set('Asia/Bangkok');

        $this->is_admin = $this->auth->is_admin();

        $this->dbhr = $this->load->database('dbhr', true);
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('Quotation List');
        $this->template->render('index');
    }

    public function edit_penawaran($id_penawaran)
    {
        $id_penawaran = urldecode($id_penawaran);
        $id_penawaran = str_replace('|', '/', $id_penawaran);
        // print_r($id_penawaran);
        // exit;

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $id_penawaran])->row();

        $this->db->select('a.*, b.nm_aktifitas as nama_aktifitas, COUNT(c.id_chk_point) AS jml_check_point');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->join('kons_master_check_point c', 'c.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $this->db->group_by('a.id');
        $this->db->order_by('a.id', 'asc');
        $get_penawaran_aktifitas = $this->db->get()->result();

        $get_penawaran_akomodasi = $this->db->get_where('kons_tr_penawaran_akomodasi', ['id_penawaran' => $id_penawaran])->result();
        $get_penawaran_others = $this->db->get_where('kons_tr_penawaran_others', ['id_penawaran' => $id_penawaran])->result();
        $get_penawaran_lab = $this->db->get_where('kons_tr_penawaran_lab', ['id_penawaran' => $id_penawaran])->result();
        $get_penawaran_subcont_tenaga_ahli = $this->db->get_where('kons_tr_penawaran_subcont_tenaga_ahli', ['id_penawaran' => $id_penawaran])->result();
        $get_penawaran_subcont_perusahaan = $this->db->get_where('kons_tr_penawaran_subcont_perusahaan', ['id_penawaran' => $id_penawaran])->result();

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
        $this->db->where('a.flag_active', 'Y');
        $get_employees = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_konsultasi_header a');
        $get_package = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.tipe_biaya', 1);
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_akomodasi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.tipe_biaya', 2);
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_others = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_lab a');
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_tenaga_ahli a');
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_subcont_perusahaan a');
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_subcont_perusahaan = $this->db->get()->result();


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
            'list_def_akomodasi' => $get_def_biaya_akomodasi,
            'list_def_others' => $get_def_biaya_others,
            'list_def_lab' => $get_def_biaya_lab,
            'list_def_tenaga_ahli' => $get_def_biaya_tenaga_ahli,
            'list_def_subcont_perusahaan' => $get_def_biaya_subcont_perusahaan,
            'list_divisi' => $get_divisi,
            'list_employees' => $get_employees,
            'list_company' => $get_company
        ];

        $this->template->title('Edit Quotation');
        $this->template->set($data);
        $this->template->render('edit_penawaran');
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
        $this->db->where_in('a.id', ['EMP0010', 'EMP0029', 'EMP0031', 'EMP0170', 'EMP0246', 'EMP0035', 'EMP0001', 'EMP0257', 'EMP0173']);
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

        $this->db->select('a.*, c.nama, f.nm_lengkap');
        $this->db->from('kons_tr_penawaran a');
        $this->db->join('customer b', 'b.id_customer = a.id_customer', 'left');
        $this->db->join('members c', 'c.id = a.id_marketing', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = a.id_paket', 'left');
        $this->db->join('kons_master_paket e', 'e.id_paket = d.id_paket', 'left');
        $this->db->join('users f', 'f.id_user = a.input_by', 'left');
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
            $this->db->or_like('f.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->group_by('a.id_quotation');
        $this->db->order_by('a.input_date', 'desc');

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        // print_r($this->db->last_query());
        // exit;

        $hasil = [];

        $no = ($start + 1);
        foreach ($get_data->result() as $item) {

            $get_marketing = $this->dbhr->get_where('employees', ['id' => $item->id_marketing])->row();
            $nm_marketing = (!empty($get_marketing)) ? $get_marketing->name : '';

            $this->db->select('a.*');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_paket);
            $get_package = $this->db->get()->row();

            $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

            $get_customers = $this->db->get_where('customer', ['id_customer' => $item->id_customer])->row();
            $nm_customer = (!empty($get_customers)) ? $get_customers->nm_customer : '';

            // Quotation & Paket
            $quotation_paket = '<span style="font-weight: 700; color: #333;">' . $item->id_quotation . '</span>';
            if ($item->revisi > 0) {
                $quotation_paket .= ' <span class="badge bg-purple" style="font-size: 10px;">Rev ' . $item->revisi . '</span>';
            }
            if (!empty($item->tgl_quotation)) {
                $quotation_paket .= '<br><span class="text-muted" style="font-size: 11px;"><i class="fa fa-calendar"></i> ' . date('d F Y', strtotime($item->tgl_quotation)) . '</span>';
            }
            if (!empty($nm_paket)) {
                $quotation_paket .= '<br><span class="text-muted" style="font-size: 12px;"><i class="fa fa-briefcase"></i> ' . $nm_paket . '</span>';
            }

            // Customer
            $customer_info = '<span style="font-weight: 600; color: #333;">' . $nm_customer . '</span>';
            if ($item->sts_cust == 0) {
                $customer_info .= '<br><span class="label label-warning" style="font-size: 10px;">New</span>';
            } else {
                $customer_info .= '<br><span class="label label-info" style="font-size: 10px;">Repeat</span>';
            }

            // Marketing & Creator
            $marketing_creator = '';
            if (!empty($nm_marketing)) {
                $marketing_creator .= '<div><i class="fa fa-user-circle text-primary"></i> <b>Marketing:</b> ' . ucfirst($nm_marketing) . '</div>';
            }
            if (!empty($item->nm_lengkap)) {
                $marketing_creator .= '<div class="text-muted" style="font-size: 11px;"><i class="fa fa-user text-muted"></i> <b>Created:</b> ' . ucfirst($item->nm_lengkap) . ' (' . date('d/m/Y H:i', strtotime($item->input_date)) . ')</div>';
            }

            // Grand Total
            $grand_total = '<span style="font-weight: 700;">Rp ' . number_format($item->grand_total) . '</span>';

            // Status
            if ($item->sts_deal == '1') {
                $status_quot = '<span class="badge bg-green">Deal</span>';
            } elseif ($item->sts_quot == 1) {
                $status_quot = '<span class="badge bg-blue">Waiting Approval</span>';
            } elseif ($item->sts_quot == 2) {
                $status_quot = '<span class="badge bg-green">Approved</span>';
            } elseif ($item->sts_quot == 0) {
                $status_quot = '<span class="badge bg-red">Rejected</span>';
            } else {
                $status_quot = '-';
            }

            // Option / Action Dropdown
            $option = '
            <div class="btn-group penawaran-actions">
                <button
                    type="button"
                    class="penawaran-action-toggle dropdown-toggle"
                    title="Aksi Penawaran" aria-label="Aksi Penawaran" aria-haspopup="true"
                    data-toggle="dropdown"
                    aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg> <span>Aksi</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right penawaran-action-menu" role="menu">
            ';

            if ($this->viewPermission) {
                $option .= '
                    <li>
                        <a href="' . base_url('penawaran/view_penawaran/' . urlencode(str_replace('/', '|', $item->id_quotation))) . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg> View
                        </a>
                    </li>
                ';
            }

            if ($this->managePermission && ($item->sts_deal == null || $item->sts_deal == '')) {
                $option .= '
                    <li>
                        <a href="' . base_url('penawaran/edit_penawaran/' . urlencode(str_replace('/', '|', $item->id_quotation))) . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 5l5 5M4 20l4-1L20 7a3 3 0 0 0-4-4L4 15z"/></svg> Revisi
                        </a>
                    </li>
                ';
            }

            if ($this->deletePermission && ($item->sts_deal == null || $item->sts_deal == '')) {
                $option .= '
                    <li>
                        <a href="javascript:void(0);" class="del_penawaran penawaran-action-danger" data-id_penawaran="' . $item->id_quotation . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg> Delete
                        </a>
                    </li>
                ';
            }

            if ($this->managePermission && $item->sts_quot == '2' && ($item->sts_deal == null || $item->sts_deal == '') && ($this->auth->user_id() == '92' || $this->is_admin)) {
                $option .= '
                    <li>
                        <a href="javascript:void(0);" class="deal_penawaran" data-id_penawaran="' . $item->id_quotation . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12l4 4L19 6"/></svg> Deal
                        </a>
                    </li>
                ';
            }

            $option .= '
                    <li>
                        <a href="javascript:void(0);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 12h1"/></svg> Print
                        </a>
                    </li>
            ';
            $option .= '</ul></div>';

            $hasil[] = [
                'no' => $no,
                'quotation_paket' => $quotation_paket,
                'customer_info' => $customer_info,
                'marketing_creator' => $marketing_creator,
                'grand_total' => $grand_total,
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

    public function add_penawaran()
    {
        $this->auth->restrict($this->viewPermission);

        // $get_customer = $this->db->get_where('customers')->result();


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
        $this->db->where('a.flag_active', 'Y');
        $get_employees = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_konsultasi_header a');
        $get_package = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.tipe_biaya', 1);
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_akomodasi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.tipe_biaya', 2);
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_others = $this->db->get()->result();

        $this->db->select('a.id, a.isu_lingkungan, a.peraturan, a.waktu, a.harga_ssc, a.harga_lab');
        $this->db->from('kons_master_lab a');
        $this->db->where('a.deleted_by', null);
        $get_def_biaya_lab = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_tenaga_ahli a');
        $this->db->where('a.tipe_biaya', 1);
        $this->db->where('a.deleted_by', null);
        $get_def_subcont_tenaga_ahli = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_subcont_perusahaan a');
        $this->db->where('a.tipe_biaya', 1);
        $this->db->where('a.deleted_by', null);
        $get_def_subcont_perusahaan = $this->db->get()->result();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_company a');
        $get_company = $this->db->get()->result();

        $data = [
            'list_customers' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_package' => $get_package,
            'list_aktifitas' => $get_aktifitas,
            'list_def_akomodasi' => $get_def_biaya_akomodasi,
            'list_def_others' => $get_def_biaya_others,
            'list_def_lab' => $get_def_biaya_lab,
            'list_def_subcont_tenaga_ahli' => $get_def_subcont_tenaga_ahli,
            'list_def_subcont_perusahaan' => $get_def_subcont_perusahaan,
            'list_divisi' => $get_divisi,
            'list_employees' => $get_employees,
            'list_company' => $get_company
        ];

        $this->template->title('Create Quotation');
        $this->template->set($data);
        $this->template->render('add_penawaran');
    }

    public function add_penawaran_non()
    {
        $list_divisi = $this->Penawaran_model->list_divisi();
        $list_customer = $this->Penawaran_model->list_customer();
        $list_company = $this->Penawaran_model->list_company();
        $list_sales = $this->Penawaran_model->list_employee();
        $list_employee = $this->Penawaran_model->list_employee();

        $data = [
            'list_divisi' => $list_divisi,
            'list_customer' => $list_customer,
            'list_company' => $list_company,
            'list_sales' => $list_sales,
            'list_employee' => $list_employee,
        ];

        $this->template->title('Add Penawaran Non Konsultasi');
        $this->template->set($data);
        $this->template->render('add_penawaran_non');
    }

    public function change_customer()
    {
        $id_customer = $this->input->post('id_customer');

        $this->db->select('a.alamat as address, b.nm_pic as contact');
        $this->db->from('customer a');
        $this->db->join('customer_pic b', 'b.id_pic = a.id_pic', 'left');
        $this->db->where('a.id_customer', $id_customer);
        $get_cust = $this->db->get()->row();

        if (!empty($get_cust)) {
            $valid = 1;

            $contact = $get_cust->contact;
            $address = $get_cust->address;
        } else {
            $valid = 1;

            $contact = '';
            $address = '';
        }

        echo json_encode([
            'status' => $valid,
            'contact' => $contact,
            'address' => $address
        ]);
    }


    public function change_package()
    {
        $id_package = $this->input->post('id_package');

        // $get_konsultasi_detail = $this->db->get_where('kons_master_konsultasi_detail', ['id_konsultasi_h' => $id_package])->order_by('id_konsultasi_d', 'asc')->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_konsultasi_detail a');
        $this->db->where('a.id_konsultasi_h', $id_package);
        $this->db->order_by('a.id_konsultasi_d', 'asc');
        $get_konsultasi_detail = $this->db->get()->result();

        $hasil = '';

        $ttl_mandays = 0;
        $ttl_mandays_rate = 0;
        $ttl_price = 0;

        $no = 1;
        foreach ($get_konsultasi_detail as $item) {

            $get_check_point = $this->db->get_where('kons_master_check_point', ['id_aktifitas' => $item->id_aktifitas]);

            $hasil .= '<tr class="tr_aktifitas_' . $no . '">';

            $hasil .= '<td class="text-center tr_no">' . $no . '</td>';

            $hasil .= '<td class="text-left">';
            $hasil .= '<select class="form-control form-control-sm change_aktifitas select_nm_aktifitas_' . $no . '" name="dt_act[' . $no . '][nm_aktifitas]" style="max-width: 500px;" data-no="' . $no . '">';

            $hasil .= '<option value="">- Select Activity Name -</option>';

            $this->db->select('a.*');
            $this->db->from('kons_master_aktifitas a');
            $get_aktifitas = $this->db->get();

            foreach ($get_aktifitas->result() as $item_aktifitas) {
                $selected = '';
                if ($item_aktifitas->id_aktifitas == $item->id_aktifitas) {
                    $selected = 'selected';
                }

                $hasil .= '<option value="' . $item_aktifitas->id_aktifitas . '" ' . $selected . '>' . $item_aktifitas->nm_aktifitas . '</option>';
            }

            $mandays = $item->mandays;
            $mandays_rate = ($item->mandays >= 1) ? ($item->harga_aktifitas / $item->mandays) : ($item->harga_aktifitas);
            $total = ($item->mandays >= 1) ? ($mandays_rate * $item->mandays) : ($item->harga_aktifitas * $item->mandays);

            $hasil .= '</select>';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_' . $no . '" name="dt_act[' . $no . '][mandays]" value="' . $item->mandays . '" onchange="hitung_total_activity()">';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="hidden" name="dt_act[' . $no . '][min_mandays_rate]" value="' . $mandays_rate . '">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_rate_' . $no . '" name="dt_act[' . $no . '][mandays_rate]" value="' . $mandays_rate . '" onchange="hitung_total_activity()">';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_subcont_' . $no . '" name="dt_act[' . $no . '][mandays_subcont]" value="" onchange="hitung_total_activity()">';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_rate_subcont_' . $no . '" name="dt_act[' . $no . '][mandays_rate_subcont]" value="" onchange="hitung_total_activity()">';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_tandem_' . $no . '" name="dt_act[' . $no . '][mandays_tandem]" value="" onchange="hitung_total_activity()">';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_mandays_rate_tandem_' . $no . '" name="dt_act[' . $no . '][mandays_rate_tandem]" value="" onchange="hitung_total_activity()">';
            $hasil .= '</td>';


            $hasil .= '<td class="text-right">';
            $hasil .= '<input type="text" class="form-control form-control-sm auto_num text-right input_harga_aktifitas_' . $no . '" name="dt_act[' . $no . '][harga_aktifitas]" value="' . $total . '" onchange="hitung_total_activity()" readonly>';
            $hasil .= '</td>';

            $hasil .= '<td class="text-center">';
            $hasil .= '<button type="button" class="btn btn-sm btn-danger del_aktifitas" data-no="' . $no . '"><i class="fa fa-trash"></i></button>';
            $hasil .= '</td>';

            $hasil .= '</tr>';

            $no++;

            $ttl_mandays += $mandays;
            $ttl_mandays_rate += $mandays_rate;
            $ttl_price += $total;
        }

        echo json_encode([
            'hasil' => $hasil,
            'no' => $no,
            'ttl_mandays' => $ttl_mandays,
            'ttl_mandays_rate' => $ttl_mandays_rate,
            'ttl_price' => $ttl_price
        ]);
    }

    public function change_aktifitas()
    {
        $id_aktifitas = $this->input->post('id_aktifitas');

        $bobot = 0;
        $mandays = 0;
        $price = 0;
        $check_point = 0;
        $mandays_rate = 0;

        $get_aktifitas = $this->db->get_where('kons_master_aktifitas', ['id_aktifitas' => $id_aktifitas])->row();
        if (!empty($get_aktifitas)) {
            $bobot = $get_aktifitas->bobot;
            $mandays = $get_aktifitas->mandays;
            $price = $get_aktifitas->harga_aktifitas;
            $mandays_rate = $get_aktifitas->harga_aktifitas;
        }

        $get_check_point = $this->db->get_where('kons_master_check_point', ['id_aktifitas' => $id_aktifitas])->num_rows();
        $check_point = $get_check_point;

        echo json_encode([
            'mandays' => $mandays,
            'mandays_rate' => $mandays_rate,
            'price' => ($mandays * $mandays_rate)
        ]);
    }

    public function hitung_ttl_check_point()
    {
        $id_aktifitas = $this->input->post('id_aktifitas');

        $this->db->select('a.*');
        $this->db->from('kons_master_check_point a');
        $this->db->where_in('a.id_aktifitas', $id_aktifitas);
        $get_check_point = $this->db->get()->num_rows();

        echo $get_check_point;
    }

    public function save_penawaran()
    {
        $post = $this->input->post();

        $this->db->trans_begin();
        $valid = 1;
        $msg = '';

        try {
            $uploads = [
                'upload_proposal' => './uploads/proposal_penawaran/',
                'upload_tahapan'  => './uploads/tahapan_penawaran/',
                'upload_po'       => './uploads/po_penawaran/'
            ];

            $file_names = ['upload_proposal' => '', 'upload_tahapan' => '', 'upload_po' => ''];

            foreach ($uploads as $field => $path) {
                if (!empty($_FILES[$field]['name'])) {
                    $this->upload->initialize([
                        'upload_path'   => $path,
                        'allowed_types' => '*',
                        'remove_spaces' => TRUE,
                        'encrypt_name'  => TRUE
                    ]);
                    if ($this->upload->do_upload($field)) {
                        $file_names[$field] = $this->upload->data('file_name');
                    }
                }
            }

            $tipe_info_awal = '';
            $detail_info_awal = '';
            $tipe_penawaran = '';

            if (isset($post['check_info_awal_sales'])) {
                $tipe_info_awal = 'Sales';
                $detail_info_awal = $post['informasi_awal_sales'];
                $tipe_penawaran = 'STM/';
            }
            if (isset($post['check_info_awal_medsos'])) {
                $tipe_info_awal = 'Medsos';
                $detail_info_awal = $post['informasi_awal_medsos'];
                $tipe_penawaran = 'STM/IC-MKT/';
            }
            if (isset($post['check_info_awal_others'])) {
                $tipe_info_awal = 'Others';
                $detail_info_awal = $post['informasi_awal_others'];
                $tipe_penawaran = 'STM/INT/';
            }

            $id_penawaran = generateNoPenawaran($post['employee_code'] ?? '', $tipe_penawaran);
            $id_history   = $this->Penawaran_model->generate_history_id();
            $user_id      = $this->auth->user_id();
            $datetime     = date('Y-m-d H:i:s');

            $arr_insert = [
                'id_quotation'          => $id_penawaran,
                'tipe_penawaran'        => $tipe_penawaran,
                'tgl_quotation'         => $post['tgl_quotation'] ?? null,
                'id_customer'           => $post['customer'] ?? null,
                'id_marketing'          => $post['marketing'] ?? null,
                'nm_pic'                => $post['pic'] ?? null,
                'address'               => $post['address'] ?? null,
                'id_paket'              => $post['consultation_package'] ?? null,
                'upload_proposal'       => $file_names['upload_proposal'],
                'upload_tahapan'        => $file_names['upload_tahapan'],
                'upload_po'             => $file_names['upload_po'],
                'sts_cust'              => $post['sts_cust'] ?? 0,
                'sts_quot'              => 1,
                'grand_total'           => $post['grand_total'] ?? 0,
                'ppn'                   => isset($post['include_ppn']) ? 1 : 0,
                'persen_disc'           => str_replace(',', '', $post['persen_disc'] ?? 0),
                'nilai_disc'            => str_replace(',', '', $post['nilai_disc'] ?? 0),
                'tipe_informasi_awal'   => $tipe_info_awal,
                'detail_informasi_awal' => $detail_info_awal,
                'id_divisi'             => $post['divisi'] ?? null,
                'nm_divisi'             => $post['nm_divisi'] ?? null,
                'total_mandays'         => $post['ttl_total_mandays'] ?? 0,
                'mandays_subcont'       => $post['ttl_mandays_subcont'] ?? 0,
                'mandays_tandem'        => $post['ttl_mandays_tandem'] ?? 0,
                'mandays_internal'      => $post['ttl_total_mandays'] ?? 0,
                'mandays_rate'          => $post['ttl_mandays_rate'] ?? 0,
                'input_by'              => $user_id,
                'input_date'            => $datetime,
                'company'               => $post['company'] ?? null,
                'nm_company'            => $post['nm_company'] ?? null
            ];

            if (!$this->db->insert('kons_tr_penawaran', $arr_insert)) {
                throw new Exception('Failed to insert main quotation data.');
            }

            $arr_insert_history = $arr_insert;
            unset($arr_insert_history['company'], $arr_insert_history['nm_company']);
            $arr_insert_history['id_history'] = $id_history;

            if (!$this->db->insert('kons_tr_penawaran_history', $arr_insert_history)) {
                throw new Exception('Failed to insert history quotation data.');
            }

            $batch_data = [
                'kons_tr_penawaran_aktifitas' => [],
                'kons_tr_penawaran_akomodasi' => [],
                'kons_tr_penawaran_others'    => [],
                'kons_tr_penawaran_lab'       => [],
                'kons_tr_penawaran_subcont_tenaga_ahli' => [],
                'kons_tr_penawaran_subcont_perusahaan'  => []
            ];

            if (isset($post['dt_act']) && is_array($post['dt_act'])) {
                foreach ($post['dt_act'] as $item) {
                    if (!empty($item['nm_aktifitas'])) {
                        $batch_data['kons_tr_penawaran_aktifitas'][] = [
                            'id_penawaran'         => $id_penawaran,
                            'id_aktifitas'         => $item['nm_aktifitas'],
                            'mandays'              => str_replace(',', '', $item['mandays'] ?? 0),
                            'mandays_rate'         => str_replace(',', '', $item['mandays_rate'] ?? 0),
                            'mandays_subcont'      => str_replace(',', '', $item['mandays_subcont'] ?? 0),
                            'mandays_rate_subcont' => str_replace(',', '', $item['mandays_rate_subcont'] ?? 0),
                            'mandays_tandem'       => str_replace(',', '', $item['mandays_tandem'] ?? 0),
                            'mandays_rate_tandem'  => str_replace(',', '', $item['mandays_rate_tandem'] ?? 0),
                            'harga_aktifitas'      => str_replace(',', '', $item['harga_aktifitas'] ?? 0),
                            'total_aktifitas'      => str_replace(',', '', $item['harga_aktifitas'] ?? 0),
                            'input_by'             => $user_id,
                            'input_date'           => $datetime
                        ];
                    }
                }
            }

            $detail_configs = [
                'dt_ako' => [
                    'table'   => 'kons_tr_penawaran_akomodasi',
                    'id_post' => 'id_akomodasi',
                    'ket'     => 'keterangan_akomodasi',
                    'fields'  => ['id_akomodasi' => 'id_item', 'qty_akomodasi' => 'qty', 'harga_akomodasi' => 'price_unit', 'total_akomodasi' => 'total']
                ],
                'dt_oth' => [
                    'table'   => 'kons_tr_penawaran_others',
                    'id_post' => 'id_others',
                    'ket'     => 'keterangan_others',
                    'fields'  => ['id_others' => 'id_item', 'qty_others' => 'qty', 'harga_others' => 'price_unit', 'total_others' => 'total', 'harga_others_budget' => 'price_unit_budget', 'total_budget_others' => 'total_budget']
                ],
                'dt_lab' => [
                    'table'   => 'kons_tr_penawaran_lab',
                    'id_post' => 'id_lab',
                    'ket'     => 'keterangan_lab',
                    'fields'  => ['id_lab' => 'id_item', 'qty_lab' => 'qty', 'harga_lab' => 'price_unit', 'total_lab' => 'total', 'harga_lab_budget' => 'price_unit_budget', 'total_lab_budget' => 'total_budget']
                ],
                'dt_subcont_tenaga_ahli' => [
                    'table'   => 'kons_tr_penawaran_subcont_tenaga_ahli',
                    'id_post' => 'id_subcont_tenaga_ahli',
                    'ket'     => 'keterangan_subcont_tenaga_ahli',
                    'fields'  => ['id_subcont_tenaga_ahli' => 'id_item', 'qty_subcont_tenaga_ahli' => 'qty', 'harga_subcont_tenaga_ahli' => 'price_unit', 'total_subcont_tenaga_ahli' => 'total', 'harga_subcont_tenaga_ahli_budget' => 'price_unit_budget', 'total_subcont_tenaga_ahli_budget' => 'total_budget']
                ],
                'dt_subcont_perusahaan' => [
                    'table'   => 'kons_tr_penawaran_subcont_perusahaan',
                    'id_post' => 'id_subcont_perusahaan',
                    'ket'     => 'keterangan_subcont_perusahaan',
                    'fields'  => ['id_subcont_perusahaan' => 'id_item', 'qty_subcont_perusahaan' => 'qty', 'harga_subcont_perusahaan' => 'price_unit', 'total_subcont_perusahaan' => 'total', 'harga_subcont_perusahaan_budget' => 'price_unit_budget', 'total_subcont_perusahaan_budget' => 'total_budget']
                ]
            ];

            foreach ($detail_configs as $post_key => $cfg) {
                if (isset($post[$post_key]) && is_array($post[$post_key])) {
                    foreach ($post[$post_key] as $item) {
                        if (!empty($item[$cfg['id_post']])) {
                            $row = [
                                'id_penawaran' => $id_penawaran,
                                'input_by'     => $user_id,
                                'input_date'   => $datetime,
                                'keterangan'   => $item[$cfg['ket']] ?? ''
                            ];
                            foreach ($cfg['fields'] as $post_field => $db_field) {
                                $row[$db_field] = str_replace(',', '', $item[$post_field] ?? 0);
                            }
                            $batch_data[$cfg['table']][] = $row;
                        }
                    }
                }
            }

            foreach ($batch_data as $table => $data) {
                if (!empty($data)) {
                    if (!$this->db->insert_batch($table, $data)) {
                        throw new Exception("Failed to insert into {$table}.");
                    }

                    $history_data = array_map(function ($row) use ($id_history) {
                        $row['id_history'] = $id_history;
                        return $row;
                    }, $data);

                    if (!$this->db->insert_batch("{$table}_history", $history_data)) {
                        throw new Exception("Failed to insert into {$table}_history.");
                    }
                }
            }

            if ($this->db->trans_status() === false) {
                throw new Exception('Database transaction failed.');
            }

            $this->db->trans_commit();
            $msg = 'Data has been successfully saved !';
            $this->Penawaran_model->history_penawaran($id_penawaran);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $valid = 0;
            $msg = 'Please try again later ! ' . $e->getMessage();
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    public function del_penawaran()
    {
        $id_penawaran = $this->input->post('id_penawaran');

        $this->db->trans_begin();

        $this->db->delete('kons_tr_penawaran_subcont_perusahaan', ['id_penawaran' => $id_penawaran]);
        $this->db->delete('kons_tr_penawaran_subcont_tenaga_ahli', ['id_penawaran' => $id_penawaran]);
        $this->db->delete('kons_tr_penawaran_others', ['id_penawaran' => $id_penawaran]);
        $this->db->delete('kons_tr_penawaran_aktifitas', ['id_penawaran' => $id_penawaran]);
        $this->db->delete('kons_tr_penawaran_akomodasi', ['id_penawaran' => $id_penawaran]);
        $this->db->delete('kons_tr_penawaran', ['id_quotation' => $id_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $msg = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    public function update_penawaran()
    {
        $post = $this->input->post();
        $this->db->trans_begin();

        try {
            $id_penawaran = $post['id_penawaran'];

            // 1. Delete existing details
            $tables_to_delete = [
                'kons_tr_penawaran_aktifitas',
                'kons_tr_penawaran_akomodasi',
                'kons_tr_penawaran_others',
                'kons_tr_penawaran_lab',
                'kons_tr_penawaran_subcont_tenaga_ahli',
                'kons_tr_penawaran_subcont_perusahaan'
            ];
            foreach ($tables_to_delete as $table) {
                $this->db->delete($table, ['id_penawaran' => $id_penawaran]);
            }

            // 2. Handle File Uploads
            $upload_configs = [
                'upload_proposal' => './uploads/proposal_penawaran/',
                'upload_tahapan'  => './uploads/tahapan_penawaran/',
                'upload_po'       => './uploads/po_penawaran/'
            ];

            $upload_data = [];
            foreach ($upload_configs as $input_name => $path) {
                $this->upload->initialize([
                    'upload_path'   => $path,
                    'allowed_types' => '*',
                    'remove_spaces' => TRUE,
                    'encrypt_name'  => TRUE
                ]);

                if ($this->upload->do_upload($input_name)) {
                    $upload_data[$input_name] = $this->upload->data('file_name');
                }
            }

            // 3. Determine 'informasi awal'
            $tipe_info_awal = '';
            $detail_info_awal = '';

            if (isset($post['check_info_awal_sales'])) {
                $tipe_info_awal = 'Sales';
                $detail_info_awal = $post['informasi_awal_sales'];
            } elseif (isset($post['check_info_awal_medsos'])) {
                $tipe_info_awal = 'Medsos';
                $detail_info_awal = $post['informasi_awal_medsos'];
            } elseif (isset($post['check_info_awal_others'])) {
                $tipe_info_awal = 'Others';
                $detail_info_awal = $post['informasi_awal_others'];
            }

            // 4. Prepare Main Update Data
            $arr_update = [
                'tgl_quotation'         => $post['tgl_quotation'],
                'id_customer'           => $post['customer'],
                'id_marketing'          => $post['marketing'],
                'nm_pic'                => $post['pic'],
                'address'               => $post['address'],
                'id_paket'              => $post['consultation_package'],
                'ppn'                   => isset($post['include_ppn']) ? 1 : 0,
                'persen_disc'           => str_replace(',', '', $post['persen_disc']),
                'nilai_disc'            => str_replace(',', '', $post['nilai_disc']),
                'grand_total'           => $post['grand_total'],
                'tipe_informasi_awal'   => $tipe_info_awal,
                'detail_informasi_awal' => $detail_info_awal,
                'id_divisi'             => $post['divisi'],
                'nm_divisi'             => $post['nm_divisi'],
                'total_mandays'         => $post['ttl_total_mandays'],
                'mandays_subcont'       => $post['ttl_mandays_subcont'],
                'mandays_tandem'        => $post['ttl_mandays_tandem'],
                'mandays_internal'      => $post['ttl_total_mandays'],
                'mandays_rate'          => $post['ttl_mandays_rate'],
                'sts_quot'              => 1,
                'sts_deal'              => null,
                'revisi'                => ($post['revisi'] + 1),
                'company'               => $post['company'],
                'nm_company'            => $post['nm_company'],
                'updated_by'            => $this->auth->user_id(),
                'updated_date'          => date('Y-m-d H:i:s')
            ];

            // Merge uploaded files if any exist
            if (!empty($upload_data)) {
                $arr_update = array_merge($arr_update, $upload_data);
            }

            // Update main table
            if (!$this->db->update('kons_tr_penawaran', $arr_update, ['id_quotation' => $id_penawaran])) {
                throw new Exception("Error update kons_tr_penawaran");
            }

            // 5. Prepare Detailed Data
            $user_id = $this->auth->user_id();
            $datetime_now = date('Y-m-d H:i:s');

            $arr_insert_act = [];
            if (!empty($post['dt_act'])) {
                foreach ($post['dt_act'] as $item) {
                    $arr_insert_act[] = [
                        'id_penawaran'         => $id_penawaran,
                        'id_aktifitas'         => $item['nm_aktifitas'],
                        'mandays'              => str_replace(',', '', $item['mandays']),
                        'mandays_rate'         => str_replace(',', '', $item['mandays_rate']),
                        'mandays_subcont'      => str_replace(',', '', $item['mandays_subcont']),
                        'mandays_rate_subcont' => str_replace(',', '', $item['mandays_rate_subcont']),
                        'mandays_tandem'       => str_replace(',', '', $item['mandays_tandem']),
                        'mandays_rate_tandem'  => str_replace(',', '', $item['mandays_rate_tandem']),
                        'harga_aktifitas'      => str_replace(',', '', $item['harga_aktifitas']),
                        'total_aktifitas'      => str_replace(',', '', $item['harga_aktifitas']),
                        'input_by'             => $user_id,
                        'input_date'           => $datetime_now
                    ];
                }
            }

            $arr_insert_ako = [];
            if (!empty($post['dt_ako'])) {
                foreach ($post['dt_ako'] as $item) {
                    $arr_insert_ako[] = [
                        'id_penawaran' => $id_penawaran,
                        'id_item'      => $item['id_akomodasi'],
                        'qty'          => str_replace(',', '', $item['qty_akomodasi']),
                        'price_unit'   => str_replace(',', '', $item['harga_akomodasi']),
                        'total'        => str_replace(',', '', $item['total_akomodasi']),
                        'keterangan'   => $item['keterangan_akomodasi'],
                        'input_by'     => $user_id,
                        'input_date'   => $datetime_now
                    ];
                }
            }

            $arr_insert_oth = [];
            if (!empty($post['dt_oth'])) {
                foreach ($post['dt_oth'] as $item) {
                    $arr_insert_oth[] = [
                        'id_penawaran'      => $id_penawaran,
                        'id_item'           => $item['id_others'],
                        'qty'               => str_replace(',', '', $item['qty_others']),
                        'price_unit'        => str_replace(',', '', $item['harga_others']),
                        'total'             => str_replace(',', '', $item['total_others']),
                        'price_unit_budget' => str_replace(',', '', $item['harga_others_budget']),
                        'total_budget'      => str_replace(',', '', $item['total_budget_others']),
                        'keterangan'        => $item['keterangan_others'],
                        'input_by'          => $user_id,
                        'input_date'        => $datetime_now
                    ];
                }
            }

            $arr_insert_lab = [];
            if (!empty($post['dt_lab'])) {
                foreach ($post['dt_lab'] as $item) {
                    $arr_insert_lab[] = [
                        'id_penawaran'      => $id_penawaran,
                        'id_item'           => $item['id_lab'],
                        'qty'               => str_replace(',', '', $item['qty_lab']),
                        'price_unit'        => str_replace(',', '', $item['harga_lab']),
                        'total'             => str_replace(',', '', $item['total_lab']),
                        'price_unit_budget' => str_replace(',', '', $item['harga_lab_budget']),
                        'total_budget'      => str_replace(',', '', $item['total_lab_budget']),
                        'keterangan'        => $item['keterangan_lab'],
                        'input_by'          => $user_id,
                        'input_date'        => $datetime_now
                    ];
                }
            }

            $arr_insert_subcont_ta = [];
            if (!empty($post['dt_subcont_tenaga_ahli'])) {
                foreach ($post['dt_subcont_tenaga_ahli'] as $item) {
                    $arr_insert_subcont_ta[] = [
                        'id_penawaran'      => $id_penawaran,
                        'id_item'           => $item['id_subcont_tenaga_ahli'],
                        'qty'               => str_replace(',', '', $item['qty_subcont_tenaga_ahli']),
                        'price_unit'        => str_replace(',', '', $item['harga_subcont_tenaga_ahli']),
                        'total'             => str_replace(',', '', $item['total_subcont_tenaga_ahli']),
                        'price_unit_budget' => str_replace(',', '', $item['harga_subcont_tenaga_ahli_budget']),
                        'total_budget'      => str_replace(',', '', $item['total_subcont_tenaga_ahli_budget']),
                        'keterangan'        => $item['keterangan_subcont_tenaga_ahli'],
                        'input_by'          => $user_id,
                        'input_date'        => $datetime_now
                    ];
                }
            }

            $arr_insert_subcont_comp = [];
            if (!empty($post['dt_subcont_perusahaan'])) {
                foreach ($post['dt_subcont_perusahaan'] as $item) {
                    $arr_insert_subcont_comp[] = [
                        'id_penawaran'      => $id_penawaran,
                        'id_item'           => $item['id_subcont_perusahaan'],
                        'qty'               => str_replace(',', '', $item['qty_subcont_perusahaan']),
                        'price_unit'        => str_replace(',', '', $item['harga_subcont_perusahaan']),
                        'total'             => str_replace(',', '', $item['total_subcont_perusahaan']),
                        'price_unit_budget' => str_replace(',', '', $item['harga_subcont_perusahaan_budget']),
                        'total_budget'      => str_replace(',', '', $item['total_subcont_perusahaan_budget']),
                        'keterangan'        => $item['keterangan_subcont_perusahaan'],
                        'input_by'          => $user_id,
                        'input_date'        => $datetime_now
                    ];
                }
            }

            // 6. Insert All Details Batch Data
            $batch_inserts = [
                'kons_tr_penawaran_aktifitas'            => $arr_insert_act,
                'kons_tr_penawaran_akomodasi'            => $arr_insert_ako,
                'kons_tr_penawaran_others'               => $arr_insert_oth,
                'kons_tr_penawaran_lab'                  => $arr_insert_lab,
                'kons_tr_penawaran_subcont_tenaga_ahli'  => $arr_insert_subcont_ta,
                'kons_tr_penawaran_subcont_perusahaan'   => $arr_insert_subcont_comp
            ];

            foreach ($batch_inserts as $table => $data) {
                if (!empty($data)) {
                    if (!$this->db->insert_batch($table, $data)) {
                        throw new Exception("Error inserting {$table}");
                    }
                }
            }

            // 7. Verify Transaction Status and Commit
            if ($this->db->trans_status() === false) {
                throw new Exception("Transaction status failed");
            }

            $this->db->trans_commit();

            $this->Penawaran_model->history_penawaran($id_penawaran);

            echo json_encode([
                'status' => 1,
                'msg'    => 'Data has been successfully saved !',
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 0,
                'msg'    => 'Please try again later ! Error: ' . $e->getMessage()
            ]);
        }
    }

    public function deal_penawaran()
    {
        $id_penawaran = $this->input->post('id_penawaran');

        $this->db->trans_begin();

        $this->db->update('kons_tr_penawaran', ['sts_deal' => 1], ['id_quotation' => $id_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $msg = 'Quotation status has changed to Deal !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg,
        ]);
    }

    public function get_list_def_akomodasi()
    {
        $this->db->select('a.*');
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.tipe_biaya', 1);
        $get_data = $this->db->get()->result();

        $hasil = '';

        foreach ($get_data as $item) {
            $hasil .= '<option value="' . $item->id . '">' . $item->nm_biaya . '</option>';
        }

        echo json_encode([
            'hasil' => $hasil
        ]);
    }

    public function get_nm_divisi()
    {
        $post = $this->input->post();

        $id_divisi = $post['id_divisi'];

        $get_divisi = $this->db->get_where('ms_department', ['id' => $id_divisi])->row();

        $nm_divisi = (!empty($get_divisi)) ? $get_divisi->nama : '';

        echo json_encode([
            'nm_divisi' => $nm_divisi
        ]);
    }

    public function change_lab()
    {
        $id_lab = $this->input->post('id_lab');

        $get_lab = $this->db->get_where('kons_master_lab', array('id' => $id_lab))->row();

        $harga_ssc = (!empty($get_lab)) ? $get_lab->harga_ssc : 0;
        $harga_lab = (!empty($get_lab)) ? $get_lab->harga_lab : 0;

        echo json_encode([
            'harga_ssc' => $harga_ssc,
            'harga_lab' => $harga_lab
        ]);
    }

    public function check_sts_customer()
    {
        $cust = $this->input->post('cust');

        $get_penawaran_cust = $this->db->get_where('kons_tr_penawaran', array('id_customer' => $cust))->result();

        if (count($get_penawaran_cust) > 0) {
            $sts_cust = 1;
        } else {
            $sts_cust = 0;
        }

        echo json_encode([
            'sts_cust' => $sts_cust
        ]);
    }

    public function get_initials()
    {
        $sales = $this->input->post('sales');
        $get_sales = $this->dbhr->get_where('employees', ['id' => $sales])->row();

        $employee_code = '';
        if (!empty($get_sales)) {
            $employee_code = get_initials($get_sales->name);
        }

        echo json_encode([
            'initial_sales' => $employee_code
        ]);
    }

    public function get_company()
    {
        $company = $this->input->post('company');

        $get_company_nm = $this->db->get_where('kons_tr_company', ['id' => $company])->row();

        $nm_company = '';
        if (!empty($get_company_nm)) {
            $nm_company = $get_company_nm->nm_company;
        }

        echo json_encode([
            'company_nm' => $nm_company
        ]);
    }

    public function save_penawaran_non_konsultasi()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        try {

            $id_penawaran = generateNoPenawaranNon();

            $get_customer = $this->db->get_where('customer', ['id_customer' => $post['customer']])->row();
            $nm_customer = (!empty($get_customer->nm_customer)) ? $get_customer->nm_customer : '';

            $get_divisi = $this->dbhr->get_where('divisions', ['id' => $post['divisi']])->row();
            $nm_divisi = (!empty($get_divisi->name)) ? $get_divisi->name : '';

            $get_company = $this->dbhr->get_where('companies', ['id' => $post['company']])->row();
            $nm_company = (!empty($get_company->name)) ? $get_company->name : '';

            $get_pic_penawaran = $this->dbhr->get_where('employees', ['id' => $post['pic_penawaran']])->row();
            $nm_pic_penawaran = (!empty($get_pic_penawaran->name)) ? $get_pic_penawaran->name : '';

            $tipe_informasi_awal = '';
            $detail_informasi_awal = '';
            if (isset($post['informasi_awal_sales'])) {
                $tipe_informasi_awal = 'Sales';
                $get_sales = $this->dbhr->get_where('employees', ['id' => $post['sales_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_sales->name)) ? $get_sales->name : '';
            }
            if (isset($post['informasi_awal_medsos'])) {
                $tipe_informasi_awal = 'Medsos';
                $detail_informasi_awal = $post['medsos_informasi_awal'];
            }
            if (isset($post['informasi_awal_others'])) {
                $tipe_informasi_awal = 'Others';
                $get_employees = $this->dbhr->get_where('employees', ['id' => $post['others_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_employees->name)) ? $get_employees->name : '';
            }

            $arr_insert = [
                'id_penawaran' => $id_penawaran,
                'tgl_quotation' => $post['tgl_penawaran'],
                'id_customer' => $post['customer'],
                'nm_customer' => $nm_customer,
                'pic' => $post['pic'],
                'id_divisi' => $post['divisi'],
                'nm_divisi' => $nm_divisi,
                'id_company' => $post['company'],
                'nm_company' => $nm_company,
                'address' => $post['address'],
                'pic_penawaran' => $post['pic_penawaran'],
                'nm_pic_penawaran' => $nm_pic_penawaran,
                'tipe_informasi_awal' => $tipe_informasi_awal,
                'detail_informasi_awal' => $detail_informasi_awal,
                'keterangan_penawaran' => $post['keterangan_penawaran'],
                'biaya_kirim' => str_replace(',', '', $post['biaya_kirim']),
                'subtotal' => $post['subtotal'],
                'nominal_disc' => str_replace(',', '', $post['disc_nominal']),
                'persen_disc' => str_replace(',', '', $post['disc_persen']),
                'persen_ppn' => $post['persen_ppn'],
                'ppn' => $post['ppn'],
                'grand_total' => $post['grand_total'],
                'sts_quot' => '0',
                'sts_deal' => '0',
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];

            $insert_header = $this->db->insert('kons_tr_penawaran_non_konsultasi', $arr_insert);
            if (!$insert_header) {
                throw new Exception($this->db->error()['message']);
            }


            if (isset($post['detail'])) {
                $arr_detail = [];

                foreach ($post['detail'] as $item_detail) {
                    $arr_detail[] = [
                        'id_header' => $id_penawaran,
                        'nm_item' => $item_detail['item'],
                        'qty' => $item_detail['qty'],
                        'harga' => str_replace(',', '', $item_detail['harga']),
                        'total' => str_replace(',', '', $item_detail['total']),
                        'input_by' => $this->auth->user_id(),
                        'input_at' => date('Y-m-d H:i:s')
                    ];
                }

                $this->db->insert_batch('kons_tr_detail_penawaran_non_konsultasi', $arr_detail);
            }

            $this->db->trans_commit();

            $this->output->set_status_header(200);
            echo json_encode([
                'msg' => 'Data has been saved !'
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function update_penawaran_non_konsultasi()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        try {
            $get_customer = $this->db->get_where('customer', ['id_customer' => $post['customer']])->row();
            $nm_customer = (!empty($get_customer->nm_customer)) ? $get_customer->nm_customer : '';

            $get_divisi = $this->dbhr->get_where('divisions', ['id' => $post['divisi']])->row();
            $nm_divisi = (!empty($get_divisi->name)) ? $get_divisi->name : '';

            $get_company = $this->dbhr->get_where('companies', ['id' => $post['company']])->row();
            $nm_company = (!empty($get_company->name)) ? $get_company->name : '';

            $get_pic_penawaran = $this->dbhr->get_where('employees', ['id' => $post['pic_penawaran']])->row();
            $nm_pic_penawaran = (!empty($get_pic_penawaran->name)) ? $get_pic_penawaran->name : '';

            $tipe_informasi_awal = '';
            $detail_informasi_awal = '';
            if (isset($post['informasi_awal_sales'])) {
                $tipe_informasi_awal = 'Sales';
                $get_sales = $this->dbhr->get_where('employees', ['id' => $post['sales_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_sales->name)) ? $get_sales->name : '';
            }
            if (isset($post['informasi_awal_medsos'])) {
                $tipe_informasi_awal = 'Medsos';
                $detail_informasi_awal = $post['medsos_informasi_awal'];
            }
            if (isset($post['informasi_awal_others'])) {
                $tipe_informasi_awal = 'Others';
                $get_employees = $this->dbhr->get_where('employees', ['id' => $post['others_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_employees->name)) ? $get_employees->name : '';
            }

            $get_customer = $this->db->get_where('customer', ['id_customer' => $post['customer']])->row();
            $nm_customer = (!empty($get_customer->nm_customer)) ? $get_customer->nm_customer : '';

            $get_divisi = $this->dbhr->get_where('divisions', ['id' => $post['divisi']])->row();
            $nm_divisi = (!empty($get_divisi->name)) ? $get_divisi->name : '';

            $get_company = $this->dbhr->get_where('companies', ['id' => $post['company']])->row();
            $nm_company = (!empty($get_company->name)) ? $get_company->name : '';

            $tipe_informasi_awal = '';
            $detail_informasi_awal = '';
            if (isset($post['informasi_awal_sales'])) {
                $tipe_informasi_awal = 'Sales';
                $get_sales = $this->dbhr->get_where('employees', ['id' => $post['sales_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_sales->name)) ? $get_sales->name : '';
            }
            if (isset($post['informasi_awal_medsos'])) {
                $tipe_informasi_awal = 'Medsos';
                $detail_informasi_awal = $post['medsos_informasi_awal'];
            }
            if (isset($post['informasi_awal_others'])) {
                $tipe_informasi_awal = 'Others';
                $get_employees = $this->dbhr->get_where('employees', ['id' => $post['others_informasi_awal']])->row();
                $detail_informasi_awal = (!empty($get_employees->name)) ? $get_employees->name : '';
            }

            $arr_update = [
                'tgl_quotation' => $post['tgl_penawaran'],
                'id_customer' => $post['customer'],
                'nm_customer' => $nm_customer,
                'pic' => $post['pic'],
                'id_divisi' => $post['divisi'],
                'nm_divisi' => $nm_divisi,
                'id_company' => $post['company'],
                'nm_company' => $nm_company,
                'address' => $post['address'],
                'pic_penawaran' => $post['pic_penawaran'],
                'nm_pic_penawaran' => $nm_pic_penawaran,
                'tipe_informasi_awal' => $tipe_informasi_awal,
                'detail_informasi_awal' => $detail_informasi_awal,
                'keterangan_penawaran' => $post['keterangan_penawaran'],
                'biaya_kirim' => str_replace(',', '', $post['biaya_kirim']),
                'subtotal' => $post['subtotal'],
                'persen_disc' => str_replace(',', '', $post['disc_persen']),
                'nominal_disc' => str_replace(',', '', $post['disc_nominal']),
                'persen_ppn' => $post['persen_ppn'],
                'ppn' => $post['ppn'],
                'grand_total' => $post['grand_total'],
                'sts_quot' => '0',
                'sts_deal' => '0',
                'updated_by' => $this->auth->user_id(),
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $update_penawaran_non_kons = $this->db->update('kons_tr_penawaran_non_konsultasi', $arr_update, ['id_penawaran' => $post['id_penawaran']]);

            $this->db->trans_commit();

            $this->output->set_status_header(200);
            echo json_encode([
                'msg' => 'Data has been updated !'
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => "There's an error occured, Please try again later !"
            ]);
        }
    }

    public function add_detail_penawaran_non_konsultasi()
    {
        $no_detail = $this->input->post('no_detail');

        $item = '<tr class="item_detail_' . $no_detail . '">';
        $item .= '<td class="text-center">' . $no_detail . '</td>';
        $item .= '<td>';
        $item .= '<textarea class="form-control form-control-sm" name="detail[' . $no_detail . '][item]"></textarea>';
        $item .= '</td>';
        $item .= '<td>';
        $item .= '<input type="text" class="form-control form-control-sm text-right auto_num qty_' . $no_detail . '" name="detail[' . $no_detail . '][qty]" onchange="hitung_total_detail(' . $no_detail . ')">';
        $item .= '</td>';
        $item .= '<td>';
        $item .= '<input type="text" class="form-control form-control-sm text-right auto_num harga_' . $no_detail . '" name="detail[' . $no_detail . '][harga]" onchange="hitung_total_detail(' . $no_detail . ')">';
        $item .= '</td>';
        $item .= '<td>';
        $item .= '<input type="text" class="form-control form-control-sm text-right auto_num total_' . $no_detail . '" name="detail[' . $no_detail . '][total]" readonly>';
        $item .= '</td>';
        $item .= '<td>';
        $item .= '<button type="button" class="btn btn-sm btn-danger" onclick="del_item(' . $no_detail . ')" title="Delete Item"><i class="fa fa-trash"></i></button>';
        $item .= '</td>';
        $item .= '</tr>';

        echo json_encode([
            'item' => $item
        ]);
    }

    public function get_detail_customer()
    {
        $id_customer = $this->input->get('customer');

        try {
            $get_customer = $this->db->get_where('customer', ['id_customer' => $id_customer])->row();

            $get_customer_pic = $this->db->get_where('customer_pic', ['id_pic' => $get_customer->id_pic])->row();

            $response = [
                'address' => (!empty($get_customer->alamat)) ? $get_customer->alamat : '',
                'pic' => (!empty($get_customer_pic->nm_pic)) ? $get_customer_pic->nm_pic : ''
            ];

            $this->output->set_status_header(200);
            echo json_encode($response);
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => "There's an error occured, Please try again later !"
            ]);
        }
    }

    private function render_status_penawaran_non_kons($item)
    {
        $status = '<span class="badge bg-blue">Waiting Approval</span>';
        if ($item->sts_quot == '1') {
            $status = '<span class="badge bg-green">Approved</span>';
        }
        if ($item->sts_quot == '2') {
            $status = '<span class="badge bg-red">Rejected</span>';
        }

        if ($item->sts_quot == '1' && $item->sts_deal == '1') {
            $status = '<span class="badge bg-green">Deal</span>';
        }

        return $status;
    }

    private function render_action_non_kons($item)
    {
        $action = '
        <div class="btn-group penawaran-actions">
            <button type="button" class="penawaran-action-toggle dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Aksi Penawaran" aria-label="Aksi Penawaran" aria-haspopup="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg> <span>Aksi</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right penawaran-action-menu" role="menu">
        ';

        if (has_permission($this->viewPermission)) {
            $action .= '
                <li>
                    <a href="' . base_url('penawaran/view_non_kons/' . $item->id_penawaran) . '">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg> View
                    </a>
                </li>
            ';

            if ($item->sts_quot == '1') {
                $action .= '
                    <li>
                        <a href="javascript:void(0);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 12h1"/></svg> Print
                        </a>
                    </li>
                ';
            }
        }

        if (has_permission($this->managePermission)) {
            if ($item->sts_quot !== '1') {
                $action .= '
                    <li>
                        <a href="' . base_url('penawaran/edit_non_kons/' . $item->id_penawaran) . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 5l5 5M4 20l4-1L20 7a3 3 0 0 0-4-4L4 15z"/></svg> Revisi
                        </a>
                    </li>
                ';
            }

            if ($item->sts_deal !== '1' && $item->sts_quot == '1') {
                $action .= '
                    <li>
                        <a href="javascript:void(0);" class="btn_deal_penawaran" data-toggle="modal" data-target="#modal_deal_penawaran" data-id_penawaran="' . $item->id_penawaran . '">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12l4 4L19 6"/></svg> Deal
                        </a>
                    </li>
                ';
            }
        }

        if (has_permission($this->deletePermission) && $item->sts_quot !== '1') {
            $action .= '
                <li>
                    <a href="javascript:void(0);" class="del_penawaran_non_kons penawaran-action-danger" data-id_penawaran="' . $item->id_penawaran . '">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg> Delete
                    </a>
                </li>
            ';
        }

        if (!empty($item->dokumen_pendukung)) {
            $action .= '
                <li>
                    <a href="' . base_url('uploads/penawaran_non_konsultasi/' . $item->dokumen_pendukung) . '" target="_blank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3v12M7 10l5 5 5-5M4 17v4h16v-4"/></svg> Dokumen Pendukung
                    </a>
                </li>
            ';
        }

        $action .= '</ul></div>';

        return $action;
    }

    public function view_non_kons($id_penawaran)
    {
        $data_penawaran = $this->Penawaran_model->get_penawaran($id_penawaran);
        $data_detail_penawaran = $this->Penawaran_model->get_penawaran_detail($id_penawaran);

        $list_divisi = $this->Penawaran_model->list_divisi();
        $list_customer = $this->Penawaran_model->list_customer();
        $list_company = $this->Penawaran_model->list_company();
        $list_sales = $this->Penawaran_model->list_employee();
        $list_employee = $this->Penawaran_model->list_employee();

        $data = [
            'data_penawaran' => $data_penawaran,
            'data_detail_penawaran' => $data_detail_penawaran,
            'list_divisi' => $list_divisi,
            'list_customer' => $list_customer,
            'list_company' => $list_company,
            'list_sales' => $list_sales,
            'list_employee' => $list_employee,
        ];

        $this->template->title('View Penawaran Non Konsultasi');
        $this->template->set($data);
        $this->template->render('view_penawaran_non');
    }

    public function del_penawaran_non_kons()
    {
        $id_penawaran = $this->input->post('id_penawaran');

        $this->db->trans_begin();

        try {
            $arr_update = [
                'deleted_by' => $this->auth->user_id(),
                'deleted_date' => date('Y-m-d H:i:s')
            ];

            $update_penawaran_non_kons = $this->db->update('kons_tr_penawaran_non_konsultasi', $arr_update, ['id_penawaran' => $id_penawaran]);

            $this->db->trans_commit();

            $this->output->set_status_header(200);
            $response = [
                'msg' => 'Data has been deleted !'
            ];

            echo json_encode($response);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            $response = [
                'msg' => $e->getMessage()
            ];

            echo json_encode($response);
        }
    }

    public function edit_non_kons($id_penawaran)
    {
        $data_penawaran = $this->Penawaran_model->get_penawaran($id_penawaran);
        $data_detail_penawaran = $this->Penawaran_model->get_penawaran_detail($id_penawaran);

        $list_divisi = $this->Penawaran_model->list_divisi();
        $list_customer = $this->Penawaran_model->list_customer();
        $list_company = $this->Penawaran_model->list_company();
        $list_sales = $this->Penawaran_model->list_employee();
        $list_employee = $this->Penawaran_model->list_employee();

        $data = [
            'data_penawaran' => $data_penawaran,
            'data_detail_penawaran' => $data_detail_penawaran,
            'list_divisi' => $list_divisi,
            'list_customer' => $list_customer,
            'list_company' => $list_company,
            'list_sales' => $list_sales,
            'list_employee' => $list_employee,
        ];

        $this->template->title('Edit Penawaran Non Konsultasi');
        $this->template->set($data);
        $this->template->render('edit_penawaran_non');
    }

    public function deal_penawaran_non_kons()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        try {
            $arr_deal = [
                'sts_deal' => '1'
            ];

            // --- PROSES UPLOAD MULAI DISINI ---
            if (!empty($_FILES['dokumen_pendukung']['name'])) {
                $config['upload_path'] = './uploads/penawaran_non_konsultasi';
                $config['allowed_types'] = '*';
                $config['remove_spaces'] = TRUE;
                $config['encrypt_name'] = TRUE;

                $this->load->library('upload'); // Load dulu
                $this->upload->initialize($config); // BARU di-initialize pakai config lu

                if (!$this->upload->do_upload('dokumen_pendukung')) {
                    // Jika upload gagal, lempar exception agar masuk ke catch
                    throw new Exception($this->upload->display_errors('', ''));
                } else {
                    // Ambil nama file yang baru diupload
                    $upload_data = $this->upload->data();
                    $arr_deal['dokumen_pendukung'] = $upload_data['file_name'];
                }
            }
            // --- PROSES UPLOAD SELESAI ---

            $this->db->update('kons_tr_penawaran_non_konsultasi', $arr_deal, ['id_penawaran' => $post['id_penawaran']]);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                throw new Exception("Gagal mengupdate database");
            } else {
                $this->db->trans_commit();
                $this->output->set_status_header(200);
                echo json_encode(['msg' => 'Data has been deal & file uploaded!']);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->output->set_status_header(500);
            echo json_encode(['msg' => $e->getMessage()]);
        }
    }

    public function get_data_penawaran_non()
    {
        try {
            $draw = $this->input->get('draw');
            $length = $this->input->get('length');
            $start = $this->input->get('start');
            $search = $this->input->get('search')['value'];

            $filter_status = $this->input->get('filter_status');

            $this->db->select('a.*, b.nm_lengkap');
            $this->db->from('kons_tr_penawaran_non_konsultasi a');
            $this->db->join('users b', 'b.id_user = a.input_by', 'left');
            $this->db->where('a.deleted_by', null);

            // Filter by status Quotation
            if (!empty($filter_status)) {
                if ($filter_status === 'waiting') {
                    $this->db->where('a.sts_quot', '0');
                } elseif ($filter_status === 'approved') {
                    $this->db->where('a.sts_quot', '1');
                    $this->db->where('a.sts_deal <>', '1');
                } elseif ($filter_status === 'deal') {
                    $this->db->where('a.sts_quot', '1');
                    $this->db->where('a.sts_deal', '1');
                } elseif ($filter_status === 'rejected') {
                    $this->db->where('a.sts_quot', '2');
                }
            }

            $db_clone = clone $this->db;
            $count_all = $db_clone->count_all_results();

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('a.id_penawaran', $search, 'both');
                $this->db->or_like('a.tgl_quotation', $search, 'both');
                $this->db->or_like('a.pic_penawaran', $search, 'both');
                $this->db->or_like('a.keterangan_penawaran', $search, 'both');
                $this->db->or_like('a.grand_total', $search, 'both');
                $this->db->or_like('b.nm_lengkap', $search, 'both');
                $this->db->group_end();
            }

            $db_clone = clone $this->db;
            $count_filtered = $db_clone->count_all_results();

            // Urutkan data: Waiting Approval (sts_quot = '0') diprioritaskan lebih dulu
            $this->db->order_by("CASE WHEN a.sts_quot = '0' THEN 0 ELSE 1 END", 'asc', false);
            $this->db->order_by('a.input_date', 'desc');
            $this->db->limit($length, $start);

            $get_data = $this->db->get()->result();

            $no = (0 + $start);
            $hasil = [];
            foreach ($get_data as $item) {
                $no++;

                $status = $this->render_status_penawaran_non_kons($item);

                $action = $this->render_action_non_kons($item);

                $quotation_penawaran = '<span style="font-weight: 700; color: #333;">' . $item->id_penawaran . '</span>';
                if (!empty($item->tgl_quotation)) {
                    $quotation_penawaran .= '<br><span class="text-muted" style="font-size: 11px;"><i class="fa fa-calendar"></i> ' . date('d F Y', strtotime($item->tgl_quotation)) . '</span>';
                }
                if (!empty($item->keterangan_penawaran)) {
                    $quotation_penawaran .= '<br><span class="text-muted" style="font-size: 12px;"><i class="fa fa-file-text-o"></i> ' . $item->keterangan_penawaran . '</span>';
                }

                $customer_info = '<span style="font-weight: 600; color: #333;">' . $item->nm_customer . '</span>';

                $sales_creator = '';
                if (!empty($item->nm_pic_penawaran)) {
                    $sales_creator .= '<div><i class="fa fa-user-circle text-primary"></i> <b>PIC:</b> ' . ucfirst($item->nm_pic_penawaran) . '</div>';
                }
                if (!empty($item->nm_lengkap)) {
                    $sales_creator .= '<div class="text-muted" style="font-size: 11px;"><i class="fa fa-user text-muted"></i> <b>Created:</b> ' . ucfirst($item->nm_lengkap) . ' (' . date('d/m/Y H:i', strtotime($item->input_date)) . ')</div>';
                }

                $grand_total = '<span style="font-weight: 700;">Rp ' . number_format($item->grand_total) . '</span>';

                $hasil[] = [
                    'no' => $no,
                    'quotation_penawaran' => $quotation_penawaran,
                    'customer_info' => $customer_info,
                    'sales_creator' => $sales_creator,
                    'grand_total' => $grand_total,
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
