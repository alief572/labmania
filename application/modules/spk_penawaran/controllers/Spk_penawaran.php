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
class SPK_penawaran extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'SPK_penawaran.View';
    protected $addPermission      = 'SPK_penawaran.Add';
    protected $managePermission = 'SPK_penawaran.Manage';
    protected $deletePermission = 'SPK_penawaran.Delete';

    protected $dbhr;

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Spk_penawaran/Spk_penawaran_model'));
        date_default_timezone_set('Asia/Bangkok');

        $this->dbhr = $this->load->database('dbhr', true);
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('SPK');
        $this->template->render('index');
    }

    public function create_spk()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Create SPK');
        $this->template->render('choose_spk');
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
        $this->db->where('a.flag_active', 'Y');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $this->db->order_by('a.name', 'ASC');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.name as nm_karyawan');
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
        $get_nilai_subcont_tenaga_ahli = $this->db->get()->row();

        $nilai_subcont_tenaga_ahli = (!empty($get_nilai_subcont_tenaga_ahli)) ? $get_nilai_subcont_tenaga_ahli->ttl_subcont_tenaga_ahli : 0;

        $this->db->select('SUM(a.total_budget) as ttl_subcont_perusahaan');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_nilai_subcont_perusahaan = $this->db->get()->row();

        $nilai_subcont_perusahaan = (!empty($get_nilai_subcont_perusahaan)) ? $get_nilai_subcont_perusahaan->ttl_subcont_perusahaan : 0;

        $nilai_kontrak = 0;
        foreach ($get_aktifitas as $item_aktifitas) {
            $nilai_kontrak += $item_aktifitas->harga_aktifitas;
        }

        $approval_data = [];

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
            'nilai_project' => $get_spk_penawaran->nilai_kontrak,
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

    public function edit_spk($id_spk_penawaran)
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
        $this->db->where('a.flag_active', 'Y');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $this->db->order_by('a.name', 'ASC');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.name as nm_karyawan');
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
        $get_nilai_subcont_tenaga_ahli = $this->db->get()->row();

        $nilai_subcont_tenaga_ahli = (!empty($get_nilai_subcont_tenaga_ahli)) ? $get_nilai_subcont_tenaga_ahli->ttl_subcont_tenaga_ahli : 0;

        $this->db->select('SUM(a.total_budget) as ttl_subcont_perusahaan');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->where('a.id_penawaran', $get_penawaran->id_quotation);
        $get_nilai_subcont_perusahaan = $this->db->get()->row();

        $nilai_subcont_perusahaan = (!empty($get_nilai_subcont_perusahaan)) ? $get_nilai_subcont_perusahaan->ttl_subcont_perusahaan : 0;

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
        $this->template->title('Edit SPK');
        $this->template->set($data);
        $this->template->render('edit_spk');
    }

    public function print_spk($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        // $get_spk_penawaran_subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        $get_spk_penawaran_payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran_subcont a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $this->db->order_by('a.id', 'asc');
        $get_spk_penawaran_subcont = $this->db->get()->result();

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
        $this->db->where('a.flag_active', 'Y');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $this->db->order_by('a.name', 'ASC');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.name as nm_karyawan');
            $this->db->from(DBHR . '.employees a');
            $this->db->where('a.id', $get_penawaran->detail_informasi_awal);
            $get_marketing_informasi_awal = $this->db->get()->row();

            if (!empty($get_marketing_informasi_awal)) {
                $detail_informasi_awal = $get_marketing_informasi_awal->nm_karyawan;
            }
        } else {
            $detail_informasi_awal = $get_penawaran->detail_informasi_awal;
        }

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
        $get_akomodasi = $this->db->get()->result();

        $this->db->select('a.*, b.nm_biaya');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
        $get_others = $this->db->get()->result();

        $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
        $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
        $get_lab = $this->db->get()->result();

        $ttl_mandays_subcont = 0;
        $ttl_tandem = 0;
        foreach ($get_spk_penawaran_subcont as $item) {
            $ttl_mandays_subcont += $item->mandays_subcont;
            $ttl_tandem += ($item->mandays_tandem * $item->mandays_rate_tandem);
        }

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_spk_penawaran->id_project);
        $get_package = $this->db->get()->row();

        $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

        $data = [
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
            'list_akomodasi' => $get_akomodasi,
            'list_others' => $get_others,
            'list_lab' => $get_lab,
            'ttl_mandays_subcont' => $ttl_mandays_subcont,
            'ttl_tandem' => $ttl_tandem,
            'nm_paket' => $nm_paket
        ];

        // ob_clean();
        // ob_start();
        $this->auth->restrict($this->managePermission);
        $this->load->view('print_spk', $data);
        // $html = ob_get_contents();

        // require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
        // $html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(0, 0, 0, 0));
        // $html2pdf->pdf->SetDisplayMode('fullpage');
        // $html2pdf->WriteHTML($html);
        // ob_end_clean();
        // $html2pdf->Output('Penawaran.pdf', 'I');
    }

    public function get_data_spk()
    {

        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $filter_status = $this->input->post('filter_status');

        $this->db->select('a.*, b.grand_total, c.nm_lengkap');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->join('users c', 'c.id_user = a.input_by', 'left');
        $this->db->where('a.deleted_by', null);
        $this->db->where('a.id_penawaran IS NOT NULL');
        $this->db->where('a.id_penawaran <>', '');

        // Filter by status SPK
        if (!empty($filter_status)) {
            if ($filter_status === 'approved') {
                $this->db->where('a.sts_spk', '1');
            } elseif ($filter_status === 'rejected') {
                $this->db->group_start();
                $this->db->where('a.sts_spk', '0');
                $this->db->or_where('a.reject_sales_sts IS NOT NULL');
                $this->db->or_where('a.reject_konsultan_1_sts IS NOT NULL');
                $this->db->or_where('a.reject_konsultan_2_sts IS NOT NULL');
                $this->db->or_where('a.reject_project_leader_sts IS NOT NULL');
                $this->db->or_where('a.reject_manager_sales_sts IS NOT NULL');
                $this->db->or_where('a.reject_level2_by IS NOT NULL');
                $this->db->group_end();
            } elseif ($filter_status === 'waiting') {
                // Waiting = belum approved (sts_spk bukan '1', termasuk NULL)
                // DAN tidak ada satupun reject
                $this->db->where('(a.sts_spk IS NULL OR a.sts_spk != \'1\')');
                $this->db->where('a.reject_sales_sts IS NULL');
                $this->db->where('a.reject_konsultan_1_sts IS NULL');
                $this->db->where('a.reject_konsultan_2_sts IS NULL');
                $this->db->where('a.reject_project_leader_sts IS NULL');
                $this->db->where('a.reject_manager_sales_sts IS NULL');
                $this->db->where('a.reject_level2_by IS NULL');
            }
        }

        $count_all = $this->db->count_all_results('', false);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->or_like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.grand_total', $search['value'], 'both');
            $this->db->or_like('c.nm_lengkap', $search['value'], 'both');
            $this->db->group_end();
        }

        $count_filter = $this->db->count_all_results('', false);

        $this->db->order_by('a.input_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $hasil = [];

        $no = 1 + intval($start);
        foreach ($get_data->result() as $item) {

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

            $status = str_replace('width: 100% !important;', '', $this->Spk_penawaran_model->render_status($item));
            $status_spk = $this->Spk_penawaran_model->render_status_spk($item);

            $option = $this->render_index_actions($item, $no);

            $nm_marketing = $item->nm_sales;

            $this->db->select('a.*');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_project);
            $get_package = $this->db->get()->row();

            $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

            $nm_customer = $item->nm_customer;

            $grand_total = (!empty($item->grand_total) && $item->grand_total > 0) ? number_format($item->grand_total) : number_format($item->nilai_kontrak);

            // Compact grouped cells (mirror kasbon_project 2-line style)
            $spk_paket = '<strong>' . htmlspecialchars($item->id_spk_penawaran) . '</strong>';
            if (!empty($nm_paket)) {
                $spk_paket .= '<br><small class="text-muted">' . htmlspecialchars($nm_paket) . '</small>';
            }

            $customer_cell = '<strong>' . htmlspecialchars($nm_customer) . '</strong>';
            $customer_cell .= '<br><small class="text-muted">Rp ' . $grand_total . '</small>';

            $marketing_cell = '<strong>' . htmlspecialchars(ucfirst($nm_marketing), ENT_QUOTES, 'UTF-8') . '</strong>';
            $marketing_cell .= '<br><small class="text-muted">' . htmlspecialchars($item->nm_lengkap) . '</small>';
            $marketing_cell .= '<br><small class="text-muted">' . date('d M Y H:i', strtotime($item->input_date)) . '</small>';

            $status_cell = '<div class="spk-status-stack">' . $status . $status_spk . '</div>';

            $hasil[] = [
                'no' => $no,
                'spk_paket' => $spk_paket,
                'nm_customer' => $customer_cell,
                'nm_marketing' => $marketing_cell,
                'status' => $status_cell,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_filter,
            'data' => $hasil
        ]);
    }

    private function render_index_actions($item, $no)
    {
        $route_id = urlencode(str_replace('/', '|', $item->id_spk_penawaran));
        $label = htmlspecialchars($item->id_spk_penawaran, ENT_QUOTES, 'UTF-8');
        $icons = [
            'more' => '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
            'view' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'edit' => '<path d="M14 5l5 5M4 20l4-1L20 7a3 3 0 0 0-4-4L4 15z"/>',
            'delete' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
            'print' => '<path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 12h1"/>'
        ];
        $icon = function ($name) use ($icons) {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
        };
        $html = '<div class="btn-group spk-actions"><button type="button" class="spk-action-toggle dropdown-toggle" data-toggle="dropdown" id="spkActions' . $no . '" aria-haspopup="true" aria-expanded="false" aria-label="Aksi SPK ' . $label . '">' . $icon('more') . '<span>Aksi</span></button><ul class="dropdown-menu dropdown-menu-right spk-action-menu" aria-labelledby="spkActions' . $no . '">';
        if (has_permission($this->viewPermission)) {
            $html .= '<li><a href="' . base_url('spk_penawaran/view_spk/' . $route_id) . '">' . $icon('view') . '<span>Lihat SPK</span></a></li>';
        }
        if (has_permission($this->managePermission)) {
            $html .= '<li><a href="' . base_url('spk_penawaran/edit_spk/' . $route_id) . '">' . $icon('edit') . '<span>Revisi SPK</span></a></li>';
        }
        $html .= '<li><a href="' . base_url('spk_penawaran/print_spk/' . $route_id) . '" target="_blank" rel="noopener">' . $icon('print') . '<span>Cetak SPK</span></a></li>';
        if ($this->auth->user_id() == '7') {
            $html .= '<li class="spk-action-divider" role="separator"></li><li><a href="#" class="del_spk spk-action-danger" data-id_spk_penawaran="' . $label . '">' . $icon('delete') . '<span>Hapus SPK</span></a></li>';
        }
        return $html . '</ul></div>';
    }

    public function get_data_penawaran()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*, c.nama');
        $this->db->from('kons_tr_penawaran a');
        $this->db->join('customer b', 'b.id_customer = a.id_customer', 'left');
        $this->db->join('members c', 'c.id = a.id_marketing', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = a.id_paket', 'left');
        $this->db->join('kons_master_paket e', 'e.id_paket = d.id_paket', 'left');
        $this->db->where(1, 1);
        $this->db->where('a.deleted_by', null);
        $this->db->group_start();
        $this->db->where('a.id_spk_penawaran', null);
        $this->db->or_where('a.id_spk_penawaran', '');
        $this->db->group_end();
        $this->db->where('a.sts_deal', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.tgl_quotation', $search['value'], 'both');
            $this->db->or_like('c.nama', $search['value'], 'both');
            $this->db->or_like('e.nm_paket', $search['value'], 'both');
            $this->db->or_like('b.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.grand_total', str_replace(',', '', $search['value']), 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, c.nama');
        $this->db->from('kons_tr_penawaran a');
        $this->db->join('customer b', 'b.id_customer = a.id_customer', 'left');
        $this->db->join('members c', 'c.id = a.id_marketing', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = a.id_paket', 'left');
        $this->db->join('kons_master_paket e', 'e.id_paket = d.id_paket', 'left');
        $this->db->where(1, 1);
        $this->db->where('a.deleted_by', null);
        $this->db->group_start();
        $this->db->where('a.id_spk_penawaran', null);
        $this->db->or_where('a.id_spk_penawaran', '');
        $this->db->group_end();
        $this->db->where('a.sts_deal', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.tgl_quotation', $search['value'], 'both');
            $this->db->or_like('c.nama', $search['value'], 'both');
            $this->db->or_like('e.nm_paket', $search['value'], 'both');
            $this->db->or_like('b.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.grand_total', str_replace(',', '', $search['value']), 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];

        $no = 1;
        foreach ($get_data->result() as $item) {

            if ($item->sts_cust == 0) {
                $status_cust = '
                    <span class="btn btn-sm btn-success" style="width: 100% !important;">
                        <b>NEW</b>
                    </span>
                ';
            } else {
                $status_cust = '
                    <span class="btn btn-sm btn-primary" style="width: 100% !important;">
                        <b>REPEAT</b>
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
                <a href="' . base_url('spk_penawaran/add_spk/' . urlencode(str_replace('/', '|', $item->id_quotation))) . '" class="btn btn-sm btn-primary"><i class="fa fa-arrow-right"></i></a>
            ';


            $this->db->select('a.id, a.name as nm_karyawan');
            $this->db->from(DBHR . '.employees a');
            $this->db->where('a.id', $item->id_marketing);
            $get_marketing = $this->db->get()->row();
            $nm_marketing = (!empty($get_marketing)) ? $get_marketing->nm_karyawan : '';

            $this->db->select('a.*');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_paket);
            $get_package = $this->db->get()->row();

            $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

            $get_customers = $this->db->get_where('customer', ['id_customer' => $item->id_customer])->row();
            $nm_customer = (!empty($get_customers)) ? $get_customers->nm_customer : '';

            $hasil[] = [
                'no' => $no,
                'id_quotation' => $item->id_quotation,
                'tgl_quotation' => $item->tgl_quotation,
                'nm_marketing' => ucfirst($item->nama),
                'nm_paket' => $nm_paket,
                'nm_customer' => $nm_customer,
                'grand_total' => number_format($item->grand_total),
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

    public function add_spk($id_quotation)
    {

        $id_quotation = urldecode($id_quotation);
        $id_quotation = str_replace('|', '/', $id_quotation);

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $id_quotation])->row();

        $this->db->select('a.*, b.nm_pic, b.divisi as jabatan_pic, b.hp as no_hp_pic');
        $this->db->from('customer a');
        $this->db->join('customer_pic b', 'b.id_pic = a.id_pic', 'left');
        $this->db->where('a.nm_customer <>', '');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        // $this->db->select('a.*');
        // $this->db->from('employee a');
        // $this->db->where('a.deleted', 'N');
        // $this->db->where('a.id', $get_penawaran->id_marketing);
        // $get_marketing = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.flag_active', 'Y');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $this->db->order_by('a.name', 'ASC');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('a.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.name as nama');
        $this->db->from(DBHR . '.divisions a');
        $this->db->where_not_in('a.company_id', ['COM004', 'COM005']);
        $get_divisi = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.name as nm_karyawan');
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
        $this->db->where('a.id_penawaran', $id_quotation);
        $this->db->order_by('a.id', 'asc');
        $get_aktifitas = $this->db->get()->result();

        $nilai_kontrak = 0;
        foreach ($get_aktifitas as $item_aktifitas) {
            $nilai_kontrak += $item_aktifitas->harga_aktifitas;
        }

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_akomodasi a');
        $this->db->where('a.id_penawaran', $id_quotation);
        $get_akomodasi = $this->db->get()->result();

        $nilai_akomodasi = 0;
        foreach ($get_akomodasi as $item_akomodasi) {
            $nilai_akomodasi += $item_akomodasi->total;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_others a');
        $this->db->where('a.id_penawaran', $id_quotation);
        $get_others = $this->db->get()->result();

        $nilai_others = 0;
        foreach ($get_others as $item_others) {
            $nilai_others += $item_others->total_budget;
        }

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_lab a');
        $this->db->where('a.id_penawaran', $id_quotation);
        $get_lab = $this->db->get()->result();

        $nilai_lab = 0;
        foreach ($get_lab as $item_lab) {
            $nilai_lab += $item_lab->total_budget;
        }

        $this->db->select('SUM(a.total_budget) as ttl_subcont_tenaga_ahli');
        $this->db->from('kons_tr_penawaran_subcont_tenaga_ahli a');
        $this->db->where('a.id_penawaran', $id_quotation);
        $get_biaya_subcont_tenaga_ahli = $this->db->get()->row_array();

        $nilai_subcont_tenaga_ahli = (!empty($get_biaya_subcont_tenaga_ahli)) ? $get_biaya_subcont_tenaga_ahli['ttl_subcont_tenaga_ahli'] : 0;

        $nilai_project = $get_penawaran->grand_total;

        $this->db->select('SUM(a.total_budget) as ttl_subcont_perusahaan');
        $this->db->from('kons_tr_penawaran_subcont_perusahaan a');
        $this->db->where('a.id_penawaran', $id_quotation);
        $get_biaya_subcont_perusahaan = $this->db->get()->row_array();

        $nilai_subcont_perusahaan = (!empty($get_biaya_subcont_perusahaan)) ? $get_biaya_subcont_perusahaan['ttl_subcont_perusahaan'] : 0;

        $nilai_project = $get_penawaran->grand_total;

        $data = [
            'list_penawaran' => $get_penawaran,
            'list_customer' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_all_marketing' => $get_all_marketing,
            'list_divisi' => $get_divisi,
            'list_aktifitas' => $get_aktifitas,
            'list_all_aktifitas' => $get_all_aktifitas,
            'detail_informasi_awal' => $detail_informasi_awal,
            'nm_paket' => $get_konsultasi->nm_paket,
            'nilai_kontrak' => $nilai_kontrak,
            'nilai_project' => $nilai_project,
            'nilai_akomodasi' => $nilai_akomodasi,
            'nilai_others' => $nilai_others,
            'nilai_lab' => $nilai_lab,
            'nilai_subcont_tenaga_ahli' => $nilai_subcont_tenaga_ahli,
            'nilai_subcont_perusahaan' => $nilai_subcont_perusahaan
        ];

        $this->template->set($data);
        $this->template->title('Add SPK');
        $this->template->render('add_spk');
    }

    public function add_spk_non_kons($id_penawaran)
    {

        $data_penawaran = $this->Spk_penawaran_model->get_penawaran_non_kons($id_penawaran);
        $data_detail_penawaran = $this->Spk_penawaran_model->get_penawaran_detail_non_kons($id_penawaran);

        $list_employee = $this->Spk_penawaran_model->get_list_employee();

        $data = [
            'data_penawaran' => $data_penawaran,
            'data_detail_penawaran' => $data_detail_penawaran,
            'list_employee' => $list_employee
        ];

        $this->template->title("Add SPK Penawaran");
        $this->template->set($data);
        $this->template->render('add_spk_non_kons');
    }

    /**
     * Helper: clean numeric value from formatted string (remove comma separator)
     */
    private function _clean_number($value)
    {
        return ($value !== '') ? str_replace(',', '', $value) : 0;
    }

    /**
     * Helper: get employee name, returns array [id, name]
     */
    private function _get_employee_info($id)
    {
        $emp = $this->Spk_penawaran_model->get_employee($id);
        return [
            'id' => !empty($emp) ? $emp->id : '',
            'nm' => !empty($emp) ? $emp->nm_karyawan : ''
        ];
    }

    public function save_spk_penawaran()
    {
        $post = $this->input->post();

        // Early validation: check duplicate ID before heavy queries
        if ($post['id_spk_penawaran'] !== '') {
            if ($this->Spk_penawaran_model->is_spk_exists($post['id_spk_penawaran'])) {
                echo json_encode(['status' => 0, 'msg' => 'Maaf, ID SPK sudah terdaftar sebelum nya !']);
                return;
            }
            $id_spk_penawaran = $post['id_spk_penawaran'];
        } else {
            $id_spk_penawaran = $this->Spk_penawaran_model->generate_id_spk_penawaran();
        }

        // Fetch related data
        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $post['id_quotation']])->row();
        if (empty($get_penawaran)) {
            echo json_encode(['status' => 0, 'msg' => 'Data penawaran tidak ditemukan !']);
            return;
        }

        $get_penawaran_aktifitas = $this->Spk_penawaran_model->get_penawaran_aktifitas($post['id_quotation']);

        // Customer
        $get_customer = $this->db->get_where('customer', ['id_customer' => $get_penawaran->id_customer])->row();
        $id_customer = !empty($get_customer) ? $get_customer->id_customer : '';
        $nm_customer = !empty($get_customer) ? $get_customer->nm_customer : '';

        // Employees (reusable helper eliminates 4x duplicate queries)
        $marketing = $this->_get_employee_info($get_penawaran->id_marketing);
        $project_leader = $this->_get_employee_info($post['project_leader']);
        $konsultan_1 = $this->_get_employee_info($post['konsultan_1']);
        $konsultan_2 = $this->_get_employee_info($post['konsultan_2']);

        // Konsultasi & Divisi
        $get_konsultasi = $this->Spk_penawaran_model->get_konsultasi($get_penawaran->id_paket);
        $get_divisi = $this->Spk_penawaran_model->get_divisi($post['divisi']);
        $id_divisi = !empty($get_divisi) ? $get_divisi->id : '';
        $nm_divisi = !empty($get_divisi) ? $get_divisi->name : '';

        // Informasi awal eksternal
        $tipe_info_awal_eks = isset($post['informasi_awal_eksternal']) ? $post['informasi_awal_eksternal'] : null;
        $detail_info_awal_eks = '';
        $cp_info_awal_eks = '';
        if ($tipe_info_awal_eks !== null) {
            $detail_info_awal_eks = $post['informasi_awal_eksternal_detail_' . $tipe_info_awal_eks];
            $cp_info_awal_eks = $post['informasi_awal_eksternal_cp_' . $tipe_info_awal_eks];
        }

        $now = date('Y-m-d H:i:s');
        $user_id = $this->auth->user_id();

        // Build main insert array
        $arr_insert = [
            'id_spk_penawaran'      => $id_spk_penawaran,
            'id_penawaran'          => $post['id_quotation'],
            'id_customer'           => $id_customer,
            'nm_customer'           => $nm_customer,
            'address'               => $post['alamat'],
            'npwp_cust'             => $post['no_npwp'],
            'nm_pic'                => $post['pic'],
            'tipe_informasi_awal'   => $get_penawaran->tipe_informasi_awal,
            'detail_informasi_awal' => $get_penawaran->detail_informasi_awal,
            'waktu_from'            => $post['waktu_from'],
            'waktu_to'              => $post['waktu_to'],
            'id_sales'              => $marketing['id'],
            'nm_sales'              => $marketing['nm'],
            'upload_proposal'       => $get_penawaran->upload_proposal,
            'id_project'            => $get_konsultasi->id_konsultasi_h,
            'nm_project'            => $get_konsultasi->nm_paket,
            'id_divisi'             => $id_divisi,
            'nm_divisi'             => $nm_divisi,
            'id_project_leader'     => $project_leader['id'],
            'nm_project_leader'     => $project_leader['nm'],
            'id_konsultan_1'        => $post['konsultan_1'],
            'nm_konsultan_1'        => $konsultan_1['nm'],
            'id_konsultan_2'        => $post['konsultan_2'],
            'nm_konsultan_2'        => $konsultan_2['nm'],
            'nilai_kontrak'         => $this->_clean_number($post['nilai_kontrak']),
            'biaya_subcont'         => 0,
            'biaya_akomodasi'       => $this->_clean_number($post['biaya_akomodasi']),
            'biaya_others'          => $this->_clean_number($post['biaya_others']),
            'biaya_tandem'          => $this->_clean_number($post['biaya_tandem']),
            'biaya_lab'             => $this->_clean_number($post['biaya_lab']),
            'biaya_subcont_tenaga_ahli' => $this->_clean_number($post['biaya_subcont_tenaga_ahli']),
            'biaya_subcont_perusahaan'  => $this->_clean_number($post['biaya_subcont_perusahaan']),
            'nilai_kontrak_bersih'  => $this->_clean_number($post['nilai_kontrak_bersih']),
            'mandays_rate'          => $this->_clean_number($post['mandays_rate']),
            'total_mandays'         => $this->_clean_number($post['total_mandays']),
            'mandays_subcont'       => 0,
            'mandays_internal'      => $this->_clean_number($post['total_mandays']),
            'nm_pemberi_informasi_1_komisi'      => $post['nm_pemberi_informasi_1_komisi'],
            'persen_pemberi_informasi_1_komisi'   => $this->_clean_number($post['persentase_pemberi_informasi_1_komisi']),
            'nominal_pemberi_informasi_1_komisi'  => $this->_clean_number($post['nominal_pemberi_informasi_1_komisi']),
            'nm_pemberi_informasi_2_komisi'      => $post['nm_pemberi_informasi_2_komisi'],
            'persen_pemberi_informasi_2_komisi'   => $this->_clean_number($post['persentase_pemberi_informasi_2_komisi']),
            'nominal_pemberi_informasi_2_komisi'  => $this->_clean_number($post['nominal_pemberi_informasi_2_komisi']),
            'nm_sales_1_komisi'     => $post['nm_sales_1_komisi'],
            'persen_sales_1_komisi' => $this->_clean_number($post['persentase_sales_1_komisi']),
            'nominal_sales_1_komisi' => $this->_clean_number($post['nominal_sales_1_komisi']),
            'nm_sales_2_komisi'     => $post['nm_sales_2_komisi'],
            'persen_sales_2_komisi' => $this->_clean_number($post['persentase_sales_2_komisi']),
            'nominal_sales_2_komisi' => $this->_clean_number($post['nominal_sales_2_komisi']),
            'isu_khusus'            => $post['isu_khusus'],
            'tipe_info_awal_eks'    => $tipe_info_awal_eks,
            'detail_info_awal_eks'  => $detail_info_awal_eks,
            'cp_info_awal_eks'      => $cp_info_awal_eks,
            'input_by'              => $user_id,
            'input_date'            => $now
        ];

        // Build aktifitas data
        $data_insert_aktifitas = [];
        foreach ($get_penawaran_aktifitas as $item) {
            $data_insert_aktifitas[] = [
                'id_penawaran'       => $post['id_quotation'],
                'id_spk_penawaran'   => $id_spk_penawaran,
                'id_aktifitas'       => $item->id_aktifitas,
                'nm_aktifitas'       => $item->aktifitas_nm,
                'bobot'              => $item->bobot,
                'mandays'            => $item->mandays,
                'mandays_rate'       => $item->mandays_rate,
                'mandays_tandem'     => $item->mandays_tandem,
                'mandays_rate_tandem' => $item->mandays_rate_tandem,
                'harga_aktifitas'    => $item->harga_aktifitas,
                'total_aktifitas'    => $item->total_aktifitas,
                'input_by'           => $user_id,
                'input_date'         => $now
            ];
        }

        // Build subcont data
        $data_insert_subcont = [];

        // Build payment data
        $data_insert_payment = [];
        if (isset($post['pt'])) {
            foreach ($post['pt'] as $item) {
                $data_insert_payment[] = [
                    'id_spk_penawaran' => $id_spk_penawaran,
                    'term_payment'     => $item['term_payment'],
                    'persen_payment'   => $this->_clean_number($item['persen_payment']),
                    'nominal_payment'  => $this->_clean_number($item['nominal_payment']),
                    'desc_payment'     => $item['desc_payment'],
                    'dibuat_oleh'      => $user_id,
                    'dibuat_tgl'       => $now
                ];
            }
        }

        // Execute all inserts via model (single transaction)
        $result = $this->Spk_penawaran_model->insert_spk_penawaran(
            $arr_insert,
            $data_insert_aktifitas,
            $data_insert_payment,
            $post['id_quotation'],
            $post['tipe_informasi_awal']
        );

        if ($result) {
            $revisi = $this->Spk_penawaran_model->get_revisi($id_spk_penawaran);
            $this->Spk_penawaran_model->save_to_history($id_spk_penawaran, $revisi);
            echo json_encode(['status' => 1, 'msg' => 'Data has been successfully saved !']);
        } else {
            echo json_encode(['status' => 0, 'msg' => 'Please try again later !']);
        }
    }

    public function update_spk_penawaran()
    {
        $post = $this->input->post();

        $id_spk_penawaran = $post['id_spk_penawaran'];

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_spk_penawaran->id_penawaran])->row();

        $this->db->select('a.id, a.id_aktifitas, a.mandays, a.mandays_rate, a.mandays_tandem, a.mandays_rate_tandem, a.harga_aktifitas, a.total_aktifitas, b.nm_aktifitas as aktifitas_nm');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $get_spk_penawaran->id_penawaran);
        $get_penawaran_aktifitas = $this->db->get()->result();

        $this->db->select('a.id_customer, a.nm_customer');
        $this->db->from('customer a');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.*, b.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->join('kons_master_paket b', 'b.id_paket = a.id_paket', 'left');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $get_divisi = $this->db->get_where(DBHR . '.divisions', ['id' => $post['divisi']])->row();

        $nm_divisi = (!empty($get_divisi)) ? $get_divisi->name : '';

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $post['project_leader']);
        $get_project_leader = $this->db->get()->row();

        $nm_project_leader = (!empty($get_project_leader)) ? $get_project_leader->nm_karyawan : '';

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $post['konsultan_1']);
        $get_konsultan_1 = $this->db->get()->row();

        $nm_konsultan_1 = (!empty($get_konsultan_1)) ? $get_konsultan_1->nm_karyawan : '';

        $this->db->select('a.id, a.name as nm_karyawan');
        $this->db->from(DBHR . '.employees a');
        $this->db->where('a.id', $post['konsultan_2']);
        $get_konsultan_2 = $this->db->get()->row();

        $nm_konsultan_2 = (!empty($get_konsultan_2)) ? $get_konsultan_2->nm_karyawan : '';

        $revisi = $this->Spk_penawaran_model->get_revisi($id_spk_penawaran);
        $this->Spk_penawaran_model->save_to_history($id_spk_penawaran, $revisi);

        $this->db->trans_begin();

        $this->db->delete('kons_tr_spk_aktifitas', ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran]);

        $tipe_info_awal_eks = (isset($post['informasi_awal_eksternal'])) ? $post['informasi_awal_eksternal'] : null;

        $detail_info_awal_eks = '';
        $cp_info_awal_eks = '';
        if (isset($post['informasi_awal_eksternal'])) {
            $detail_info_awal_eks = $post['informasi_awal_eksternal_detail_' . $tipe_info_awal_eks];
            $cp_info_awal_eks = $post['informasi_awal_eksternal_cp_' . $tipe_info_awal_eks];
        }

        $arr_insert = [
            'id_customer' => $get_customer->id_customer,
            'nm_customer' => $get_customer->nm_customer,
            'address' => $post['address'],
            'npwp_cust' => $post['no_npwp'],
            'nm_pic' => $post['pic'],
            'tipe_informasi_awal' => $get_penawaran->tipe_informasi_awal,
            'detail_informasi_awal' => $get_penawaran->detail_informasi_awal,
            'waktu_from' => $post['waktu_from'],
            'waktu_to' => $post['waktu_to'],
            'id_sales' => $get_marketing->id,
            'nm_sales' => $get_marketing->nm_karyawan,
            'upload_proposal' => $get_penawaran->upload_proposal,
            'id_project' => $get_konsultasi->id_konsultasi_h,
            'nm_project' => $get_konsultasi->nm_paket,
            'id_divisi' => $post['divisi'],
            'nm_divisi' => $nm_divisi,
            'id_project_leader' => $post['project_leader'],
            'nm_project_leader' => $nm_project_leader,
            'id_konsultan_1' => $post['konsultan_1'],
            'nm_konsultan_1' => $nm_konsultan_1,
            'id_konsultan_2' => $post['konsultan_2'],
            'nm_konsultan_2' => $nm_konsultan_2,
            'nilai_kontrak' => ($post['nilai_kontrak'] !== '') ? str_replace(',', '', $post['nilai_kontrak']) : 0,
            'biaya_subcont' => 0,
            'biaya_akomodasi' => ($post['biaya_akomodasi'] !== '') ? str_replace(',', '', $post['biaya_akomodasi']) : 0,
            'biaya_others' => ($post['biaya_others'] !== '') ? str_replace(',', '', $post['biaya_others']) : 0,
            'biaya_tandem' => ($post['biaya_tandem'] !== '') ? str_replace(',', '', $post['biaya_tandem']) : 0,
            'biaya_lab' => ($post['biaya_lab'] !== '') ? str_replace(',', '', $post['biaya_lab']) : 0,
            'biaya_subcont_tenaga_ahli' => ($post['biaya_subcont_tenaga_ahli'] !== '') ? str_replace(',', '', $post['biaya_subcont_tenaga_ahli']) : 0,
            'biaya_subcont_perusahaan' => ($post['biaya_subcont_perusahaan'] !== '') ? str_replace(',', '', $post['biaya_subcont_perusahaan']) : 0,
            'nilai_kontrak_bersih' => ($post['nilai_kontrak_bersih'] !== '') ? str_replace(',', '', $post['nilai_kontrak_bersih']) : 0,
            'mandays_rate' => ($post['mandays_rate'] !== '') ? str_replace(',', '', $post['mandays_rate']) : 0,
            'total_mandays' => ($post['total_mandays'] !== '') ? str_replace(',', '', $post['total_mandays']) : 0,
            'mandays_subcont' => 0,
            'mandays_internal' => ($post['total_mandays'] !== '') ? str_replace(',', '', $post['total_mandays']) : 0,
            'nm_pemberi_informasi_1_komisi' => $post['nm_pemberi_informasi_1_komisi'],
            'persen_pemberi_informasi_1_komisi' => ($post['persentase_pemberi_informasi_1_komisi'] !== '') ? str_replace(',', '', $post['persentase_pemberi_informasi_1_komisi']) : 0,
            'nominal_pemberi_informasi_1_komisi' => ($post['nominal_pemberi_informasi_1_komisi'] !== '') ? str_replace(',', '', $post['nominal_pemberi_informasi_1_komisi']) : 0,
            'nm_pemberi_informasi_2_komisi' => $post['nm_pemberi_informasi_2_komisi'],
            'persen_pemberi_informasi_2_komisi' => ($post['persentase_pemberi_informasi_2_komisi'] !== '') ? str_replace(',', '', $post['persentase_pemberi_informasi_2_komisi']) : 0,
            'nominal_pemberi_informasi_2_komisi' => ($post['nominal_pemberi_informasi_2_komisi'] !== '') ? str_replace(',', '', $post['nominal_pemberi_informasi_2_komisi']) : 0,
            'nm_sales_1_komisi' => $post['nm_sales_1_komisi'],
            'persen_sales_1_komisi' => ($post['persentase_sales_1_komisi'] !== '') ? str_replace(',', '', $post['persentase_sales_1_komisi']) : 0,
            'nominal_sales_1_komisi' => ($post['nominal_sales_1_komisi'] !== '') ? str_replace(',', '', $post['nominal_sales_1_komisi']) : 0,
            'nm_sales_2_komisi' => $post['nm_sales_2_komisi'],
            'persen_sales_2_komisi' => ($post['persentase_sales_2_komisi'] !== '') ? str_replace(',', '', $post['persentase_sales_2_komisi']) : 0,
            'nominal_sales_2_komisi' => ($post['nominal_sales_2_komisi'] !== '') ? str_replace(',', '', $post['nominal_sales_2_komisi']) : 0,
            'isu_khusus' => $post['isu_khusus'],
            'tipe_info_awal_eks' => $tipe_info_awal_eks,
            'detail_info_awal_eks' => $detail_info_awal_eks,
            'cp_info_awal_eks' => $cp_info_awal_eks,
            'reject_project_leader_sts' => null,
            'reject_konsultan_1_sts' => null,
            'reject_konsultan_2_sts' => null,
            'reject_sales_sts' => null,
            'reject_manager_sales_sts' => null,
            'reject_level2_by' => null,
            'edited_by' => $this->auth->user_id(),
            'edited_date' => date('Y-m-d H:i:s')
        ];

        $data_insert_aktifitas = [];
        if (!empty($get_penawaran_aktifitas)) {
            foreach ($get_penawaran_aktifitas as $item) {

                $data_insert_aktifitas[] = [
                    'id_penawaran' => $get_spk_penawaran->id_penawaran,
                    'id_spk_penawaran' => $id_spk_penawaran,
                    'id_aktifitas' => $item->id_aktifitas,
                    'nm_aktifitas' => $item->aktifitas_nm,
                    'mandays' => $item->mandays,
                    'mandays_rate' => $item->mandays_rate,
                    'mandays_tandem' => $item->mandays_tandem,
                    'mandays_rate_tandem' => $item->mandays_rate_tandem,
                    'harga_aktifitas' => $item->harga_aktifitas,
                    'total_aktifitas' => $item->total_aktifitas,
                    'input_by' => $this->auth->user_id(),
                    'input_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $data_insert_subcont = [];

        $data_insert_payment = [];

        if (isset($post['pt'])) {
            foreach ($post['pt'] as $item) {
                $data_insert_payment[] = [
                    'id_spk_penawaran' => $id_spk_penawaran,
                    'term_payment' => $item['term_payment'],
                    'persen_payment' => ($item['persen_payment'] !== '') ? str_replace(',', '', $item['persen_payment']) : 0,
                    'nominal_payment' => ($item['nominal_payment'] !== '') ? str_replace(',', '', $item['nominal_payment']) : 0,
                    'desc_payment' => $item['desc_payment'],
                    'dibuat_oleh' => $this->auth->user_id(),
                    'dibuat_tgl' => date('Y-m-d H:i:s')
                ];
            }
        }

        $update_spk_penawaran = $this->db->update('kons_tr_spk_penawaran', $arr_insert, ['id_spk_penawaran' => $id_spk_penawaran]);
        if (!$update_spk_penawaran) {
            $this->db->trans_rollback();
            print_r($this->db->error($update_spk_penawaran) . ' ' . $this->db->last_query());
            exit;
        }

        if (!empty($data_insert_aktifitas)) {
            $insert_aktifitas = $this->db->insert_batch('kons_tr_spk_aktifitas', $data_insert_aktifitas);
            if (!$insert_aktifitas) {
                $this->db->trans_rollback();
                print_r($this->db->error($insert_aktifitas) . ' ' . $this->db->last_query());
                exit;
            }
        }



        if (!empty($data_insert_payment)) {
            $insert_spk_penawaran_payment = $this->db->insert_batch('kons_tr_spk_penawaran_payment', $data_insert_payment);
            if (!$insert_spk_penawaran_payment) {
                $this->db->trans_rollback();
                print_r($this->db->error($insert_spk_penawaran_payment) . ' ' . $this->db->last_query());
                exit;
            }
        }

        $update_penawaran_sts_cust = $this->db->update('kons_tr_penawaran', ['sts_cust' => $post['tipe_informasi_awal']], ['id_quotation' => $get_spk_penawaran->id_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $msg = 'Data has been successfully saved !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    public function del_spk()
    {
        $id_spk_penawaran = $this->input->post('id_spk_penawaran');

        $this->db->trans_begin();

        $this->db->update('kons_tr_penawaran', ['id_spk_penawaran' => null], ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran]);
        $this->db->delete('kons_tr_spk_aktifitas', ['id_spk_penawaran' => $id_spk_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $msg = 'Data has been successfully deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
        ]);
    }

    public function one_time()
    {

        $get_spk_divisi = $this->db->select('a.id_divisi, a.nm_divisi')->from('kons_tr_spk_penawaran a')->where('a.id_divisi <>', '')->where('a.id_divisi <>', null)->get()->result();

        // foreach($get_spk_divisi as $item) {
        //     $id_divisi_hris = '';
        //     if($item->id_divisi == '')
        // }

        // $get_spk_project_leader = $this->db->select('a.id_project_leader, a.nm_project_leader')->from('kons_tr_spk_penawaran a')->where('a.id_project_leader <>', '')->not_like('a.id_project_leader', 'EMP', 'both')->get()->result();

        // foreach($get_spk_project_leader as $item) {
        //     $id_employees_hris = '';

        //     if($item->id_project_leader == '166') {
        //         $id_employees_hris = 'EMP0213';
        //     }
        //     if($item->id_project_leader == '169') {
        //         $id_employees_hris = 'EMP0187';
        //     }
        //     if($item->id_project_leader == '170') {
        //         $id_employees_hris = 'EMP0121';
        //     }
        //     if($item->id_project_leader == '172') {
        //         $id_employees_hris = 'EMP0011';
        //     }
        //     if($item->id_project_leader == '173') {
        //         $id_employees_hris = 'EMP0018';
        //     }
        //     if($item->id_project_leader == '174') {
        //         $id_employees_hris = 'EMP0005';
        //     }
        //     if($item->id_project_leader == '175') {
        //         $id_employees_hris = 'EMP0090';
        //     }
        //     if($item->id_project_leader == '176') {
        //         $id_employees_hris = 'EMP0001';
        //     }
        //     if($item->id_project_leader == '183') {
        //         $id_employees_hris = 'EMP0207';
        //     }

        //     if($id_employees_hris !== '') {
        //         $get_hris_employees = $this->db->get_where(DBHR.'.employees', ['id' => $id_employees_hris])->row();
        //         if(!empty($get_hris_employees)) {
        //             $this->db->update('kons_tr_spk_penawaran', ['id_project_leader' => $id_employees_hris, 'nm_project_leader' => $get_hris_employees->name], ['id_project_leader' => $item->id_project_leader]);
        //         }
        //     }
        // }

        // $get_spk_konsultan_1 = $this->db->select('a.id_konsultan_1, a.nm_konsultan_1')->from('kons_tr_spk_penawaran a')->where('a.id_konsultan_1 <>', '')->not_like('a.id_konsultan_1', 'EMP', 'both')->get()->result();

        // foreach($get_spk_konsultan_1 as $item) {
        //     $id_employees_hris = '';

        //     if($item->id_konsultan_1 == '166') {
        //         $id_employees_hris = 'EMP0213';
        //     }
        //     if($item->id_konsultan_1 == '169') {
        //         $id_employees_hris = 'EMP0187';
        //     }
        //     if($item->id_konsultan_1 == '170') {
        //         $id_employees_hris = 'EMP0121';
        //     }
        //     if($item->id_konsultan_1 == '172') {
        //         $id_employees_hris = 'EMP0011';
        //     }
        //     if($item->id_konsultan_1 == '173') {
        //         $id_employees_hris = 'EMP0018';
        //     }
        //     if($item->id_konsultan_1 == '174') {
        //         $id_employees_hris = 'EMP0005';
        //     }
        //     if($item->id_konsultan_1 == '175') {
        //         $id_employees_hris = 'EMP0090';
        //     }
        //     if($item->id_konsultan_1 == '176') {
        //         $id_employees_hris = 'EMP0001';
        //     }
        //     if($item->id_konsultan_1 == '183') {
        //         $id_employees_hris = 'EMP0207';
        //     }

        //     if($id_employees_hris !== '') {
        //         $get_hris_employees = $this->db->get_where(DBHR.'.employees', ['id' => $id_employees_hris])->row();
        //         if(!empty($get_hris_employees)) {
        //             $this->db->update('kons_tr_spk_penawaran', ['id_konsultan_1' => $id_employees_hris, 'nm_konsultan_1' => $get_hris_employees->name], ['id_konsultan_1' => $item->id_konsultan_1]);
        //         }
        //     }
        // }

        // $get_spk_konsultan_2 = $this->db->select('a.id_konsultan_2, a.nm_konsultan_2')->from('kons_tr_spk_penawaran a')->where('a.id_konsultan_2 <>', '')->not_like('a.id_konsultan_2', 'EMP', 'both')->get()->result();

        // foreach($get_spk_konsultan_2 as $item) {
        //     $id_employees_hris = '';

        //     if($item->id_konsultan_2 == '166') {
        //         $id_employees_hris = 'EMP0213';
        //     }
        //     if($item->id_konsultan_2 == '169') {
        //         $id_employees_hris = 'EMP0187';
        //     }
        //     if($item->id_konsultan_2 == '170') {
        //         $id_employees_hris = 'EMP0121';
        //     }
        //     if($item->id_konsultan_2 == '172') {
        //         $id_employees_hris = 'EMP0011';
        //     }
        //     if($item->id_konsultan_2 == '173') {
        //         $id_employees_hris = 'EMP0018';
        //     }
        //     if($item->id_konsultan_2 == '174') {
        //         $id_employees_hris = 'EMP0005';
        //     }
        //     if($item->id_konsultan_2 == '175') {
        //         $id_employees_hris = 'EMP0090';
        //     }
        //     if($item->id_konsultan_2 == '176') {
        //         $id_employees_hris = 'EMP0001';
        //     }
        //     if($item->id_konsultan_2 == '183') {
        //         $id_employees_hris = 'EMP0207';
        //     }

        //     if($id_employees_hris !== '') {
        //         $get_hris_employees = $this->db->get_where(DBHR.'.employees', ['id' => $id_employees_hris])->row();
        //         if(!empty($get_hris_employees)) {
        //             $this->db->update('kons_tr_spk_penawaran', ['id_konsultan_2' => $id_employees_hris, 'nm_konsultan_2' => $get_hris_employees->name], ['id_konsultan_2' => $item->id_konsultan_2]);
        //         }
        //     }
        // }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $msg = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $msg = 'One time query has been successful !';
        }

        echo json_encode([
            'status' => $valid,
            'msg' => $msg
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


    public function create_spk_non_konsultasi()
    {
        $this->template->title('Create SPK Penawaran Non Konsultasi');
        $this->template->render('choose_spk_non_kons');
    }

    public function table_penawaran_non_kons()
    {
        $post = $this->input->post();

        $draw = $post['draw'];
        $length = $post['length'];
        $start = $post['start'];
        $search = $post['search']['value'];

        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_non_konsultasi a');
        $this->db->join('kons_tr_spk_non_kons b', 'b.id_penawaran = a.id_penawaran', 'left');
        $this->db->where('a.sts_deal', '1');
        $this->db->where('a.sts_quot', '1');
        $this->db->where('b.id_penawaran', null);

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        if (!empty($search)) {
            $arr_filter_col = [
                'a.id_penawaran',
                'a.tgl_quotation',
                'a.pic_penawaran',
                'a.keterangan_penawaran',
                'a.nm_customer',
                'a.grand_total'
            ];

            $this->db->group_start();

            $no_col = 0;
            foreach ($arr_filter_col as $item_col) {
                $no_col++;

                if ($no_col <= 1) {
                    $this->db->like($item_col, $search, 'both');
                } else {
                    $this->db->or_like($item_col, $search, 'both');
                }
            }
            $this->db->group_end();
        }

        $db_clone = clone $this->db;
        $count_filtered = $db_clone->count_all_results();

        $this->db->group_by('a.id_penawaran');
        $this->db->order_by('a.id_penawaran', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get()->result();

        $no = (0 + $start);
        $hasil = [];

        foreach ($get_data as $item) {
            $no++;

            $btn_create = '<a href="' . base_url('spk_penawaran/add_spk_non_kons/' . $item->id_penawaran) . '" class="btn btn-sm btn-primary" title="Create SPK Penawaran"><i class="fa fa-check"></i></a>';

            $hasil[] = [
                'no' => $no,
                'id_quotation' => $item->id_penawaran,
                'date' => date('d F Y', strtotime($item->tgl_quotation)),
                'pic_penawaran' => $item->pic_penawaran,
                'penawaran' => $item->keterangan_penawaran,
                'customer' => $item->nm_customer,
                'grand_total' => number_format($item->grand_total),
                'action' => $btn_create
            ];
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_filtered,
            'data' => $hasil
        ];
        echo json_encode($response);
    }

    public function save_spk_penawaran_non_konsultasi()
    {
        $this->db->trans_begin();

        try {
            $id_spk_penawaran = $this->Spk_penawaran_model->generate_id_spk_penawaran_non_kons();

            $get_penawaran = $this->Spk_penawaran_model->get_penawaran_non_kons($this->input->post('id_quotation', true));

            $id_customer = (!empty($get_penawaran->id_customer)) ? $get_penawaran->id_customer : '';
            $nm_customer = (!empty($get_penawaran->nm_customer)) ? $get_penawaran->nm_customer : '';
            $address = (!empty($get_penawaran->address)) ? $get_penawaran->address : '';
            $pic = (!empty($get_penawaran->pic)) ? $get_penawaran->pic : '';
            $tipe_informasi_awal = (!empty($get_penawaran->tipe_informasi_awal)) ? $get_penawaran->tipe_informasi_awal : '';
            $detail_informasi_awal = (!empty($get_penawaran->detail_informasi_awal)) ? $get_penawaran->detail_informasi_awal : '';
            $keterangan_penawaran = (!empty($get_penawaran->keterangan_penawaran)) ? $get_penawaran->keterangan_penawaran : '';
            $id_divisi = (!empty($get_penawaran->id_divisi)) ? $get_penawaran->id_divisi : '';
            $nm_divisi = (!empty($get_penawaran->nm_divisi)) ? $get_penawaran->nm_divisi : '';

            $informasi_awal_eksternal = (isset($_POST['informasi_awal_eksternal'])) ? $this->input->post('informasi_awal_eksternal', true) : '';
            if (!empty($informasi_awal_eksternal)) {
                if ($informasi_awal_eksternal == 'bs') {
                    $detail_informasi_awal_eksternal = $this->input->post('informasi_awal_eksternal_detail_bs', true);
                    $cp_info_awal_eks = $this->input->post('informasi_awal_eksternal_cp_bs', true);
                } else {
                    $detail_informasi_awal_eksternal = $this->input->post('informasi_awal_eksternal_detail_lain', true);
                    $cp_info_awal_eks = $this->input->post('informasi_awal_eksternal_cp_lain', true);
                }
            } else {
                $detail_informasi_awal_eksternal = '';
                $cp_info_awal_eks = '';
            }

            $get_project_leader = $this->Spk_penawaran_model->get_list_employee($this->input->post('project_leader', true));
            $nm_project_leader = (!empty($get_project_leader->name)) ? $get_project_leader->name : '';

            $get_konsultan_1 = $this->Spk_penawaran_model->get_list_employee($this->input->post('konsultan_1', true));
            $nm_konsultan_1 = (!empty($get_konsultan_1->name)) ? $get_konsultan_1->name : '';

            $get_konsultan_2 = $this->Spk_penawaran_model->get_list_employee($this->input->post('konsultan_2', true));
            $nm_konsultan_2 = (!empty($get_konsultan_2->name)) ? $get_konsultan_2->name : '';

            $nilai_kontrak = (!empty($get_penawaran->grand_total)) ? $get_penawaran->grand_total : 0;

            $get_sales = $this->Spk_penawaran_model->get_list_employee($this->input->post('sales', true));

            $id_sales = (!empty($get_sales)) ? $get_sales->id : '';
            $nm_sales = (!empty($get_sales)) ? $get_sales->name : '';

            $arr_insert_header = [
                'id_spk_penawaran' => $id_spk_penawaran,
                'id_penawaran' => $this->input->post('id_quotation', true),
                'id_customer' => $id_customer,
                'nm_customer' => $nm_customer,
                'address' => $address,
                'npwp_cust' => $this->input->post('no_npwp', true),
                'nm_pic' => $pic,
                'tipe_informasi_awal' => $tipe_informasi_awal,
                'detail_informasi_awal' => $detail_informasi_awal,
                'tipe_info_awal_eks' => $informasi_awal_eksternal,
                'detail_info_awal_eks' => $detail_informasi_awal_eksternal,
                'cp_info_awal_eks' => $cp_info_awal_eks,
                'waktu_from' => $this->input->post('waktu_from', true),
                'waktu_to' => $this->input->post('waktu_to', true),
                'nm_project' => $keterangan_penawaran,
                'id_divisi' => $id_divisi,
                'nm_divisi' => $nm_divisi,
                'id_sales' => $id_sales,
                'nm_sales' => $nm_sales,
                'id_project_leader' => $this->input->post('project_leader', true),
                'nm_project_leader' => $nm_project_leader,
                'id_konsultan_1' => $this->input->post('konsultan_1', true),
                'nm_konsultan_1' => $nm_konsultan_1,
                'id_konsultan_2' => $this->input->post('konsultan_2', true),
                'nm_konsultan_2' => $nm_konsultan_2,
                'nilai_kontrak' => $nilai_kontrak,
                'nilai_kontrak_bersih' => $nilai_kontrak,
                'isu_khusus' => $this->input->post('isu_khusus', true),
                'sts_spk' => '0',
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('kons_tr_spk_non_kons', $arr_insert_header);

            $arr_insert_top = [];
            if (isset($_POST['pt'])) {
                $pt = $this->input->post('pt', true);

                foreach ($pt as $item_pt) {
                    $term_payment = $item_pt['term_payment'];
                    $persen_payment = $item_pt['persen_payment'];
                    $nominal_payment = str_replace(',', '', $item_pt['nominal_payment']);
                    $desc_payment = $item_pt['desc_payment'];

                    $arr_insert_top[] = [
                        'id_spk_penawaran' => $id_spk_penawaran,
                        'term_payment' => $term_payment,
                        'persen_payment' => $persen_payment,
                        'nominal_payment' => $nominal_payment,
                        'desc_payment' => $desc_payment,
                        'dibuat_oleh' => $this->auth->user_id(),
                        'dibuat_tgl' => date('Y-m-d H:i:s')
                    ];
                }
            }

            if (!empty($arr_insert_top)) {
                $this->db->insert_batch('kons_tr_spk_non_kons_payment', $arr_insert_top);
            }

            if ($this->db->trans_status() === false) {
                throw new Exception('Database transaction failed');
            }

            $this->db->trans_commit();

            $this->output->set_status_header(200);

            $response = [
                'msg' => 'Data berhasil disimpan !'
            ];

            echo json_encode($response);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header(500);
            echo json_encode([
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function del_spk_non_kons()
    {
        $id_spk_penawaran = $this->input->post('id_spk_penawaran', true);

        $this->db->trans_begin();

        try {
            $arr_update = [
                'deleted_by' => $this->auth->user_id(),
                'deleted_date' => date('Y-m-d H:i:s')
            ];

            $this->db->update('kons_tr_spk_non_kons', $arr_update, array('id_spk_penawaran' => $id_spk_penawaran));

            $this->db->trans_commit();

            $this->output->set_status_header(200);

            echo json_encode(array(
                'msg' => 'Data has been deleted !'
            ));
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output->set_status_header($e->getCode());
            echo json_encode(array(
                'msg' => $e->getMessage()
            ));
        }
    }

    public function view_non_kons($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $data_spk_non_kons = $this->Spk_penawaran_model->get_spk_non_kons($id_spk_penawaran);

        $data = [
            'data_spk_non_kons' => $data_spk_non_kons
        ];

        $this->template->title('View SPK Non Konsultasi');
        $this->template->set($data);
        $this->template->render('view_spk_non_kons');
    }

    public function table_spk_non_konsultasi()
    {
        $draw = $this->input->post('draw', true);
        $length = $this->input->post('length', true);
        $start = $this->input->post('start', true);
        $search_post = $this->input->post('search', true);
        $search = is_array($search_post) ? ($search_post['value'] ?? '') : '';
        $order = $this->input->post('order', true);

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_non_kons a');
        $this->db->where('a.deleted_by', null);

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        if (!empty($search)) {

            $columnSearch = [
                'a.id_spk_penawaran',
                'a.nm_sales',
                'a.nm_project',
                'a.nm_customer',
                'a.nilai_kontrak'
            ];

            $this->db->group_start();

            $no_search = 1;
            foreach ($columnSearch as $item_search) {
                if ($no_search == 1) {
                    $this->db->like($item_search, $search, 'both');
                } else {
                    $this->db->or_like($item_search, $search, 'both');
                }

                $no_search++;
            }
            $this->db->group_end();
        }

        $db_clone = clone $this->db;
        $count_filtered = $db_clone->count_all_results();

        if (isset($order) && !empty($order)) {
            $columns = array(
                0 => '',
                1 => 'a.id_spk_penawaran',
                2 => 'a.nm_sales',
                3 => 'a.nm_project',
                4 => 'a.nm_customer',
                5 => 'a.nilai_kontrak'
            );

            $columnIndex = $this->input->post('order', true)[0]['column'];
            $columnName = $columns[$columnIndex];
            $columnSortOrder = $this->input->post('order')[0]['dir'];

            $this->db->order_by($columnName, $columnSortOrder);
        } else {
            $this->db->order_by('a.input_date', 'desc');
        }

        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $no = (0 + $start);
        $data = [];

        foreach ($get_data->result() as $item) {
            $no++;

            $sts_spk = $this->Spk_penawaran_model->render_status_spk($item);

            $action = $this->Spk_penawaran_model->render_action_non_kons($item);

            $data[] = [
                'no' => $no,
                'id_spk_penawaran' => $item->id_spk_penawaran,
                'nm_marketing' => $item->nm_sales,
                'nm_paket' => $item->nm_project,
                'nm_customer' => $item->nm_customer,
                'grand_total' => number_format($item->nilai_kontrak),
                'status_spk' => $sts_spk,
                'action' => $action
            ];
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_filtered,
            'data' => $data
        ];

        echo json_encode($response);
    }

    public function export_excel()
    {
        $this->db->select('a.*, b.grand_total, c.nm_lengkap');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->join('users c', 'c.id_user = a.input_by', 'left');
        $this->db->where('a.deleted_by', null);
        $this->db->where('a.id_penawaran IS NOT NULL');
        $this->db->where('a.id_penawaran <>', '');
        $this->db->order_by('a.input_date', 'desc');
        $get_data = $this->db->get()->result();

        $hasil = [];
        $no = 1;
        foreach ($get_data as $item) {
            $this->db->select('a.*');
            $this->db->from('kons_master_konsultasi_header a');
            $this->db->where('a.id_konsultasi_h', $item->id_project);
            $get_package = $this->db->get()->row();
            $nm_paket = (!empty($get_package)) ? $get_package->nm_paket : '';

            $grand_total = (!empty($item->grand_total) && $item->grand_total > 0) ? $item->grand_total : $item->nilai_kontrak;

            $hasil[] = [
                'no' => $no,
                'id_spk_penawaran' => $item->id_spk_penawaran,
                'nm_marketing' => ucfirst($item->nm_sales),
                'nm_paket' => $nm_paket,
                'nm_customer' => $item->nm_customer,
                'grand_total' => $grand_total,
                'created_by' => $item->nm_lengkap,
                'created_date' => date('d F Y H:i:s', strtotime($item->input_date))
            ];
            $no++;
        }

        $data = [
            'results' => $hasil
        ];

        $this->load->view('export_excel', $data);
    }
}
