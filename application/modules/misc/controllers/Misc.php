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
class Misc extends Admin_Controller
{
    protected $sendigs_finance;

    public function __construct()
    {
        parent::__construct();
        $this->sendigs_finance = $this->load->database('sendigs_finance', true);
    }
    public function index()
    {
        $this->db->select('a.id_spk_penawaran');
        $this->db->from('kons_tr_spk_penawaran a');
        $get_data = $this->db->get()->result();

        $this->template->title('Rubah ID SPK');
        $this->template->set('list_spk', $get_data);
        $this->template->render('index');
    }

    public function check_company()
    {
        $this->db->trans_begin();
        try {
            $this->db->select('a.id_spk_penawaran, c.id as id_company, c.nm_company');
            $this->db->from('kons_tr_spk_penawaran a');
            $this->db->join('kons_tr_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran');
            $this->db->join('kons_tr_company c', 'c.id = b.company', 'left');
            $get_spk_penawaran = $this->db->get()->result();

            foreach ($get_spk_penawaran as $item_spk_penawaran) {

                $id_company = $item_spk_penawaran->id_company;
                $nm_company = $item_spk_penawaran->nm_company;

                if (!empty($id_company)) {
                    $this->db->select('a.id');
                    $this->db->from(DBSF . '.tr_invoicing a');
                    $this->db->where('a.id_spk_penawaran', $item_spk_penawaran->id_spk_penawaran);
                    $get_invoicing = $this->db->get()->result();

                    foreach ($get_invoicing as $item_invoicing) {
                        $arr_update_jurnal = [
                            'id_company' => $id_company,
                            'nm_company' => $nm_company
                        ];

                        $this->db->update(DBSF . '.tr_jurnal', $arr_update_jurnal, ['no_transaksi' => $item_invoicing->id]);
                    }
                }
            }

            $this->db->trans_commit();

            echo 'Berhasil !';
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $e->getMessage();
        }
    }

    public function ubah_no_spk()
    {
        $post = $this->input->post();

        $arr_where = [
            'id_spk_penawaran' => $post['nomor_spk']
        ];

        $arr_update = [
            'id_spk_penawaran' => $post['nomor_spk_baru'],
        ];

        $this->db->select('a.id_spk_penawaran');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->where('a.id_spk_penawaran', $post['nomor_spk_baru']);
        $get_spk_penawaran = $this->db->get()->result();

        if (count($get_spk_penawaran) < 1) {
            $this->db->trans_begin();
            try {

                $arr_table = [
                    'kons_tr_spk_penawaran',
                    'kons_tr_spk_penawaran_payment',
                    'kons_tr_spk_penawaran_subcont',
                    'kons_tr_spk_aktifitas',
                    'kons_tr_penawaran',
                    'kons_tr_spk_budgeting',
                    'kons_tr_spk_budgeting_akomodasi',
                    'kons_tr_spk_budgeting_aktifitas',
                    'kons_tr_spk_budgeting_lab',
                    'kons_tr_spk_budgeting_others',
                    'kons_tr_spk_budgeting_subcont_perusahaan',
                    'kons_tr_spk_budgeting_subcont_tenaga_ahli',
                    'kons_tr_kasbon_custom_akomodasi',
                    'kons_tr_kasbon_custom_lab',
                    'kons_tr_kasbon_custom_others',
                    'kons_tr_kasbon_custom_ovb_subcont',
                    'kons_tr_kasbon_custom_subcont_perusahaan',
                    'kons_tr_kasbon_custom_subcont_tenaga_ahli',
                    'kons_tr_kasbon_project_akomodasi',
                    'kons_tr_kasbon_project_header',
                    'kons_tr_kasbon_project_lab',
                    'kons_tr_kasbon_project_others',
                    'kons_tr_kasbon_project_subcont',
                    'kons_tr_kasbon_project_subcont_perusahaan',
                    'kons_tr_kasbon_project_subcont_tenaga_ahli',
                    'kons_tr_kasbon_req_ovb_akomodasi_header',
                    'kons_tr_kasbon_req_ovb_lab_header',
                    'kons_tr_kasbon_req_ovb_others_header',
                    'kons_tr_kasbon_req_ovb_subcont_header',
                    'kons_tr_kasbon_req_ovb_subcont_perusahaan_header',
                    'kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header',
                    'kons_tr_expense_report_project_detail'
                ];

                $arr_table2 = [
                    'kons_tr_plan_tagih_header',
                    'kons_tr_plan_tagih_detail',
                    'kons_tr_actual_plan_tagih',
                    'tr_invoicing'
                ];

                foreach ($arr_table as $table) :
                    $this->db->update($table, $arr_update, $arr_where);
                endforeach;

                foreach ($arr_table2 as $table2) :
                    $this->sendigs_finance->update($table2, $arr_update, $arr_where);
                endforeach;

                $this->db->trans_commit();

                $response = [
                    'msg' => 'No. SPK berhasil di perbarui !'
                ];

                http_response_code(200);

                echo json_encode($response);
            } catch (Exception $e) {
                $this->db->trans_rollback();
                $response = [
                    'msg' => $e->getMessage()
                ];

                http_response_code(500);

                echo json_encode($response);
            }
        } else {
            $response = [
                'msg' => 'Nomor baru sudah ada di program !'
            ];

            http_response_code(500);

            echo json_encode($response);
        }
    }

    public function update_spk_company()
    {
        $arr_update = [];

        $this->db->select('a.id_spk_penawaran, a.company, a.nm_company');
        $this->db->from('kons_tr_penawaran a');
        // $this->db->group_start();
        $this->db->where('a.id_spk_penawaran <>', null);
        $this->db->where('a.id_spk_penawaran <>', '');
        // $this->db->group_end();
        $get_penawaran = $this->db->get()->result();

        foreach ($get_penawaran as $item_penawaran) {
            $arr_update[] = [
                'id_spk_penawaran' => $item_penawaran->id_spk_penawaran,
                'id_company' => $item_penawaran->company,
                'nm_company' => $item_penawaran->nm_company
            ];
        }

        if (!empty($arr_update)) {
            $this->db->trans_begin();

            try {
                $this->db->update_batch('kons_tr_spk_penawaran', $arr_update, 'id_spk_penawaran');

                $this->db->trans_commit();

                echo 'Berhasil !';
            } catch (Exception $e) {
                $this->db->trans_rollback();

                echo 'Gagal - ' . $e->getMessage();
            }
        } else {
            echo 'Tidak ada data yang diedit !';
        }
    }
}
