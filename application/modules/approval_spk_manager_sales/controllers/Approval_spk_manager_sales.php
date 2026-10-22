<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$status = array();
class Approval_spk_manager_sales extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Manager_Sales.View';
    protected $addPermission      = 'Manager_Sales.Add';
    protected $managePermission = 'Manager_Sales.Manage';
    protected $deletePermission = 'Manager_Sales.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Approval SPK Manager Sales');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        // $this->load->model(array('Approval_spk_penawaran/Approval_spk_penawaran_model'));
        date_default_timezone_set('Asia/Bangkok');

        $this->is_admin = $this->auth->is_admin();
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval SPK Manager Sales');
        $this->template->render('index');
    }

    public function view_spk($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        // $get_spk_penawaran_subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        $get_spk_penawaran_payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $this->db->select('a.id, a.id_spk_penawaran, a.id_aktifitas, a.nm_aktifitas, a.mandays, a.mandays_rate, a.mandays_tandem, a.mandays_rate_tandem, a.harga_aktifitas, a.total_aktifitas');
        $this->db->from('kons_tr_spk_aktifitas a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $get_list_spk_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran_subcont a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $this->db->order_by('a.id', 'asc');
        $get_spk_penawaran_subcont = $this->db->get()->result();

        // $this->db->select('a.id, a.id_penawaran, a.id_spk_penawaran, a.');

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_spk_penawaran->id_penawaran])->row();

        $this->db->select('a.*, b.nm_pic, b.divisi as jabatan_pic, b.hp as no_hp_pic');
        $this->db->from('customer a');
        $this->db->join('customer_pic b', 'b.id_pic = a.id_pic', 'left');
        $this->db->where('a.nm_customer <>', '');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->order_by('a.name', 'asc');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where('a.company_id', 'COM003');
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.id, a.name as nm_karyawan');
            $this->db->from(DBHR . '.employees a');
            $this->db->where('a.id', $get_penawaran->detail_informasi_awal);
            $get_marketing_informasi_awal = $this->db->get()->row();

            if (!empty($get_marketing_informasi_awal)) {
                $detail_informasi_awal = $get_marketing_informasi_awal->nm_karyawan;
            }
        } else {
            $detail_informasi_awal = $get_penawaran->detail_informasi_awal;
        }

        $this->db->select('a.*, b.nm_aktifitas');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $this->db->order_by('a.id', 'asc');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_akomodasi = $this->db->get()->result();

        $nilai_akomodasi = 0;
        foreach ($get_akomodasi as $item_akomodasi) {
            $nilai_akomodasi += $item_akomodasi->total;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_others = $this->db->get()->result();

        $nilai_others = 0;
        foreach ($get_others as $item_others) {
            $nilai_others += $item_others->total_budget;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_lab = $this->db->get()->result();

        $nilai_lab = 0;
        foreach ($get_lab as $item_lab) {
            $nilai_lab += $item_lab->total_budget;
        }

        $this->db->select('SUM(a.total_budget) as ttl_subcont_tenaga_ahli');
        $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_subcont_tenaga_ahli = $this->db->get()->row_array();

        $nilai_subcont_tenaga_ahli = (!empty($get_subcont_tenaga_ahli)) ? $get_subcont_tenaga_ahli['ttl_subcont_tenaga_ahli'] : 0;

        $this->db->select('SUM(a.total_budget) as ttl_subcont_perusahaan');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_subcont_perusahaan = $this->db->get()->row_array();

        $nilai_subcont_perusahaan = (!empty($get_subcont_perusahaan)) ? $get_subcont_perusahaan['ttl_subcont_perusahaan'] : 0;

        $nilai_kontrak = 0;
        foreach ($get_aktifitas as $item_aktifitas) {
            $nilai_kontrak += $item_aktifitas->harga_aktifitas;
        }

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_spk_penawaran->id_project);
        $get_package = $this->db->get()->row();

        $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

        $data = [
            'list_spk_aktifitas' => $get_list_spk_aktifitas,
            'list_spk_penawaran' => $get_spk_penawaran,
            'list_spk_penawaran_subcont' => $get_spk_penawaran_subcont,
            'list_spk_penawaran_payment' => $get_spk_penawaran_payment,
            'list_penawaran' => $get_penawaran,
            'list_customer' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_all_marketing' => $get_all_marketing,
            'list_divisi' => $get_divisi,
            'list_all_aktifitas' => $get_all_aktifitas,
            'detail_informasi_awal' => $detail_informasi_awal,
            'nilai_project' => $get_penawaran->grand_total,
            'nilai_akomodasi' => $nilai_akomodasi,
            'nilai_others' => $nilai_others,
            'nilai_lab' => $nilai_lab,
            'nilai_subcont_tenaga_ahli' => $nilai_subcont_tenaga_ahli,
            'nilai_subcont_perusahaan' => $nilai_subcont_perusahaan,
            'nilai_kontrak' => $nilai_kontrak,
            'nm_paket' => $nm_paket
        ];

        $this->auth->restrict($this->viewPermission);
        $this->template->title('View SPK');
        $this->template->set($data);
        $this->template->render('view_spk');
    }

    public function approval_spk($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        // $get_spk_penawaran_subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        $get_spk_penawaran_payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $this->db->select('a.id, a.id_spk_penawaran, a.id_aktifitas, a.nm_aktifitas, a.mandays, a.mandays_rate, a.mandays_tandem, a.mandays_rate_tandem, a.harga_aktifitas, a.total_aktifitas');
        $this->db->from('kons_tr_spk_aktifitas a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $get_list_spk_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran_subcont a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $this->db->order_by('a.id', 'asc');
        $get_spk_penawaran_subcont = $this->db->get()->result();

        // $this->db->select('a.id, a.id_penawaran, a.id_spk_penawaran, a.');

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_spk_penawaran->id_penawaran])->row();

        $this->db->select('a.*, b.nm_pic, b.divisi as jabatan_pic, b.hp as no_hp_pic');
        $this->db->from('customer a');
        $this->db->join('customer_pic b', 'b.id_pic = a.id_pic', 'left');
        $this->db->where('a.nm_customer <>', '');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->order_by('a.name', 'asc');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where('a.company_id', 'COM003');
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.id, a.name as nm_karyawan');
            $this->db->from(DBHR . '.employees a');
            $this->db->where('a.id', $get_penawaran->detail_informasi_awal);
            $get_marketing_informasi_awal = $this->db->get()->row();

            if (!empty($get_marketing_informasi_awal)) {
                $detail_informasi_awal = $get_marketing_informasi_awal->nm_karyawan;
            }
        } else {
            $detail_informasi_awal = $get_penawaran->detail_informasi_awal;
        }

        $this->db->select('a.*, b.nm_aktifitas');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $this->db->order_by('a.id', 'asc');
        $get_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_akomodasi = $this->db->get()->result();

        $nilai_akomodasi = 0;
        foreach ($get_akomodasi as $item_akomodasi) {
            $nilai_akomodasi += $item_akomodasi->total;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_others = $this->db->get()->result();

        $nilai_others = 0;
        foreach ($get_others as $item_others) {
            $nilai_others += $item_others->total_budget;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_lab = $this->db->get()->result();

        $nilai_lab = 0;
        foreach ($get_lab as $item_lab) {
            $nilai_lab += $item_lab->total_budget;
        }

        $this->db->select('SUM(a.total_budget) as ttl_subcont_tenaga_ahli');
        $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_subcont_tenaga_ahli = $this->db->get()->row_array();

        $nilai_subcont_tenaga_ahli = (!empty($get_subcont_tenaga_ahli)) ? $get_subcont_tenaga_ahli['ttl_subcont_tenaga_ahli'] : 0;

        $this->db->select('SUM(a.total_budget) as ttl_subcont_perusahaan');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_subcont_perusahaan = $this->db->get()->row_array();

        $nilai_subcont_perusahaan = (!empty($get_subcont_perusahaan)) ? $get_subcont_perusahaan['ttl_subcont_perusahaan'] : 0;

        $nilai_kontrak = 0;
        foreach ($get_aktifitas as $item_aktifitas) {
            $nilai_kontrak += $item_aktifitas->harga_aktifitas;
        }

        $this->db->select('a.*');
        $this->db->from('users a');
        $this->db->where('a.id_user', $this->auth->user_id());
        $get_user = $this->db->get()->row();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_spk_penawaran->id_project);
        $get_package = $this->db->get()->row();

        $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

        $data = [
            'list_spk_aktifitas' => $get_list_spk_aktifitas,
            'list_spk_penawaran' => $get_spk_penawaran,
            'list_spk_penawaran_subcont' => $get_spk_penawaran_subcont,
            'list_spk_penawaran_payment' => $get_spk_penawaran_payment,
            'list_penawaran' => $get_penawaran,
            'list_customer' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_all_marketing' => $get_all_marketing,
            'list_divisi' => $get_divisi,
            'list_all_aktifitas' => $get_all_aktifitas,
            'detail_informasi_awal' => $detail_informasi_awal,
            'nilai_project' => $get_penawaran->grand_total,
            'nilai_akomodasi' => $nilai_akomodasi,
            'nilai_others' => $nilai_others,
            'nilai_lab' => $nilai_lab,
            'nilai_subcont_tenaga_ahli' => $nilai_subcont_tenaga_ahli,
            'nilai_subcont_perusahaan' => $nilai_subcont_perusahaan,
            'nilai_kontrak' => $nilai_kontrak,
            'data_user' => $get_user,
            'nm_paket' => $nm_paket
        ];

        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval SPK');
        $this->template->set($data);
        $this->template->render('approval_spk');
    }

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*');
        $this->db->from('users a');
        $this->db->where('a.id_user', $this->auth->user_id());
        $get_user = $this->db->get()->row();

        $this->db->select('a.*, b.grand_total');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->where('a.deleted_by', null);
        $this->db->where('a.sts_spk', null);
        $this->db->where('a.approval_manager_sales', null);
        $this->db->where('a.approval_project_leader_sts <>', null);
        $this->db->where('a.approval_project_leader_sts <>', '');

        if (!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }

        $this->db->order_by('a.input_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, b.grand_total');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->where('a.deleted_by', null);
        $this->db->where('a.sts_spk', null);
        $this->db->where('a.approval_manager_sales', null);
        $this->db->where('a.approval_project_leader_sts <>', null);
        $this->db->where('a.approval_project_leader_sts <>', '');

        if (!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');

        $get_data_all = $this->db->get();
        // print_r($this->is_admin);
        // echo '<br><br>';
        // print_r($this->db->last_query());
        // exit;

        $hasil = [];

        $no = 1;
        foreach ($get_data->result() as $item) {

            $status = '<span class="btn btn-sm btn-success" style="width: 100% !important;">New</span>';
            $status_spk = '<span class="btn btn-sm btn-primary" style="width: 100% !important;">Waiting Approval</span>';

            $approval_position = '';
            $approval_position_arr = [];
            if ($item->approval_sales_sts == null) {
                $approval_position_arr[] = 'Sales';
            }
            if ($item->approval_sales_sts !== null && $item->approval_konsultan_1_sts == null && $item->id_konsultan_1 !== '') {
                $approval_position_arr[] = 'Konsultan 1';
            }
            if ($item->approval_sales_sts !== null && $item->approval_konsultan_2_sts == null && $item->id_konsultan_2 !== '') {
                $approval_position_arr[] = 'Konsultan 2';
            }

            if (empty($approval_position_arr) && $item->approval_level2_sts == null) {
                $approval_position = 'Direktur';
            }
            if (empty($approval_position_arr) && $item->approval_manager_sales == null) {
                $approval_position = 'Manager Sales';
            }
            if (empty($approval_position_arr) && $item->approval_project_leader_sts == null) {
                $approval_position = 'Project Leader';
            }

            if (!empty($approval_position_arr)) {
                $status_spk = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval : ' . implode(' & ', $approval_position_arr) . '</button>';
            }
            if (!empty($approval_position)) {
                $status_spk = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval : ' . $approval_position . '</button>';
            }

            $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $item->id_penawaran])->row();
            if ($get_penawaran->sts_cust == 0) {
                $status = '
                    <span class="btn btn-sm btn-warning" style="width: 100% !important;">
                        <b>New</b>
                    </span>
                ';
            } else {
                $status = '
                    <span class="btn btn-sm btn-info" style="width: 100% !important;">
                        <b>Repeat</b>
                    </span>
                ';
            }

            if ($item->sts_spk == '1') {
                $status_spk = '<span class="btn btn-sm btn-success" style="width: 100% !important;">Approved</span>';
            }
            if ($item->sts_spk == '0') {
                $status_spk = '<span class="btn btn-sm btn-danger" style="width: 100% !important;">Rejected</span>';
            }

            if ($item->reject_sales_sts !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Sales</button>';
            }
            if ($item->reject_konsultan_1_sts !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Konsultan 1</button>';
            }
            if ($item->reject_konsultan_2_sts !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Konsultan 2</button>';
            }
            if ($item->reject_project_leader_sts !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Project Leader</button>';
            }
            if ($item->reject_manager_sales_sts !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Manager Sales</button>';
            }
            if ($item->reject_level2_by !== null) {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger" style="font-weight: bold;"> Rejected by : Direktur</button>';
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
                        <a href="' . base_url('approval_spk_manager_sales/view_spk/' . urlencode(str_replace('/', '|', $item->id_spk_penawaran))) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-file text-info"></i> View
                        </a>
                    </li>
                ';
            }

            if ($this->managePermission) {

                $valid = 1;

                if ($get_user->employee_id == $item->id_sales && $item->approval_sales_sts == 1) {
                    $valid = 0;
                }
                if ($get_user->employee_id == $item->id_konsultan_1 && $item->approval_konsultan_1_sts == 1) {
                    $valid = 0;
                }
                if ($get_user->employee_id == $item->id_konsultan_2 && $item->approval_konsultan_2_sts == 1) {
                    $valid = 0;
                }

                if ($get_user->employee_id == 'EMP0010') {
                    $valid = 1;
                }


                if ($valid == 1) {
                    $option .= '
                    <li>
                        <a href="' . base_url('approval_spk_manager_sales/approval_spk/' . urlencode(str_replace('/', '|', $item->id_spk_penawaran))) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-check text-success"></i> Approval
                        </a>
                    </li>
                ';
                }
            }
            $option .= '</ul>';

            $nm_marketing = $item->nm_sales;

            $this->db->select('a.nm_paket');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_project);
            $get_nm_paket = $this->db->get()->row_array();

            $nm_paket = (!empty($get_nm_paket['nm_paket'])) ? $get_nm_paket['nm_paket'] : '';

            $nm_customer = $item->nm_customer;

            $valid_show = 1;
            if ($get_user->employee_id == $item->id_sales && $item->approval_sales_sts !== null) {
                $valid_show = 0;
            }
            if ($get_user->employee_id == $item->id_konsultan_1 && $item->approval_konsultan_1_sts !== null) {
                $valid_show = 0;
            }
            if ($get_user->employee_id == $item->id_konsultan_2 && $item->approval_konsultan_2_sts !== null) {
                $valid_show = 0;
            }

            if ($get_user->employee_id == 'EMP0010') {
                $valid_show = 1;
            }

            if ($valid_show == 1) {
                $hasil[] = [
                    'no' => $no,
                    'id_spk_penawaran' => $item->id_spk_penawaran,
                    'nm_marketing' => ucfirst($nm_marketing),
                    'nm_paket' => $nm_paket,
                    'nm_customer' => $nm_customer,
                    'grand_total' => number_format($item->grand_total),
                    'status' => $status,
                    'status_spk' => $status_spk,
                    'option' => $option
                ];

                $no++;
            }
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function reject_spk()
    {
        $post = $this->input->post();

        $id_spk_penawaran = $post['id_spk_penawaran'];
        $reject_reason = $post['reject_reason'];

        $get_user = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();

        $get_spk = $this->db->get_where('kons_tr_spk_penawaran', array('id_spk_penawaran' => $id_spk_penawaran))->row();

        $this->db->trans_begin();

        $data_arr = [];

        $data_arr = [
            'approval_manager_sales' => null,
            'approval_manager_sales_date' => null,
            'approval_sales_sts' => null,
            'approval_sales_date' => null,
            'approval_konsultan_1_sts' => null,
            'approval_konsultan_1_date' => null,
            'approval_konsultan_2_sts' => null,
            'approval_konsultan_2_date' => null,
            'approval_project_leader_sts' => null,
            'approval_project_leader_date' => null,
            'reject_manager_sales_sts' => 1,
            'reject_manager_sales_date' => date('Y-m-d H:i:s'),
            'reject_manager_sales_reason' => $reject_reason
        ];

        $update_reject_spk = $this->db->update('kons_tr_spk_penawaran', $data_arr, ['id_spk_penawaran' => $id_spk_penawaran]);
        if (!$update_reject_spk) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        if ($this->db->trans_status() === false || empty($data_arr)) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been rejected !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function approve_spk()
    {
        $post = $this->input->post();

        $id_spk_penawaran = $post['id_spk_penawaran'];
        $isu_khusus = $post['isu_khusus'];

        $get_user = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();

        $get_spk = $this->db->get_where('kons_tr_spk_penawaran', array('id_spk_penawaran' => $id_spk_penawaran))->row();

        $this->db->trans_begin();

        $data_arr = [
            'isu_khusus' => $post['isu_khusus'],
            'approval_manager_sales' => 1,
            'approval_manager_sales_date' => date('Y-m-d H:i:s'),
            'reject_manager_sales_sts' => null,
            'reject_manager_sales_date' => null,
            'reject_manager_sales_reason' => null
        ];

        if (!empty($data_arr)) {
            $update_approve_spk = $this->db->update('kons_tr_spk_penawaran', $data_arr, ['id_spk_penawaran' => $id_spk_penawaran]);
        }

        if ($this->db->trans_status() === false || empty($data_arr)) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been Approved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function detail_sum()
    {
        if (isset($_POST['id_spk_penawaran'])) {
            $id_spk_penawaran = $this->input->post('id_spk_penawaran');
            $type = $this->input->post('type');

            $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', array('id_spk_penawaran' => $id_spk_penawaran))->row();

            if ($type == 'akomodasi') {
                $this->db->select('a.id, a.qty, a.price_unit, a.total, a.keterangan, b.nm_biaya');
                $this->db->from('kons_tr_penawaran_akomodasi a');
                $this->db->join('kons_master_biaya b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
                $get_akomodasi = $this->db->get()->result();

                $data = [
                    'list_akomodasi' => $get_akomodasi
                ];

                $this->template->set($data);
                $this->template->render('detail_akomodasi');
            }
            if ($type == 'others') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya');
                $this->db->from('kons_tr_penawaran_others a');
                $this->db->join('kons_master_biaya b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
                $get_others = $this->db->get()->result();

                $data = [
                    'list_others' => $get_others
                ];

                $this->template->set($data);
                $this->template->render('detail_others');
            }
            if ($type == 'lab') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.isu_lingkungan as nm_biaya');
                $this->db->from('kons_tr_penawaran_lab a');
                $this->db->join('kons_master_lab b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
                $get_lab = $this->db->get()->result();

                $data = [
                    'list_lab' => $get_lab
                ];

                $this->template->set($data);
                $this->template->render('detail_lab');
            }
            if ($type == 'subcont_tenaga_ahli') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya as nm_biaya');
                $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
                $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
                $get_subcont_tenaga_ahli = $this->db->get()->result();

                $data = [
                    'list_subcont_tenaga_ahli' => $get_subcont_tenaga_ahli
                ];

                $this->template->set($data);
                $this->template->render('detail_subcont_tenaga_ahli');
            }
            if ($type == 'subcont_perusahaan') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya as nm_biaya');
                $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
                $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
                $get_subcont_perusahaan = $this->db->get()->result();

                $data = [
                    'list_subcont_perusahaan' => $get_subcont_perusahaan
                ];

                $this->template->set($data);
                $this->template->render('detail_subcont_perusahaan');
            }
        } else {
            $id_penawaran = $this->input->post('id_penawaran');
            $type = $this->input->post('type');

            if ($type == 'akomodasi') {
                $this->db->select('a.id, a.qty, a.price_unit, a.total, a.keterangan, b.nm_biaya');
                $this->db->from('kons_tr_penawaran_akomodasi a');
                $this->db->join('kons_master_biaya b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $id_penawaran);
                $get_akomodasi = $this->db->get()->result();

                $data = [
                    'list_akomodasi' => $get_akomodasi
                ];

                $this->template->set($data);
                $this->template->render('detail_akomodasi');
            }
            if ($type == 'others') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya');
                $this->db->from('kons_tr_penawaran_others a');
                $this->db->join('kons_master_biaya b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $id_penawaran);
                $get_others = $this->db->get()->result();

                $data = [
                    'list_others' => $get_others
                ];

                $this->template->set($data);
                $this->template->render('detail_others');
            }
            if ($type == 'lab') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.isu_lingkungan as nm_biaya');
                $this->db->from('kons_tr_penawaran_lab a');
                $this->db->join('kons_master_lab b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $id_penawaran);
                $get_lab = $this->db->get()->result();

                $data = [
                    'list_lab' => $get_lab
                ];

                $this->template->set($data);
                $this->template->render('detail_lab');
            }
            if ($type == 'subcont_tenaga_ahli') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya as nm_biaya');
                $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
                $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $id_penawaran);
                $get_subcont_tenaga_ahli = $this->db->get()->result();

                $data = [
                    'list_subcont_tenaga_ahli' => $get_subcont_tenaga_ahli
                ];

                $this->template->set($data);
                $this->template->render('detail_subcont_tenaga_ahli');
            }
            if ($type == 'subcont_perusahaan') {
                $this->db->select('a.id, a.qty, a.price_unit_budget, a.total_budget, a.keterangan, b.nm_biaya as nm_biaya');
                $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
                $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item');
                $this->db->where('a.id_penawaran', $id_penawaran);
                $get_subcont_perusahaan = $this->db->get()->result();

                $data = [
                    'list_subcont_perusahaan' => $get_subcont_perusahaan
                ];

                $this->template->set($data);
                $this->template->render('detail_subcont_perusahaan');
            }
        }
    }
}
