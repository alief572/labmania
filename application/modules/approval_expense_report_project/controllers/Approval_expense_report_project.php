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
class Approval_expense_report_project extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Approval_Expense_Report_Project.View';
    protected $addPermission      = 'Approval_Expense_Report_Project.Add';
    protected $managePermission = 'Approval_Expense_Report_Project.Manage';
    protected $deletePermission = 'Approval_Expense_Report_Project.Delete';

    protected $otherdb;
    protected $gl;

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Expense Report Project');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Approval_expense_report_project/Approval_expense_report_project_model'));
        date_default_timezone_set('Asia/Bangkok');

        $this->otherdb = $this->load->database('sendigs_finance', TRUE);
        $this->gl = $this->load->database('gl_sendigs', true);
    }

    // View Page Function

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval Expense Report Project');
        $this->template->render('index');
    }

    public function req_app($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->where('a.id_header', $id_header);
        $get_header = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_bukti_pengembalian a');
        $this->db->where('a.id_header_expense', $get_header->id);
        $get_bukti_pengembalian = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_bukti_penggunaan_expense a');
        $this->db->where('a.id_header_expense', $get_header->id);
        $get_bukti_penggunaan = $this->db->get()->result();

        $list_bukti_penggunaan_by_detail = [];
        if (!empty($get_bukti_penggunaan)) {
            foreach ($get_bukti_penggunaan as $bp) {
                $list_bukti_penggunaan_by_detail[$bp->id_detail_kasbon][] = $bp;
            }
        }

        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header a', ['a.id' => $id_header])->row();

        $this->db->select('a.*, b.nm_sales, b.waktu_from, b.waktu_to, c.nm_paket');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header c', 'c.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
        $get_spk_budgeting = $this->db->get()->row();

        $datalist_item = [];
        $datalist_item_expense = [];

        $ada_pph = 0;

        if ($get_kasbon_header->tipe == 1) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_spk_budgeting_aktifitas a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_aktifitas', 'asc');
            $get_list_subcont = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_ovb_subcont a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_subcont_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_aktifitas', $item->id_aktifitas);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_aktifitas,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            foreach ($get_list_subcont_custom as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_aktifitas', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        if ($get_kasbon_header->tipe == 2) {
            $this->db->select('a.*, b.id as id_biaya, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_akomodasi = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_akomodasi a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_akomodasi_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_akomodasi as $item) {
                $no++;

                if ($item->id_biaya == '15') {
                    $ada_pph = '1';
                }

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_akomodasi', $item->id_akomodasi);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            foreach ($get_list_akomodasi_custom as $item) {
                $no++;

                // if ($item->id_biaya == '15') {
                //     $ada_pph = '1';
                // }

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_akomodasi', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        if ($get_kasbon_header->tipe == 3) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_others a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_others = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_others a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_others_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_others as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_others', $item->id_others);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            foreach ($get_list_others_custom as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_others', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        if ($get_kasbon_header->tipe == 4) {
            $this->db->select('a.*, b.isu_lingkungan as nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_lab a');
            $this->db->join('kons_master_lab b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_lab = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_lab a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_lab_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_lab as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_lab a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_lab', $item->id_lab);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            foreach ($get_list_lab_custom as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_lab a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_lab', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        if ($get_kasbon_header->tipe == 5) {
            $this->db->select('a.*, b.nm_biaya as nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_subcont_tenaga_ahli a');
            $this->db->join('kons_master_tenaga_ahli b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_subcont_tenaga_ahli = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_subcont_tenaga_ahli a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_subcont_tenaga_ahli_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont_tenaga_ahli as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_subcont', $item->id_subcont);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            foreach ($get_list_subcont_tenaga_ahli_custom as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_subcont', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        if ($get_kasbon_header->tipe == 6) {
            $this->db->select('a.*, b.nm_biaya as nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_subcont_perusahaan a');
            $this->db->join('kons_master_subcont_perusahaan b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_subcont_perusahaan = $this->db->get()->result();

            $this->db->select('a.*');
            $this->db->from('kons_tr_kasbon_custom_subcont_perusahaan a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $get_list_subcont_perusahaan_custom = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont_perusahaan as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_subcont', $item->id_subcont);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }
            foreach ($get_list_subcont_perusahaan_custom as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;
                $total_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_subcont', $item->id);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                    $total_kasbon = $get_kasbon->total_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_item,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon,
                    'total_kasbon' => $total_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $qty_expense = ($item->qty_expense >= 1) ? $item->qty_expense : 1;
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense,
                    'keterangan' => $item->keterangan,
                    'total_expense' => ($qty_expense * $item->nominal_expense)
                ];
            }
        }

        $this->otherdb->select('a.id, a.rekening, a.nama, a.coa_bank, b.nama_bank');
        $this->otherdb->from('ms_bank a');
        $this->otherdb->join('list_bank b', 'b.id = a.bank', 'left');
        $this->otherdb->where('a.deleted', '0');
        $get_bank = $this->otherdb->get()->result();

        $list_jurnal_pph21 = $this->Approval_expense_report_project_model->list_jurnal_pph21($id_header);

        $data = [
            'header' => $get_header,
            'list_bukti_pengembalian' => $get_bukti_pengembalian,
            'list_bukti_penggunaan' => $get_bukti_penggunaan,
            'list_bukti_penggunaan_by_detail' => $list_bukti_penggunaan_by_detail,
            'datalist_item' => $datalist_item,
            'datalist_item_expense' => $datalist_item_expense,
            'id_spk_budgeting' => $get_kasbon_header->id_spk_budgeting,
            'id_header' => $id_header,
            'id_spk_penawaran' => $get_kasbon_header->id_spk_penawaran,
            'id_penawaran' => $get_kasbon_header->id_penawaran,
            'tipe' => $get_kasbon_header->tipe,
            'list_bank' => $get_bank,
            'ada_pph' => $ada_pph,
            'list_jurnal_pph21' => $list_jurnal_pph21,
            'list_budgeting' => $get_spk_budgeting
        ];

        $this->template->set($data);
        $this->template->render('request_approval');
    }

    public function add_expense_subcont($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header a', ['a.id' => $id_header])->row();

        $datalist_item = [];

        if ($get_kasbon_header->tipe == 1) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_spk_budgeting_aktifitas a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_aktifitas', 'asc');
            $get_list_subcont = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_aktifitas', $item->id_aktifitas);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_aktifitas,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }
        }

        if ($get_kasbon_header->tipe == 2) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_akomodasi = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_akomodasi as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }
        }

        if ($get_kasbon_header->tipe == 3) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_others a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_others = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_others as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }
        }

        $data = [
            'datalist_item' => $datalist_item,
            'id_spk_budgeting' => $get_kasbon_header->id_spk_budgeting,
            'id_header' => $id_header,
            'id_spk_penawaran' => $get_kasbon_header->id_spk_penawaran,
            'id_penawaran' => $get_kasbon_header->id_penawaran,
            'tipe' => $get_kasbon_header->tipe
        ];

        $this->template->set($data);
        $this->template->render('add_expense_subcont');
    }

    public function edit_expense_subcont($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->where('a.id_header', $id_header);
        $get_header = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_bukti_pengembalian a');
        $this->db->where('a.id_header_expense', $get_header->id);
        $get_bukti_pengembalian = $this->db->get()->result();

        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header a', ['a.id' => $id_header])->row();

        $datalist_item = [];
        $datalist_item_expense = [];

        if ($get_kasbon_header->tipe == 1) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_spk_budgeting_aktifitas a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_aktifitas', 'asc');
            $get_list_subcont = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_aktifitas', $item->id_aktifitas);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_aktifitas,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        if ($get_kasbon_header->tipe == 2) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_akomodasi = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_akomodasi as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        if ($get_kasbon_header->tipe == 3) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_others a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_others = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_others as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        $data = [
            'header' => $get_header,
            'list_bukti_pengembalian' => $get_bukti_pengembalian,
            'datalist_item' => $datalist_item,
            'datalist_item_expense' => $datalist_item_expense,
            'id_spk_budgeting' => $get_kasbon_header->id_spk_budgeting,
            'id_header' => $id_header,
            'id_spk_penawaran' => $get_kasbon_header->id_spk_penawaran,
            'id_penawaran' => $get_kasbon_header->id_penawaran,
            'tipe' => $get_kasbon_header->tipe
        ];

        $this->template->set($data);
        $this->template->render('edit_expense_subcont');
    }

    public function view_expense_subcont($id_header)
    {
        $id_header = urldecode($id_header);
        $id_header = str_replace('|', '/', $id_header);

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->where('a.id_header', $id_header);
        $get_header = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_bukti_pengembalian a');
        $this->db->where('a.id_header_expense', $get_header->id);
        $get_bukti_pengembalian = $this->db->get()->result();

        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header a', ['a.id' => $id_header])->row();

        $datalist_item = [];
        $datalist_item_expense = [];

        if ($get_kasbon_header->tipe == 1) {
            $this->db->select('a.*');
            $this->db->from('kons_tr_spk_budgeting_aktifitas a');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_aktifitas', 'asc');
            $get_list_subcont = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_subcont as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_aktifitas', $item->id_aktifitas);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_aktifitas,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        if ($get_kasbon_header->tipe == 2) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_akomodasi = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_akomodasi as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        if ($get_kasbon_header->tipe == 3) {
            $this->db->select('a.*, b.nm_biaya');
            $this->db->from('kons_tr_spk_budgeting_others a');
            $this->db->join('kons_master_biaya b', 'b.id = a.id_item', 'left');
            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
            $this->db->order_by('a.id_item', 'asc');
            $get_list_others = $this->db->get()->result();

            $no = 0;
            foreach ($get_list_others as $item) {
                $no++;

                $qty_kasbon = 0;
                $nominal_kasbon = 0;

                $this->db->select('a.*');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $id_header);
                $this->db->where('a.id_item', $item->id_item);
                $get_kasbon = $this->db->get()->row();
                if (!empty($get_kasbon)) {
                    $qty_kasbon = $get_kasbon->qty_pengajuan;
                    $nominal_kasbon = $get_kasbon->nominal_pengajuan;
                }

                $datalist_item[] = [
                    'no' => $no,
                    'id_detail_kasbon' => $item->id,
                    'nm_item' => $item->nm_biaya,
                    'qty_kasbon' => $qty_kasbon,
                    'nominal_kasbon' => $nominal_kasbon
                ];
            }

            $this->db->select('a.*');
            $this->db->from('kons_tr_expense_report_project_detail a');
            $this->db->where('a.id_header_kasbon', $id_header);
            $get_expense_detail = $this->db->get()->result();

            $no = 1;
            foreach ($get_expense_detail as $item) {
                $datalist_item_expense[$item->id_detail_kasbon] = [
                    'id' => $item->id,
                    'id_detail_kasbon' => $item->id_detail_kasbon,
                    'tipe' => $item->tipe,
                    'qty_expense' => $item->qty_expense,
                    'nominal_expense' => $item->nominal_expense
                ];
            }
        }

        $data = [
            'header' => $get_header,
            'list_bukti_pengembalian' => $get_bukti_pengembalian,
            'datalist_item' => $datalist_item,
            'datalist_item_expense' => $datalist_item_expense,
            'id_spk_budgeting' => $get_kasbon_header->id_spk_budgeting,
            'id_header' => $id_header,
            'id_spk_penawaran' => $get_kasbon_header->id_spk_penawaran,
            'id_penawaran' => $get_kasbon_header->id_penawaran,
            'tipe' => $get_kasbon_header->tipe
        ];

        $this->template->set($data);
        $this->template->render('view_expense_subcont');
    }


    // End Page Function    

    // Get Data Function    

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');


        $this->db->select('a.id, a.id_header, a.total_expense_report, a.selisih, a.tipe, b.id_spk_penawaran, b.deskripsi, c.nm_customer, c.nm_project_leader, c.nm_sales, d.nm_paket as nm_project');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->join('kons_tr_kasbon_project_header b', 'b.id = a.id_header', 'left');
        $this->db->join('kons_tr_spk_penawaran c', 'c.id_spk_penawaran = b.id_spk_penawaran', 'left');
        $this->db->join('kons_master_konsultasi_header d', 'd.id_konsultasi_h = c.id_project', 'left');
        $this->db->where('a.sts', null);
        $this->db->where('a.sts_req', 1);

        if (!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('b.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.id', $search['value'], 'both');
            $this->db->or_like('a.id_header', $search['value'], 'both');
            $this->db->or_like('c.nm_customer', $search['value'], 'both');
            $this->db->or_like('c.nm_sales', $search['value'], 'both');
            $this->db->or_like('c.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('d.nm_paket', $search['value'], 'both');
            $this->db->or_like('b.deskripsi', $search['value'], 'both');
            $this->db->group_end();
        }

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->order_by('a.id', 'desc');
        $this->db->limit($length, $start);
        $get_data = $this->db->get()->result();

        $no = (0 + $start);
        $hasil = [];

        foreach ($get_data as $item) {
            $no++;

            if ($item->tipe == '1') {
                $tipe = '<span class="badge bg-red">Pengajuan Subcont</span>';
            }
            if ($item->tipe == '2') {
                $tipe = '<span class="badge bg-yellow">Pengajuan Akomodasi</span>';
            }
            if ($item->tipe == '3') {
                $tipe = '<span class="badge bg-blue">Pengajuan Others</span>';
            }
            if ($item->tipe == '4') {
                $tipe = '<span class="badge bg-cyan">Pengajuan lab</span>';
            }
            if ($item->tipe == '5') {
                $tipe = '<span class="badge bg-green">Pengajuan Subcont Tenaga Ahli</span>';
            }
            if ($item->tipe == '6') {
                $tipe = '<span class="badge bg-purple">Pengajuan Perusahaan</span>';
            }

            $action = '<a href="' . base_url('approval_expense_report_project/req_app/' . str_replace('/', '|', $item->id_header)) . '" class="btn btn-sm btn-primary"><i class="fa fa-arrow-up"></i></a>';

            $hasil[] = [
                'no' => $no,
                'nomor_spk' => $item->id_spk_penawaran,
                'nomor_expense' => $item->id,
                'nomor_pengajuan' => $item->id_header,
                'customer' => $item->nm_customer,
                'sales' => $item->nm_sales,
                'project_leader' => $item->nm_project_leader,
                'package' => $item->nm_project,
                'keterangan' => $item->deskripsi,
                'tipe' => $tipe,
                'nominal' => number_format($item->total_expense_report),
                'action' => $action
            ];
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_all,
            'data' => $hasil
        ];

        echo json_encode($response);
    }

    public function get_data_kasbon_subcont()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 1);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_subcont = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $get_kasbon_subcont_all = $this->db->get();

        $nilai_kasbon_on_proses = 0;
        foreach ($get_kasbon_subcont_all->result() as $item) {
            if ($item->sts !== '1') {
                $nilai_kasbon_on_proses += $item->grand_total;
            }
        }

        $hasil = [];

        $no = 1;
        foreach ($get_kasbon_subcont->result() as $item) {
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
            }

            $option = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_aktifitas' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->tgl)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'action' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_kasbon_subcont_all->num_rows(),
            'recordsFiltered' => $get_kasbon_subcont_all->num_rows(),
            'data' => $hasil
        ]);
    }

    public function get_data_kasbon_akomodasi()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $id_spk_budgeting = $this->input->post('id_spk_budgeting');
        $view = $this->input->post('view');

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 2);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_akomodasi = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 2);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
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
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
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

            if ($check_expense->num_rows() > 0) {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                        <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                            <a href="javascript:void(0);" class="btn btn-sm btn-primary req_approval" style="color: #000000" data-id="' . $item->id . '" title="Request Approval">
                                <div class="col-12 dropdown-item">
                                <b>
                                    <i class="fa fa-check"></i>
                                </b>
                                </div>
                            </a>
                            <span style="font-weight: 500"> Req. Approval </span>
                        </div>
                    ';
                }

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/edit_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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
            } else {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/add_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-success" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-plus"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Add Expense </span>
                    </div>
                ';
            }


            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont" style="color: #000000" data-id="' . $item->id . '">
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
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->tgl)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'option' => $option,
                'action' => $action
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

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 3);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_others = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 3);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
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
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
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

            if ($check_expense->num_rows() > 0) {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                        <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                            <a href="javascript:void(0);" class="btn btn-sm btn-primary req_approval" style="color: #000000" data-id="' . $item->id . '" title="Request Approval">
                                <div class="col-12 dropdown-item">
                                <b>
                                    <i class="fa fa-check"></i>
                                </b>
                                </div>
                            </a>
                            <span style="font-weight: 500"> Req. Approval </span>
                        </div>
                    ';
                }

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/edit_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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
            } else {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/add_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-success" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-plus"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Add Expense </span>
                    </div>
                ';
            }


            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont" style="color: #000000" data-id="' . $item->id . '">
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
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->created_date)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'option' => $option,
                'action' => $action
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

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 4);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_lab = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 4);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
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
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
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

            if ($check_expense->num_rows() > 0) {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                        <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                            <a href="javascript:void(0);" class="btn btn-sm btn-primary req_approval" style="color: #000000" data-id="' . $item->id . '" title="Request Approval">
                                <div class="col-12 dropdown-item">
                                <b>
                                    <i class="fa fa-check"></i>
                                </b>
                                </div>
                            </a>
                            <span style="font-weight: 500"> Req. Approval </span>
                        </div>
                    ';
                }

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/edit_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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
            } else {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/add_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-success" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-plus"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Add Expense </span>
                    </div>
                ';
            }


            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont" style="color: #000000" data-id="' . $item->id . '">
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
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->created_date)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'option' => $option,
                'action' => $action
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

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 5);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_subcont_tenaga_ahli = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 5);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
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
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
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

            if ($check_expense->num_rows() > 0) {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                        <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                            <a href="javascript:void(0);" class="btn btn-sm btn-primary req_approval" style="color: #000000" data-id="' . $item->id . '" title="Request Approval">
                                <div class="col-12 dropdown-item">
                                <b>
                                    <i class="fa fa-check"></i>
                                </b>
                                </div>
                            </a>
                            <span style="font-weight: 500"> Req. Approval </span>
                        </div>
                    ';
                }

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/edit_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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
            } else {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/add_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-success" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-plus"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Add Expense </span>
                    </div>
                ';
            }


            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont" style="color: #000000" data-id="' . $item->id . '">
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
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->created_date)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'option' => $option,
                'action' => $action
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

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('kons_tr_expense_report_project_header b', 'b.id_header = a.id', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 6);
        $this->db->where('b.sts_req', 1);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.created_by', 'desc');
        $this->db->limit($length, $start);
        $get_kasbon_subcont_perusahaan = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        $this->db->where('a.tipe', 6);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->or_like('a.deskripsi', $search['value'], 'both');
            $this->db->or_like('a.tgl', $search['value'], 'both');
            $this->db->or_like('a.grand_total', $search['value'], 'both');
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
            $check_expense = $this->db->select('a.*')->from('kons_tr_expense_report_project_header a')->where('a.id_header', $item->id)->get();

            $sts = '<button type="button" class="btn btn-sm btn-success">New</button>';
            if ($check_expense->num_rows() > 0) {
                if ($check_expense->row()->sts_req == 1) {
                    $sts = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
                } else {
                    if ($check_expense->row()->sts == 1) {
                        $sts = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
                    }
                    if ($check_expense->row()->sts == 2) {
                        $sts = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
                    }
                }
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

            if ($check_expense->num_rows() > 0) {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-eye"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> View </span>
                    </div>
                ';

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                        <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                            <a href="javascript:void(0);" class="btn btn-sm btn-primary req_approval" style="color: #000000" data-id="' . $item->id . '" title="Request Approval">
                                <div class="col-12 dropdown-item">
                                <b>
                                    <i class="fa fa-check"></i>
                                </b>
                                </div>
                            </a>
                            <span style="font-weight: 500"> Req. Approval </span>
                        </div>
                    ';
                }

                if ($check_expense->row()->sts_req == '0' && $check_expense->row()->sts == '0') {
                    $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/edit_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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
            } else {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="' . base_url('expense_report_project/add_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-success" style="color: #000000">
                            <div class="col-12 dropdown-item">
                            <b>
                                <i class="fa fa-plus"></i>
                            </b>
                            </div>
                        </a>
                        <span style="font-weight: 500"> Add Expense </span>
                    </div>
                ';
            }


            if ($item->sts !== '1' && $item->sts_req !== '1') {
                $option .= '
                    <div class="col-12" style="margin-left: 0.5rem; padding-top: 0.5rem;">
                        <a href="javascript:void(0);" class="btn btn-sm btn-danger del_kasbon_subcont" style="color: #000000" data-id="' . $item->id . '">
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
                        <a href="' . base_url('kasbon_project/edit_kasbon_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-warning" style="color: #000000">
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

            $action = '<a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item->id))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>';

            $hasil[] = [
                'no' => $no,
                'req_number' => $item->id,
                'nm_biaya' => $item->deskripsi,
                'date' => date('d F Y', strtotime($item->created_date)),
                'total' => number_format($item->grand_total, 2),
                'status' => $sts,
                'option' => $option,
                'action' => $action
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

    // End Data Function

    // Update Data Function

    // public function approve_expense_report()
    // {
    //     $id_spk_budgeting = $this->input->post('id_spk_budgeting');

    //     $this->db->select('a.*, b.deskripsi');
    //     $this->db->from('kons_tr_expense_report_project_header a');
    //     $this->db->join('kons_tr_kasbon_project_header b', 'b.id = a.id_header', 'left');
    //     $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
    //     $this->db->where('a.sts_req', 1);
    //     $get_expense_report_req_app = $this->db->get()->result();

    //     $this->db->select('a.*');
    //     $this->db->from('kons_tr_expense_report_project_detail a');
    //     $this->db->join('kons_tr_expense_report_project_header b', 'b.id = a.id_header_expense');
    //     $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
    //     $this->db->where('b.sts_req', 1);
    //     $get_expense_report_req_app_detail = $this->db->get()->result();

    //     // print_r($this->db->last_query());
    //     // exit;

    //     $get_user = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();

    //     $this->db->trans_begin();

    //     $no_doc = $this->Approval_expense_report_project_model->GetAutoGenerate('format_expense');

    //     $arr_insert_expense = [];

    //     foreach ($get_expense_report_req_app as $item) {

    //         $this->db->select('a.*');
    //         $this->db->from('kons_tr_kasbon_project_header a');
    //         $this->db->where('a.id', $item->id_header);
    //         $get_header_kasbon = $this->db->get()->row();

    //         if ($item->selisih < 0) {
    //             $arr_insert_expense[] = [
    //                 'no_doc' => $no_doc,
    //                 'tgl_doc' => date('Y-m-d'),
    //                 'nama' => $get_user->nm_lengkap,
    //                 'approval' => $get_user->nm_lengkap,
    //                 'status' => 1,
    //                 'created_by' => $this->auth->user_id(),
    //                 'created_on' => date('Y-m-d H:i:s'),
    //                 'jumlah' => ($item->selisih * -1),
    //                 'informasi' => $get_header_kasbon->deskripsi,
    //                 'bank_id' => $get_header_kasbon->bank,
    //                 'accnumber' => $get_header_kasbon->bank_number,
    //                 'accname' => $get_header_kasbon->bank_account,
    //                 'id_kasbon' => $item->id_header,
    //                 'project_consultant' => 1,
    //                 'no_expense_consultant' => $item->id
    //             ];
    //         }
    //     }

    //     $arr_insert_expense_detail = [];

    //     foreach ($get_expense_report_req_app_detail as $item) {

    //         if ($item->tipe == '1') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expense_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_subcont b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_aktifitas c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if ($item->tipe == '2') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expens e_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_akomodasi b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_akomodasi c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if ($item->tipe == '3') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expense_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_others b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_akomodasi c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if ($item->tipe == '4') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expense_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_lab b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_lab c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if ($item->tipe == '5') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expense_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_subcont_tenaga_ahli b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_subcont_tenaga_ahli c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if ($item->tipe == '6') {
    //             $this->db->select('a.qty_expense, a.nominal_expense, (a.qty_expense * a.nominal_expense) as total_expense, b.qty_pengajuan as qty_kasbon, b.nominal_pengajuan as nominal_kasbon, b.total_pengajuan as total_kasbon');
    //             $this->db->from('kons_tr_expense_report_project_detail a');
    //             $this->db->join('kons_tr_kasbon_project_subcont_perusahaan b', 'b.id_header = a.id_header_kasbon');
    //             $this->db->join('kons_tr_spk_budgeting_subcont_perusahaan c', 'c.id = a.id_detail_kasbon AND c.id_spk_budgeting = a.id_spk_budgeting');
    //             $this->db->where('a.id', $item->id);
    //             $get_selisih_expense = $this->db->get()->row();
    //         }

    //         if (!empty($get_selisih_expense)) {

    //             $selisih_expense_kasbon = ($get_selisih_expense->total_kasbon - $get_selisih_expense->total_expense);
    //             if ($selisih_expense_kasbon < 0) {
    //                 $selisih_expense_kasbon = ($selisih_expense_kasbon * -1);
    //                 $arr_insert_expense_detail[] = [
    //                     'tanggal' => date('Y-m-d'),
    //                     'no_doc' => $no_doc,
    //                     'deskripsi' => $item->keterangan,
    //                     'qty' => $item->qty_expense,
    //                     'harga' => $item->nominal_expense,
    //                     'total_harga' => $selisih_expense_kasbon,
    //                     'keterangan' => $item->keterangan,
    //                     'status' => 2,
    //                     'expense' => $selisih_expense_kasbon,
    //                     'created_by' => $get_user->nm_lengkap,
    //                     'created_on' => date('Y-m-d H:i:s')
    //                 ];
    //             }
    //         }
    //     }

    //     // print_r($arr_insert_expense_detail);
    //     // exit;

    //     if (!empty($arr_insert_expense)) {
    //         $insert_sendigs_expense = $this->otherdb->insert_batch('tr_expense', $arr_insert_expense);

    //         $error = $this->db->error();
    //         if ($error['code'] != 0) {
    //             $this->db->trans_rollback();
    //             print_r($this->db->last_query());
    //             exit;
    //         }
    //     }

    //     if (!empty($arr_insert_expense_detail)) {
    //         $insert_sendigs_expense_detail = $this->otherdb->insert_batch('tr_expense_detail', $arr_insert_expense_detail);

    //         $error = $this->db->error();
    //         if ($error['code'] != 0) {
    //             $this->db->trans_rollback();
    //             print_r($this->db->last_query());
    //             exit;
    //         }
    //     }

    //     foreach ($get_expense_report_req_app as $item) {
    //         $update_status_header = $this->db->update('kons_tr_expense_report_project_header', ['sts' => 1, 'sts_req' => null, 'reject_reason' => ''], ['id' => $item->id]);


    //         if (!$update_status_header) {
    //             $this->db->trans_rollback();
    //             print_r($this->db->error($update_status_header));
    //             exit;
    //         }
    //         // $get_user = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();

    //         // $this->db->insert('request_payment', array(
    //         //     'no_doc' => $item->id,
    //         //     'nama' => $get_user->nm_lengkap,
    //         //     'tgl_doc' => date('Y-m-d', strtotime($item->created_date)),
    //         //     'keperluan' => $item->deskripsi,
    //         //     'tipe' => 'expense',
    //         //     'jumlah' => $item->selisih,
    //         //     'created_by' => $get_user->username,
    //         //     'created_on' => date('Y-m-d H:i:s'),
    //         //     'ids' => $item->id,
    //         //     'currency' => 'IDR'
    //         // ));
    //     }

    //     if ($this->db->trans_status() === false) {
    //         $this->db->trans_rollback();

    //         $valid = 0;
    //         $pesan = 'Please try again later !';
    //     } else {
    //         $this->db->trans_commit();

    //         $valid = 1;
    //         $pesan = 'Data has been approved !';
    //     }

    //     echo json_encode([
    //         'status' => $valid,
    //         'pesan' => $pesan
    //     ]);
    // }

    public function approve_expense_report()
    {
        $post = $this->input->post();

        if (empty($post['jurnal']) || !is_array($post['jurnal'])) {
            echo json_encode([
                'status' => 0,
                'msg' => 'Journal data is required to approve Expense Report !'
            ]);
            return;
        }

        $id_header = $post['id_header'];

        $get_header = $this->db->get_where('kons_tr_expense_report_project_header', ['id_header' => $id_header])->row();
        $get_user = $this->db->get_where('users', ['id_user' => $get_header->created_by])->row();
        $get_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $id_header])->row();

        $this->db->trans_begin();

        $this->db->update('kons_tr_expense_report_project_header', [
            'sts' => 1,
            'sts_req' => null,
            'reject_reason' => '',
            'id_bank' => $post['bank'],
            'approved_by' => $this->auth->user_id(),
            'approved_date' => date('Y-m-d H:i:s')
        ], ['id_header' => $id_header]);

        $arr_jurnal = [];

        if (isset($post['jurnal'])) {
            $no_jurnal = 0;
            foreach ($post['jurnal'] as $item_jurnal) {
                $no_jurnal++;

                $no_surat_jurnal = $this->Approval_expense_report_project_model->generate_id_invoice_jurnal($no_jurnal);

                $arr_jurnal[] = [
                    'no_jurnal' => $no_surat_jurnal,
                    'tgl_jurnal' => $item_jurnal['tgl_jurnal'],
                    'coa' => $item_jurnal['coa'],
                    'id_company' => $item_jurnal['id_company'],
                    'nm_company' => $item_jurnal['nm_company'],
                    'nm_coa' => $item_jurnal['nm_coa'],
                    'debit' => $item_jurnal['debit'],
                    'kredit' => $item_jurnal['kredit'],
                    'keterangan' => $item_jurnal['nm_coa'] . ' - ' . $get_header->id,
                    'sts' => 0,
                    'no_transaksi' => $get_header->id,
                    'jenis_transaksi' => 'Expense Report Consultant',
                    'created_by' => $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            }
        }

        $arr_tabungan_pph21 = [];

        if (isset($post['jurnal_pph'])) {
            $no_tabungan = 0;


            foreach ($post['jurnal_pph'] as $item) :
                $no_tabungan++;

                $no_surat_tabungan = $this->Approval_expense_report_project_model->generate_id_invoice_jurnal_pph21($no_tabungan);

                $arr_tabungan_pph21[] = [
                    'no_tabungan' => $no_surat_tabungan,
                    'tgl_tabungan' => $item['tanggal_jurnal'],
                    'coa' => $item['coa'],
                    'id_company' => $item['id_company'],
                    'nm_company' => $item['nm_company'],
                    'nm_coa' => $item['nm_coa'],
                    'debit' => $item['debit'],
                    'kredit' => $item['kredit'],
                    'keterangan' => $item['keterangan'] . ' - ' . $get_header->id,
                    'sts' => 0,
                    'no_transaksi' => $get_header->id,
                    'jenis_transaksi' => 'Expense Report Project',
                    'created_by' => $this->auth->user_id(),
                    'created_date' => date('Y-m-d H:i:s')
                ];
            endforeach;
        }

        // print_r($arr_jurnal);
        // exit;

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $insert_jurnal = $this->db->insert_batch(DBSF . '.tr_jurnal', $arr_jurnal);
        $this->db->db_debug = $db_debug;

        if ($insert_jurnal === false || $insert_jurnal !== count($arr_jurnal) || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            echo json_encode([
                'status' => 0,
                'msg' => 'Failed to save Expense Report journal !'
            ]);
            return;
        }

        // if (!empty($arr_tabungan_pph21)) {
        //     $insert_tabungan_pph21 = $this->db->insert_batch(DBSF . '.tr_tabungan_pph21', $arr_tabungan_pph21);
        // }

        if ($get_header->selisih < 0) {
            $insert_request_payment = $this->db->insert('request_payment', array(
                'no_doc' => $get_header->id,
                'nama' => $get_user->nm_lengkap,
                'tgl_doc' => date('Y-m-d', strtotime($get_header->created_date)),
                'keperluan' => $get_kasbon->deskripsi,
                'tipe' => 'expense',
                'jumlah' => ($get_header->selisih * -1),
                'created_by' => $get_user->username,
                'created_on' => date('Y-m-d H:i:s'),
                'ids' => $get_header->id,
                'currency' => 'IDR'
            ));
        }



        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $response = [
                'status' => 0,
                'msg' => 'Please try again later !'
            ];
        } else {
            $this->db->trans_commit();

            $response = [
                'status' => 1,
                'msg' => 'Expense data has been approved !'
            ];
        }

        echo json_encode($response);
    }

    public function reject_expense_report()
    {
        $id_header = $this->input->post('id_header');
        $reject_reason = $this->input->post('reject_reason');

        $this->db->trans_begin();

        $this->db->update('kons_tr_expense_report_project_header', ['sts_req' => null, 'reject_reason' => $reject_reason], ['id_header' => $id_header]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $response = [
                'status' => 0,
                'msg' => 'Please try again later !'
            ];
        } else {
            $this->db->trans_commit();

            $response = [
                'status' => 1,
                'msg' => 'Expense data has been rejected !'
            ];
        }

        echo json_encode($response);
    }

    // public function reject_expense_report()
    // {
    //     $id_spk_budgeting = $this->input->post('id_spk_budgeting');
    //     $reject_reason = $this->input->post('reject_reason');

    //     $this->db->select('a.*');
    //     $this->db->from('kons_tr_expense_report_project_header a');
    //     $this->db->join('kons_tr_kasbon_project_header b', 'b.id = a.id_header', 'left');
    //     $this->db->where('b.id_spk_budgeting', $id_spk_budgeting);
    //     $this->db->where('a.sts_req', 1);
    //     $get_expense_report_req_app = $this->db->get()->result();

    //     $this->db->trans_begin();

    //     foreach ($get_expense_report_req_app as $item) {
    //         $this->db->update('kons_tr_expense_report_project_header', ['sts' => null, 'sts_req' => null, 'reject_reason' => $reject_reason], ['id' => $item->id]);
    //     }

    //     if ($this->db->trans_status() === false) {
    //         $this->db->trans_rollback();

    //         $valid = 0;
    //         $pesan = 'Please try again later !';
    //     } else {
    //         $this->db->trans_commit();

    //         $valid = 1;
    //         $pesan = 'Data has been rejected !';
    //     }

    //     echo json_encode([
    //         'status' => $valid,
    //         'pesan' => $pesan
    //     ]);
    // }

    public function set_jurnal_expense()
    {
        $post = $this->input->post();

        $get_expense = $this->db->get_where('kons_tr_expense_report_project_header', ['id' => $post['id_expense']])->row();
        $get_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $get_expense->id_header])->row();
        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_kasbon->id_penawaran])->row();
        $get_company = $this->db->get_where('kons_tr_company', ['id' => $get_penawaran->company])->row();

        $id_company = (isset($get_company)) ? $get_company->id : '';
        $nm_company = (isset($get_company)) ? $get_company->nm_company : '';

        $total_expense = (isset($get_expense) && $get_expense->total_expense_report > 0) ? $get_expense->total_expense_report : 0;
        $total_kasbon = (isset($get_expense) && $get_expense->total_kasbon > 0) ? $get_expense->total_kasbon : 0;

        if ($get_expense->selisih == '0') {
            $arr_coa_jurnal = ['5101-01-03', '1103-01-14'];

            $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
            $this->gl->from('coa_master a');
            $this->gl->where_in('a.no_perkiraan', $arr_coa_jurnal);
            $get_coa = $this->gl->get()->result();

            $hasil_jurnal = '';
            $no_jurnal = 0;

            $ttl_debit = 0;
            $ttl_kredit = 0;
            foreach ($get_coa as $item_coa) {


                $debit = 0;
                $kredit = 0;

                if ($item_coa->no_perkiraan == '5101-01-03') {
                    $get_expense_header = $this->db->get_where('kons_tr_expense_report_project_header', ['id' => $post['id_expense']])->row();
                    $get_expense_detail = $this->db->get_where('kons_tr_expense_report_project_detail', ['id_header_expense' => $post['id_expense']])->result();

                    foreach ($get_expense_detail as $item_expense) {
                        // if ($item_expense->tipe == '1') {
                        $no_jurnal++;

                        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $item_expense->id_header_kasbon])->row();
                        $tipe = $get_kasbon_header->tipe;

                        $keterangan = '';
                        $no_coa = '';
                        $nm_coa = '';

                        if ($tipe == '2') {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_akomodasi = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_akomodasi a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_akomodasi', $get_budgeting_akomodasi->id_akomodasi);
                            $get_akomodasi = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_akomodasi->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_akomodasi)) ? $get_akomodasi->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == '3') {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_others a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_others = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_others a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_others', $get_budgeting_others->id_others);
                            $get_others = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_others->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_others)) ? $get_others->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == '4') {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_lab a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_lab = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_lab a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_lab', $get_budgeting_lab->id_lab);
                            $get_lab = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_lab a');
                            $this->db->where('a.id', $get_lab->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_lab)) ? $get_lab->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == '5') {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_tenaga_ahli = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_tenaga_ahli a');
                            $this->db->where('a.id', $get_subcont_tenaga_ahli->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_tenaga_ahli)) ? $get_subcont_tenaga_ahli->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == '6') {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_perusahaan = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_subcont_perusahaan a');
                            $this->db->where('a.id', $get_subcont_perusahaan->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_perusahaan)) ? $get_subcont_perusahaan->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        // if ($get_expense_header->tipe == 1) {
                        //     $get_detail = $this->db->get_where('kons_tr_spk_budgeting_akomodasi', ['id' => $item_expense->id_detail_kasbon])->row();
                        //     $get_master_biaya = $this->db->get_where('kons_master_biaya', ['id' => $get_detail->id_item])->row();

                        //     $keterangan = (!empty($get_master_biaya)) ? $get_master_biaya->nm_biaya : '';
                        //     $no_coa = (!empty($get_master_biaya)) ? $get_master_biaya->no_coa : '5101-01-03';
                        //     $nm_coa = (!empty($get_master_biaya)) ? $get_master_biaya->nm_coa : '';
                        // }


                        $debit = ($item_expense->qty_expense * $item_expense->nominal_expense);
                        if ($item_expense->qty_expense < 1) {
                            $debit = $item_expense->nominal_expense;
                        }

                        $hasil_jurnal .= '<tr>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= date('d F Y');
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $no_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $no_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_company;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $nm_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $keterangan;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $keterangan . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($debit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($kredit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '</tr>';

                        $ttl_debit += $debit;
                        $ttl_kredit += $kredit;
                        // }
                    }
                } else {
                    $no_jurnal++;

                    if ($item_coa->no_perkiraan == '5101-01-03') {
                        $debit = $total_expense;
                    }
                    if ($item_coa->no_perkiraan == '1103-01-14') {
                        $kredit = $total_kasbon;
                    }

                    $hasil_jurnal .= '<tr>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= date('d F Y');
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->no_perkiraan;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $item_coa->no_perkiraan . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $nm_company;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($debit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($kredit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '</tr>';

                    $ttl_debit += $debit;
                    $ttl_kredit += $kredit;
                }
            }
        }
        if ($get_expense->selisih > 0) {
            $arr_coa_jurnal = ['5101-01-03', '1103-01-04'];

            $coa_bank = '';
            if ($post['id_bank'] !== '') {
                $get_bank = $this->otherdb->get_where('ms_bank', ['id' => $post['id_bank']])->row();

                $coa_bank = $get_bank->coa_bank;
                array_push($arr_coa_jurnal, $get_bank->coa_bank);
            }

            $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
            $this->gl->from('coa_master a');
            $this->gl->where_in('a.no_perkiraan', $arr_coa_jurnal);
            $get_coa = $this->gl->get()->result();

            $hasil_jurnal = '';
            $no_jurnal = 0;

            $ttl_debit = 0;
            $ttl_kredit = 0;
            foreach ($get_coa as $item_coa) {
                $no_jurnal++;

                $debit = 0;
                $kredit = 0;

                if ($item_coa->no_perkiraan == '5101-01-03') {
                    $get_expense_header = $this->db->get_where('kons_tr_expense_report_project_header', ['id' => $post['id_expense']])->row();
                    $get_expense_detail = $this->db->get_where('kons_tr_expense_report_project_detail', ['id_header_expense' => $post['id_expense']])->result();

                    foreach ($get_expense_detail as $item_expense) {
                        // if ($item_expense->tipe == '1') {
                        $no_jurnal++;

                        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $item_expense->id_header_kasbon])->row();
                        $tipe = $get_kasbon_header->tipe;

                        $keterangan = '';
                        $no_coa = '';
                        $nm_coa = '';

                        if ($tipe == 2) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_akomodasi = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_akomodasi a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_akomodasi', $get_budgeting_akomodasi->id_akomodasi);
                            $get_akomodasi = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_akomodasi->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_akomodasi)) ? $get_akomodasi->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 3) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_others a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_others = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_others a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_others', $get_budgeting_others->id_others);
                            $get_others = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_others->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_others)) ? $get_others->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 4) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_lab a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_lab = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_lab a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_lab', $get_budgeting_lab->id_lab);
                            $get_lab = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_lab a');
                            $this->db->where('a.id', $get_lab->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_lab)) ? $get_lab->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 5) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_tenaga_ahli = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_tenaga_ahli a');
                            $this->db->where('a.id', $get_subcont_tenaga_ahli->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_tenaga_ahli)) ? $get_subcont_tenaga_ahli->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 6) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_perusahaan = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_subcont_perusahaan a');
                            $this->db->where('a.id', $get_subcont_perusahaan->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_perusahaan)) ? $get_subcont_perusahaan->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        // if ($get_expense_header->tipe == 1) {
                        //     $get_detail = $this->db->get_where('kons_tr_spk_budgeting_akomodasi', ['id' => $item_expense->id_detail_kasbon])->row();
                        //     $get_master_biaya = $this->db->get_where('kons_master_biaya', ['id' => $get_detail->id_item])->row();

                        //     $keterangan = (!empty($get_master_biaya)) ? $get_master_biaya->nm_biaya : '';
                        //     $no_coa = (!empty($get_master_biaya)) ? $get_master_biaya->no_coa : '5101-01-03';
                        //     $nm_coa = (!empty($get_master_biaya)) ? $get_master_biaya->nm_coa : '';
                        // }


                        $debit = ($item_expense->qty_expense * $item_expense->nominal_expense);
                        if ($item_expense->qty_expense < 1) {
                            $debit = $item_expense->nominal_expense;
                        }

                        $hasil_jurnal .= '<tr>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= date('d F Y');
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $no_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $no_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_company;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $nm_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $keterangan;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $keterangan . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($debit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($kredit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '</tr>';

                        $ttl_debit += $debit;
                        $ttl_kredit += $kredit;
                        // }
                    }
                } else {

                    if ($item_coa->no_perkiraan == '5101-01-03') {
                        $debit = $total_expense;
                    }
                    if ($item_coa->no_perkiraan == '1030-20-4') {
                        $kredit = $total_kasbon;
                    }
                    if ($coa_bank !== '' && $item_coa->no_perkiraan == $coa_bank) {
                        $debit = $get_expense->selisih;
                    }


                    $hasil_jurnal .= '<tr>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= date('d F Y');
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->no_perkiraan;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $item_coa->no_perkiraan . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $nm_company;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($debit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($kredit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '</tr>';

                    $ttl_debit += $debit;
                    $ttl_kredit += $kredit;
                }
            }
        }
        if ($get_expense->selisih < 0) {
            $arr_coa_jurnal = ['5101-01-03', '1103-01-14', '9999-99-99'];

            $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
            $this->gl->from('coa_master a');
            $this->gl->where_in('a.no_perkiraan', $arr_coa_jurnal);
            $get_coa = $this->gl->get()->result();

            $hasil_jurnal = '';
            $no_jurnal = 0;

            $ttl_debit = 0;
            $ttl_kredit = 0;
            foreach ($get_coa as $item_coa) {
                $no_jurnal++;

                $debit = 0;
                $kredit = 0;

                if ($item_coa->no_perkiraan == '5101-01-03') {
                    $get_expense_header = $this->db->get_where('kons_tr_expense_report_project_header', ['id' => $post['id_expense']])->row();
                    $get_expense_detail = $this->db->get_where('kons_tr_expense_report_project_detail', ['id_header_expense' => $post['id_expense']])->result();

                    foreach ($get_expense_detail as $item_expense) {
                        // if ($item_expense->tipe == '1') {
                        $no_jurnal++;

                        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $item_expense->id_header_kasbon])->row();
                        $tipe = $get_kasbon_header->tipe;

                        $keterangan = '';
                        $no_coa = '';
                        $nm_coa = '';

                        if ($tipe == 2) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_akomodasi a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_akomodasi = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_akomodasi a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_akomodasi', $get_budgeting_akomodasi->id_akomodasi);
                            $get_akomodasi = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_akomodasi->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_akomodasi)) ? $get_akomodasi->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 3) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_others a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_others = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_others a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_others', $get_budgeting_others->id_others);
                            $get_others = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_biaya a');
                            $this->db->where('a.id', $get_others->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_others)) ? $get_others->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 4) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_spk_budgeting_lab a');
                            $this->db->where('a.id_spk_budgeting', $get_kasbon_header->id_spk_budgeting);
                            $this->db->where('a.id', $item_expense->id_detail_kasbon);
                            $get_budgeting_lab = $this->db->get()->row();

                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_lab a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_lab', $get_budgeting_lab->id_lab);
                            $get_lab = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_lab a');
                            $this->db->where('a.id', $get_lab->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_lab)) ? $get_lab->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 5) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_tenaga_ahli a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_tenaga_ahli = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_tenaga_ahli a');
                            $this->db->where('a.id', $get_subcont_tenaga_ahli->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_tenaga_ahli)) ? $get_subcont_tenaga_ahli->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        if ($tipe == 6) {
                            $this->db->select('a.*');
                            $this->db->from('kons_tr_kasbon_project_subcont_perusahaan a');
                            $this->db->where('a.id_header', $item_expense->id_header_kasbon);
                            $this->db->where('a.id_subcont', $item_expense->id_detail_kasbon);
                            $get_subcont_perusahaan = $this->db->get()->row();

                            $this->db->select('a.no_coa, a.nm_coa');
                            $this->db->from('kons_master_subcont_perusahaan a');
                            $this->db->where('a.id', $get_subcont_perusahaan->id_item);
                            $get_coa_biaya = $this->db->get()->row();

                            $keterangan = (!empty($get_subcont_perusahaan)) ? $get_subcont_perusahaan->nm_item : '';
                            $no_coa = (!empty($get_coa_biaya->no_coa)) ? $get_coa_biaya->no_coa : '5101-01-03';
                            $nm_coa = (!empty($get_coa_biaya->nm_coa)) ? $get_coa_biaya->nm_coa : 'Biaya Pengeluaran Lainnya';
                        }

                        // if ($get_expense_header->tipe == 1) {
                        //     $get_detail = $this->db->get_where('kons_tr_spk_budgeting_akomodasi', ['id' => $item_expense->id_detail_kasbon])->row();
                        //     $get_master_biaya = $this->db->get_where('kons_master_biaya', ['id' => $get_detail->id_item])->row();

                        //     $keterangan = (!empty($get_master_biaya)) ? $get_master_biaya->nm_biaya : '';
                        //     $no_coa = (!empty($get_master_biaya)) ? $get_master_biaya->no_coa : '5101-01-03';
                        //     $nm_coa = (!empty($get_master_biaya)) ? $get_master_biaya->nm_coa : '';
                        // }


                        $debit = ($item_expense->qty_expense * $item_expense->nominal_expense);
                        if ($item_expense->qty_expense < 1) {
                            $debit = $item_expense->nominal_expense;
                        }

                        $hasil_jurnal .= '<tr>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= date('d F Y');
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $no_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $no_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_company;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $nm_coa;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $nm_coa . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-center">';
                        $hasil_jurnal .= $keterangan;
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $keterangan . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($debit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '<td class="text-right">';
                        $hasil_jurnal .= number_format($kredit);
                        $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                        $hasil_jurnal .= '</td>';

                        $hasil_jurnal .= '</tr>';

                        $ttl_debit += $debit;
                        $ttl_kredit += $kredit;
                        // }
                    }
                } else {
                    if ($item_coa->no_perkiraan == '5101-01-03') {
                        $debit = $total_expense;
                    }
                    if ($item_coa->no_perkiraan == '1103-01-14') {
                        $kredit = $total_kasbon;
                    }
                    if ($item_coa->no_perkiraan == '9999-99-99') {
                        $kredit = ($get_expense->selisih * -1);
                    }


                    $hasil_jurnal .= '<tr>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= date('d F Y');
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][tgl_jurnal]" value="' . date('Y-m-d') . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->no_perkiraan;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][coa]" value="' . $item_coa->no_perkiraan . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $nm_company;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][id_company]" value="' . $id_company . '">';
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_company]" value="' . $nm_company . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][nm_coa]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-center">';
                    $hasil_jurnal .= $item_coa->nm_coa;
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][keterangan]" value="' . $item_coa->nm_coa . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($debit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][debit]" value="' . $debit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '<td class="text-right">';
                    $hasil_jurnal .= number_format($kredit);
                    $hasil_jurnal .= '<input type="hidden" name="jurnal[' . $no_jurnal . '][kredit]" value="' . $kredit . '">';
                    $hasil_jurnal .= '</td>';

                    $hasil_jurnal .= '</tr>';

                    $ttl_debit += $debit;
                    $ttl_kredit += $kredit;
                }
            }
        }

        $response = [
            'hasil' => $hasil_jurnal,
            'ttl_debit' => $ttl_debit,
            'ttl_kredit' => $ttl_kredit
        ];

        echo json_encode($response);
    }

    // End Update Data Function
}
