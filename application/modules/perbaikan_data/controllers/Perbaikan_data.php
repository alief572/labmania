<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Perbaikan_data extends Admin_Controller
{

    protected $otherdb;
    public function __construct()
    {
        parent::__construct();
        $this->template->title('Perbaikan Data');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model('Perbaikan_data/Perbaikan_data_model');
        $this->otherdb = $this->load->database('sendigs_finance', TRUE);
        // $this->otherdb =  $this->otherdb->query("SET NAMES 'utf8'");

        date_default_timezone_set('Asia/Bangkok');
    }

    public function fill_kasbon()
    {

        $this->db->trans_begin();

        $arr_insert_lab = [];

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_penawaran_lab b', 'b.id_penawaran = a.id_penawaran');
        $this->db->group_by('a.id_penawaran');
        $get_budgeting = $this->db->get()->result();

        foreach ($get_budgeting as $item) :
            $get_penawaran_lab = $this->db->get_where('kons_tr_penawaran_lab a', array('id_penawaran' => $item->id_penawaran))->result();
            foreach ($get_penawaran_lab as $item_lab) :
                $get_budgeting_lab =  $this->db->get_where('kons_tr_spk_budgeting_lab', array('id_lab' => $item_lab->id))->result();
                if (count($get_budgeting_lab) < 1) {

                    $get_lab = $this->db->get_where('kons_master_lab', array('id' => $item_lab->id_item))->row();

                    $nm_item = (!empty($get_lab)) ? $get_lab->isu_lingkungan : '';

                    $arr_insert_lab[] = [
                        'id_spk_penawaran' => $item->id_spk_penawaran,
                        'id_spk_budgeting' => $item->id_spk_budgeting,
                        'id_penawaran' => $item->id_penawaran,
                        'id_lab' => $item_lab->id,
                        'id_item' => $item_lab->id_item,
                        'nm_item' => $nm_item,
                        'qty_estimasi' => $item_lab->qty,
                        'price_unit_estimasi' => $item_lab->price_unit_budget,
                        'total_estimasi' => $item_lab->total_budget,
                        'qty_final' => $item_lab->qty,
                        'price_unit_final' => $item_lab->price_unit_budget,
                        'total_final' => $item_lab->total_budget,
                        'create_by' => $this->auth->user_id(),
                        'create_date' => date('Y-m-d H:i:s')
                    ];
                }
            endforeach;
        endforeach;

        $arr_update_kasbon_subcont = [];

        $get_kasbon_subcont = $this->db->get('kons_tr_kasbon_project_subcont')->result();
        foreach ($get_kasbon_subcont as $item) {

            $qty_terpakai = 0;
            $this->db->select('a.qty_pengajuan');
            $this->db->from('kons_tr_kasbon_project_subcont a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_aktifitas', $item->id_aktifitas);
            $this->db->where('a.created_date <', $item->created_date);
            $get_qty_terpakai = $this->db->get()->result();

            foreach ($get_qty_terpakai as $item_qty_terpakai) {
                $qty_terpakai += $item_qty_terpakai->qty_pengajuan;
            }

            $qty_overbudget = 0;
            $this->db->select('a.qty_budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_subcont_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_subcont_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_aktifitas', $item->id_aktifitas);
            $this->db->where('b.sts', 1);
            $this->db->where('b.created_date <', $item->created_date);
            $get_overbudget_subcont = $this->db->get()->result();

            foreach ($get_overbudget_subcont as $item_overbudget) {
                $qty_overbudget += $item_overbudget->qty_budget_tambahan;
            }

            $arr_update_kasbon_subcont[] = [
                'id' => $item->id,
                'nominal_terpakai' => $item->nominal_pengajuan,
                'qty_terpakai' => $qty_terpakai,
                'total_terpakai' => ($qty_terpakai * $item->price_unit_estimasi),
                'qty_overbudget' => $qty_overbudget,
                'nominal_overbudget' => $item->price_unit_estimasi,
                'total_overbudget' => ($qty_overbudget * $item->price_unit_estimasi)
            ];
        }

        $arr_update_kasbon_akomodasi = [];

        $get_kasbon_akomodasi = $this->db->get('kons_tr_kasbon_project_akomodasi')->result();
        foreach ($get_kasbon_akomodasi as $item) {

            $qty_terpakai = 0;
            $this->db->select('a.qty_pengajuan');
            $this->db->from('kons_tr_kasbon_project_akomodasi a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_akomodasi', $item->id_akomodasi);
            $this->db->where('a.created_date <', $item->created_date);
            $get_qty_terpakai = $this->db->get()->result();

            foreach ($get_qty_terpakai as $item_qty_terpakai) {
                $qty_terpakai += $item_qty_terpakai->qty_pengajuan;
            }

            $arr_update_kasbon_akomodasi[] = [
                'id' => $item->id,
                'nominal_terpakai' => $item->nominal_pengajuan,
                'qty_overbudget' => $item->qty_budget_tambahan,
                'nominal_overbudget' => $item->price_unit_estimasi,
                'total_overbudget' => $item->budget_tambahan,
                'qty_terpakai' => $qty_terpakai,
                'total_terpakai' => ($qty_terpakai * $item->price_unit_estimasi)
            ];
        }

        $arr_update_kasbon_others = [];

        $get_kasbon_others = $this->db->get('kons_tr_kasbon_project_others')->result();
        foreach ($get_kasbon_others as $item) {

            $qty_terpakai = 0;
            $this->db->select('a.qty_pengajuan');
            $this->db->from('kons_tr_kasbon_project_others a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_others', $item->id_others);
            $this->db->where('a.created_date <', $item->created_date);
            $get_qty_terpakai = $this->db->get()->result();

            foreach ($get_qty_terpakai as $item_qty_terpakai) {
                $qty_terpakai += $item_qty_terpakai->qty_pengajuan;
            }

            $qty_overbudget = 0;
            $this->db->select('a.qty_budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_others_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_others_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_item', $item->id_item);
            $this->db->where('b.sts', 1);
            $this->db->where('b.created_date <', $item->created_date);
            $get_overbudget_others = $this->db->get()->result();

            foreach ($get_overbudget_others as $item_overbudget) {
                $qty_overbudget += $item_overbudget->qty_budget_tambahan;
            }

            $arr_update_kasbon_others[] = [
                'id' => $item->id,
                'nominal_terpakai' => $item->nominal_pengajuan,
                'qty_terpakai' => $qty_terpakai,
                'total_terpakai' => ($qty_terpakai * $item->price_unit_estimasi),
                'qty_overbudget' => $qty_overbudget,
                'nominal_overbudget' => ($item->price_unit_estimasi),
                'total_overbudget' => ($qty_overbudget * $item->price_unit_estimasi)
            ];
        }

        $arr_update_kasbon_lab = [];

        $get_kasbon_lab = $this->db->get('kons_tr_kasbon_project_lab')->result();
        foreach ($get_kasbon_lab as $item) {

            $qty_terpakai = 0;
            $this->db->select('a.qty_pengajuan');
            $this->db->from('kons_tr_kasbon_project_lab a');
            $this->db->where('a.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_lab', $item->id_lab);
            $this->db->where('a.sts', 1);
            $this->db->where('a.created_date <', $item->created_date);
            $get_qty_terpakai = $this->db->get()->result();

            foreach ($get_qty_terpakai as $item_qty_terpakai) {
                $qty_terpakai += $item_qty_terpakai->qty_pengajuan;
            }

            $qty_overbudget = 0;
            $this->db->select('a.qty_budget_tambahan');
            $this->db->from('kons_tr_kasbon_req_ovb_lab_detail a');
            $this->db->join('kons_tr_kasbon_req_ovb_lab_header b', 'b.id_request_ovb = a.id_request_ovb');
            $this->db->where('b.id_spk_budgeting', $item->id_spk_budgeting);
            $this->db->where('a.id_item', $item->id_item);
            $this->db->where('b.sts', 1);
            $this->db->where('b.created_date <', $item->created_date);
            $get_overbudget_lab = $this->db->get()->result();

            foreach ($get_overbudget_lab as $item_overbudget) {
                $qty_overbudget += $item_overbudget->qty_budget_tambahan;
            }

            $arr_update_kasbon_lab[] = [
                'id' => $item->id,
                'nominal_terpakai' => $item->nominal_pengajuan,
                'qty_terpakai' => $qty_terpakai,
                'total_terpakai' => ($qty_terpakai * $item->price_unit_estimasi),
                'qty_overbudget' => $qty_overbudget,
                'nominal_overbudget' => ($item->price_unit_estimasi),
                'total_overbudget' => ($qty_overbudget * $item->price_unit_estimasi)
            ];
        }



        if (!empty($arr_insert_lab)) {
            $insert_lab = $this->db->insert_batch('kons_tr_spk_budgeting_lab', $arr_insert_lab);
        }

        if (!empty($arr_update_kasbon_subcont)) {
            $update_kasbon_subcont = $this->db->update_batch('kons_tr_kasbon_project_subcont', $arr_update_kasbon_subcont, 'id');
        }

        if (!empty($arr_update_kasbon_akomodasi)) {
            $update_kasbon_akomodasi = $this->db->update_batch('kons_tr_kasbon_project_akomodasi', $arr_update_kasbon_akomodasi, 'id');
        }

        if (!empty($arr_update_kasbon_others)) {
            $update_kasbon_others = $this->db->update_batch('kons_tr_kasbon_project_others', $arr_update_kasbon_others, 'id');
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $msg = 'Update data has been failed !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $msg = 'Update data has been success !';
        }

        echo $msg;
    }

    public function fill_request_payment()
    {

        // $this->otherdb = $this->load->database('sendigs_finance', TRUE);

        $arr_kasbon_sendigs = [];
        $arr_kasbon_sendigs_rp = [];

        $this->db->select('a.*');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->where('a.sts', 1);
        $get_kasbon = $this->db->get()->result();
        // $sql = "SELECT * FORM tr_kasbon WHERE no_kasbon_consultant = '$this->id' ";
        // $result = $this->db->get_where(DBSF . '.tr_kasbon', ['no_kasbon_consultant' => $this->id])->result();

        $no = 0;
        foreach ($get_kasbon as $item) {
            // $get_kasbon_sendigs = $this->otherdb->get_where(DBSF . '.tr_kasbon', array('no_kasbon_consultant' => $item->id));
            $get_kasbon_sendigs = $this->otherdb->query('SELECT * FROM tr_kasbon WHERE no_kasbon_consultant = "' . $item->id . '"')->result();
            // if (!$get_kasbon_sendigs) {
            // print_r($get_kasbon_sendigs);
            // exit;
            // }

            if (count($get_kasbon_sendigs) < 1) {
                $no++;
                $id_kasbon = $this->Perbaikan_data_model->no_sendigs('format_kasbon', $no);

                $get_user = $this->db->get_where('users', array('id_user' => $item->created_by))->row();
                $nama = (!empty($get_user)) ? $get_user->nm_lengkap : '';

                $get_user_now = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();
                $nama_now = (!empty($get_user_now)) ? $get_user_now->nm_lengkap : '';

                $project = '';

                if ($item->tipe == '1') {
                    $project = 'Subcont';
                }
                if ($item->tipe == '2') {
                    $project = 'Akomodasi';
                }
                if ($item->tipe == '3') {
                    $project = 'Others';
                }
                if ($item->tipe == '4') {
                    $project = 'Lab';
                }


                $arr_kasbon_sendigs[] = [
                    'no_doc' => $id_kasbon,
                    'tgl_doc' => $item->tgl,
                    'nama' => strtoupper($nama),
                    'jumlah_kasbon' => $item->grand_total,
                    'keperluan' => $item->deskripsi,
                    'status' => '1',
                    'created_by' => strtoupper($nama),
                    'created_on' => date('Y-m-d H:i:s'),
                    'bank_id' => $item->bank,
                    'accnumber' => $item->bank_number,
                    'accname' => $item->bank_account,
                    'project' => $project,
                    'metode_pembayaran' => 1,
                    'project_consultant' => 1,
                    'no_kasbon_consultant' => $item->id
                ];

                $arr_kasbon_sendigs_rp[] = [
                    'no_doc' => $id_kasbon,
                    'nama' => $nama,
                    'tgl_doc' => $item->tgl,
                    'keperluan' => $item->deskripsi,
                    'tipe' => 'kasbon',
                    'jumlah' => $item->grand_total,
                    'status' => 0,
                    'tanggal' => date('Y-m-d'),
                    'created_by' => $nama_now,
                    'created_on' => date('Y-m-d H:i:s'),
                    'bank_id' => $item->bank,
                    'accnumber' => $item->bank_number,
                    'accname' => $item->bank_account,
                    'ids' => $item->id
                ];
            }
        }

        $arr_expense_sendigs = [];
        $arr_expense_detail_sendigs = [];
        $arr_expense_sendigs_rp = [];

        $this->db->select('a.*');
        $this->db->from('kons_tr_expense_report_project_header a');
        $this->db->where('a.sts', 1);
        $get_expense = $this->db->get()->result();

        $no = 0;
        foreach ($get_expense as $item) {
            // $get_expense_sendigs = $this->db->get_where(DBSF . '.tr_expense', array('no_expense_consultant' => $item->id))->result();
            $get_expense_sendigs = $this->otherdb->query('SELECT * FROM tr_expense WHERE no_expense_consultant = "' . $item->id . '"')->result();

            $get_user = $this->db->get_where('users', array('id_user' => $item->created_by))->row();
            $nama = (!empty($get_user)) ? $get_user->nm_lengkap : '';

            $get_user_now = $this->db->get_where('users', array('id_user' => $this->auth->user_id()))->row();
            $nama_now = (!empty($get_user_now)) ? $get_user_now->nm_lengkap : '';

            if (count($get_expense_sendigs) < 1 && $item->selisih < 0) {
                $no++;

                $id_expense = $this->Perbaikan_data_model->no_sendigs('format_expense', $no);

                $get_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $item->id_header))->row();

                $informasi = (!empty($get_kasbon)) ? $get_kasbon->deskripsi : '';

                $arr_expense_sendigs[] = [
                    'no_doc' => $id_expense,
                    'tgl_doc' => date('Y-m-d', strtotime($item->created_date)),
                    'nama' => strtoupper($nama),
                    'status' => 1,
                    'created_by' => $item->created_by,
                    'created_on' => $item->created_date,
                    'jumlah' => ($item->selisih * -1),
                    'informasi' => $informasi,
                    'id_kasbon' => $item->id_header,
                    'created_by' => $this->auth->user_id(),
                    'created_on' => date('Y-m-d H:i:s'),
                    'project_consultant' => 1,
                    'no_expense_consultant' => $item->id
                ];

                $get_expense_detail = $this->db->get_where('kons_tr_expense_report_project_detail', array('id_header_expense' => $item->id))->result();
                foreach ($get_expense_detail as $item_detail) {

                    $total_harga = ($item_detail->qty_expense * $item_detail->nominal_expense);
                    $arr_expense_detail_sendigs[] = [
                        'tanggal' => date('Y-m-d'),
                        'no_doc' => $id_expense,
                        'deskripsi' => $informasi,
                        'qty' => $item_detail->qty_expense,
                        'harga' => $item_detail->nominal_expense,
                        'total_harga' => $total_harga,
                        'keterangan' => $informasi,
                        'status' => 2,
                        'expense' => $total_harga,
                        'created_by' => $nama,
                        'created_on' => date('Y-m-d H:i:s')
                    ];
                }

                $arr_expense_sendigs_rp[] = [
                    'no_doc' => $id_kasbon,
                    'nama' => $nama,
                    'tgl_doc' => date('Y-m-d', strtotime($item->created_date)),
                    'keperluan' => $informasi,
                    'tipe' => 'expense',
                    'jumlah' => ($item->selisih * -1),
                    'status' => 0,
                    'tanggal' => date('Y-m-d'),
                    'created_by' => $nama_now,
                    'created_on' => date('Y-m-d H:i:s'),
                    'ids' => $item->id
                ];
            }
        }

        $this->db->trans_begin();

        if (!empty($arr_kasbon_sendigs)) {
            $insert_kasbon_sendigs = $this->otherdb->insert_batch('tr_kasbon', $arr_kasbon_sendigs);
            if (!$insert_kasbon_sendigs) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }

        if (!empty($arr_expense_sendigs)) {
            $insert_expense_sendigs = $this->otherdb->insert_batch('tr_expense', $arr_expense_sendigs);
            if (!$insert_expense_sendigs) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }

        if (!empty($arr_expense_detail_sendigs)) {
            $insert_expense_detail_sendigs = $this->otherdb->insert_batch('tr_expense_detail', $arr_expense_detail_sendigs);
            if (!$insert_expense_detail_sendigs) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }



        // if (!empty($arr_kasbon_sendigs_rp)) {
        //     $insert_kasbon_request_payment = $this->otherdb->insert_batch('request_payment', $arr_kasbon_sendigs_rp);


        //     if (!$insert_kasbon_request_payment) {
        //         $this->db->trans_rollback();

        //         print_r($this->db->last_query());
        //         exit;
        //     }
        // }

        // if (!empty($arr_expense_sendigs_rp)) {
        //     $insert_expense_request_payment = $this->otherdb->insert_batch('request_payment', $arr_expense_sendigs_rp);
        //     if (!$insert_expense_request_payment) {
        //         $this->db->trans_rollback();

        //         print_r($this->db->last_query());
        //         exit;
        //     }
        // }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $msg = 'Error !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $msg = 'Update success !';
        }

        echo $msg;
    }

    public function fill_payment()
    {
        $get_payment = $this->db->get('payment_approve')->result();
        $get_payment_detail = $this->db->get('payment_approve_details')->result();

        $arr_payment = [];
        $arr_payment_detail = [];

        foreach ($get_payment as $item) {
            // $get_sendigs_payment = $this->db->get_where(DBSF . '.payment_approve', ['id' => $item->id])->result();
            $query = 'SELECT * FROM payment_approve WHERE id = "' . $item->id . '"';
            $get_sendigs_payment = $this->otherdb->query($query)->result();

            if (empty($get_sendigs_payment)) {
                $no_doc = '';

                if ($item->tipe == 'kasbon') {
                    $query_kasbon = 'SELECT * FROM tr_kasbon WHERE no_kasbon_consultant = "' . $item->no_doc . '"';
                    $get_kasbon = $this->otherdb->query($query_kasbon)->row();

                    $no_doc = (!empty($get_kasbon)) ? $get_kasbon->no_doc : '';
                }
                if ($item->tipe == 'expense') {
                    $query_expense = 'SELECT * FROM tr_expense WHERE no_expense_consultant = "' . $item->no_doc . '"';
                    $get_expense = $this->otherdb->query($query_expense)->row();

                    $no_doc = (!empty($get_expense)) ? $get_expense->no_doc : '';
                }

                $arr_payment[] = [
                    'id' => $item->id,
                    'no_doc' => $no_doc,
                    'nama' => $item->nama,
                    'tgl_doc' => $item->tgl_doc,
                    'keperluan' => $item->keperluan,
                    'tipe' => $item->tipe,
                    'jumlah' => $item->jumlah,
                    'status' => $item->status,
                    'tanggal' => $item->tanggal,
                    'keterangan' => $item->keterangan,
                    'created_by' => $item->created_by,
                    'created_on' => $item->created_on,
                    'approved_by' => $item->approved_by,
                    'approved_on' => $item->approved_on,
                    'pay_by' => $item->pay_by,
                    'pay_on' => $item->pay_on,
                    'doc_file' => $item->doc_file,
                    'doc_file_2' => $item->doc_file_2,
                    'bank_id' => $item->bank_id,
                    'accnumber' => $item->accnumber,
                    'accname' => $item->accname,
                    'ids' => $item->ids,
                    'no_request' => $item->no_request,
                    'app_checker' => $item->app_checker,
                    'app_checker_by' => $item->app_checker_by,
                    'app_checker_date' => $item->app_checker_date,
                    'currency' => $item->currency,
                    'bank_name' => $item->bank_name,
                    'admin_bank' => $item->admin_bank,
                    'link_doc' => $item->link_doc,
                    'id_payment' => $item->id,
                    'tgl_bayar' => $item->tanggal_pembayaran,
                    'keterangan_pembayaran' => $item->keterangan_pembayaran,
                    'mata_uang' => 'IDR',
                    'payment_bank' => $item->nilai_bayar,
                    'total_payment' => $item->nilai_bayar,
                    'selisih' => $item->selisih,
                    'kurs_payment' => 1
                ];
            }
        }

        foreach ($get_payment_detail as $item) {
            $query_payment_detail = 'SELECT * FROM payment_approve_details WHERE id = "' . $item->id . '"';
            $get_sendigs_payment_detail = $this->otherdb->query($query_payment_detail)->result();
            if (empty($get_sendigs_payment_detail)) {
                $no_doc = '';

                $get_payment = $this->db->get_where('payment_approve', ['id' => $item->payment_id])->row();

                if ($get_payment->tipe == 'kasbon') {
                    $query_kasbon = 'SELECT * FROM tr_kasbon WHERE no_kasbon_consultant = "' . $item->no_doc . '"';
                    $get_kasbon = $this->otherdb->query($query_kasbon)->row();

                    $no_doc = (!empty($get_kasbon)) ? $get_kasbon->no_doc : '';
                }
                if ($get_payment->tipe == 'expense') {
                    $query_expense = 'SELECT * FROM tr_expense WHERE no_expense_consultant = "' . $item->no_doc . '"';
                    $get_expense = $this->otherdb->query($query_expense)->row();

                    $no_doc = (!empty($get_expense)) ? $get_expense->no_doc : '';
                }

                $arr_payment_detail[] = [
                    'id' => $item->id,
                    'payment_id' => $item->payment_id,
                    'no_doc' => $no_doc,
                    'tgl_doc' => $item->tgl_doc,
                    'deskripsi' => $item->deskripsi,
                    'qty' => $item->qty,
                    'harga' => $item->harga,
                    'total' => $item->total,
                    'keterangan' => $item->keterangan,
                    'doc_file' => $item->doc_file,
                    'coa' => $item->coa,
                    'created_by' => $item->created_by,
                    'created_on' => $item->created_on
                ];
            }
        }

        $this->db->trans_begin();

        if (!empty($arr_payment)) {
            $insert_payment = $this->otherdb->insert_batch('payment_approve', $arr_payment);
            if (!$insert_payment) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }

        if (!empty($arr_payment_detail)) {
            $insert_detail_payment = $this->otherdb->insert_batch('payment_approve_details', $arr_payment_detail);
            if (!$insert_detail_payment) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            echo 'Error !';
        } else {
            $this->db->trans_commit();

            echo 'Success !';
        }
    }

    public function test()
    {
        echo phpinfo();
    }

    public function fix_pengajuan()
    {
        $get_pengajuan = $this->db->get('kons_tr_kasbon_project_header')->result();

        $this->db->trans_begin();

        foreach ($get_pengajuan as $item) {
            if ($item->tipe == '1') {
                $this->db->select('SUM(a.total_pengajuan) as ttl_detail');
                $this->db->from('kons_tr_kasbon_project_subcont a');
                $this->db->where('a.id_header', $item->id);
                $get_ttl_detail = $this->db->get()->row();

                if ($item->grand_total <> $get_ttl_detail->ttl_detail) {
                    echo $item->id . ' - ' . number_format($item->grand_total) . ' - ' . number_format($get_ttl_detail->ttl_detail) . '<br>';

                    $this->db->update('kons_tr_kasbon_project_header', array('grand_total' => $get_ttl_detail->ttl_detail), array('id' => $item->id));
                }
            }
            if ($item->tipe == '2') {
                $this->db->select('SUM(a.total_pengajuan) as ttl_detail');
                $this->db->from('kons_tr_kasbon_project_akomodasi a');
                $this->db->where('a.id_header', $item->id);
                $get_ttl_detail = $this->db->get()->row();

                if ($item->grand_total <> $get_ttl_detail->ttl_detail) {
                    echo $item->id . ' - ' . number_format($item->grand_total) . ' - ' . number_format($get_ttl_detail->ttl_detail) . '<br>';

                    $this->db->update('kons_tr_kasbon_project_header', array('grand_total' => $get_ttl_detail->ttl_detail), array('id' => $item->id));
                }
            }
            if ($item->tipe == '3') {
                $this->db->select('SUM(a.total_pengajuan) as ttl_detail');
                $this->db->from('kons_tr_kasbon_project_others a');
                $this->db->where('a.id_header', $item->id);
                $get_ttl_detail = $this->db->get()->row();

                if ($item->grand_total <> $get_ttl_detail->ttl_detail) {
                    echo $item->id . ' - ' . number_format($item->grand_total) . ' - ' . number_format($get_ttl_detail->ttl_detail) . '<br>';

                    $this->db->update('kons_tr_kasbon_project_header', array('grand_total' => $get_ttl_detail->ttl_detail), array('id' => $item->id));
                }
            }
            if ($item->tipe == '4') {
                $this->db->select('SUM(a.total_pengajuan) as ttl_detail');
                $this->db->from('kons_tr_kasbon_project_lab a');
                $this->db->where('a.id_header', $item->id);
                $get_ttl_detail = $this->db->get()->row();

                if ($item->grand_total <> $get_ttl_detail->ttl_detail) {
                    echo $item->id . ' - ' . number_format($item->grand_total) . ' - ' . number_format($get_ttl_detail->ttl_detail) . '<br>';

                    $this->db->update('kons_tr_kasbon_project_header', array('grand_total' => $get_ttl_detail->ttl_detail), array('id' => $item->id));
                }
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            echo 'ERROR!!';
        } else {
            $this->db->trans_commit();

            echo 'SUCCESS!';
        }
    }

    public function fix_kasbon_no()
    {
        $this->db->select('a.*, c.nm_lengkap');
        $this->db->from('kons_tr_kasbon_project_header a');
        $this->db->join('request_payment b', 'b.no_doc = a.id', 'left');
        $this->db->join('users c', 'c.id_user = a.created_by', 'left');
        $this->db->where('a.sts', 1);
        $this->db->where('b.no_doc', null);
        $get_kasbon = $this->db->get()->result();

        $arr_insert_req_payment = [];
        foreach ($get_kasbon as $item) {
            echo $item->id . " - " . $item->deskripsi . '<br>';

            $arr_insert_req_payment[] = [
                'no_doc' => $item->id,
                'nama' => $item->nm_lengkap,
                'tgl_doc' => $item->tgl,
                'keperluan' => $item->deskripsi,
                'jumlah' => $item->grand_total,
                'created_by' => $item->nm_lengkap,
                'created_on' => date('Y-m-d H:i:s'),
                'ids' => $item->id,
                'currency' => 'idr'
            ];
        }

        $this->db->trans_begin();

        $this->db->insert_batch('request_payment', $arr_insert_req_payment);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            echo 'ERROR !';
        } else {
            $this->db->trans_commit();

            echo 'SUCCESS !';
        }
    }
}
