<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Penawaran_model extends BF_Model
{
    protected $dbhr;

    public function __construct()
    {
        $this->dbhr = $this->load->database('dbhr', true);
    }

    public function get_penawaran($id_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_non_konsultasi a');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_data = $this->db->get()->row();

        return $get_data;
    }

    public function get_penawaran_detail($id_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_detail_penawaran_non_konsultasi a');
        $this->db->where('a.id_header', $id_penawaran);
        $get_data = $this->db->get()->result();

        return $get_data;
    }
    public function list_divisi()
    {
        $this->dbhr->select('a.id as id_divisi, a.name as nm_divisi, b.name as nm_company');
        $this->dbhr->from('departments a');
        $this->dbhr->join('companies b', 'b.id = a.company_id', 'left');
        $this->dbhr->where_in('a.company_id', ['COM003', 'COM006', 'COM012']);
        $this->dbhr->order_by('a.name', 'asc');
        $get_data = $this->dbhr->get()->result();

        return $get_data;
    }

    public function list_customer()
    {
        $this->db->select('a.id_customer, a.nm_customer');
        $this->db->from('customer a');
        $this->db->where('a.deleted', 'N');
        $this->db->order_by('a.nm_customer', 'asc');
        $get_data = $this->db->get()->result();

        return $get_data;
    }

    public function list_company()
    {
        $this->db->select('a.id as id_company, a.nm_company');
        $this->db->from('kons_tr_company a');
        $this->db->order_by('a.nm_company', 'asc');
        $get_data = $this->db->get()->result();

        return $get_data;
    }


    public function list_employee()
    {
        $this->dbhr->select('a.id, a.name as nm_karyawan');
        $this->dbhr->from('employees a');
        $this->dbhr->where('a.flag_active', 'Y');
        $get_employees = $this->dbhr->get()->result();

        return $get_employees;
    }
    public function generate_history_id()
    {
        $this->db->select('a.id_history');
        $this->db->from('kons_tr_penawaran_history a');
        $this->db->like('a.id_history', 'HST-' . date('Ym'), 'after');
        $this->db->order_by('a.id_history', 'DESC');
        $this->db->limit(1);
        $get_data = $this->db->get()->row();

        if (empty($get_data)) {
            $last_id = 0;
        } else {
            $last_id = $get_data->id_history;
            $last_id = substr($last_id, 11, 5);
        }
        $last_id = intval($last_id) + 1;
        $new_id = 'HST-' . date('Ym') . '-' . str_pad($last_id, 5, '0', STR_PAD_LEFT);

        return $new_id;
    }

    public function history_penawaran($id_penawaran)
    {
        $id_history = $this->generate_history_id();

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $id_penawaran])->row_array();
        $get_penawaran_act = $this->db->get_where('kons_tr_penawaran_aktifitas', ['id_penawaran' => $id_penawaran])->result_array();
        $get_penawaran_akomodasi = $this->db->get_where('kons_tr_penawaran_akomodasi', ['id_penawaran' => $id_penawaran])->result_array();
        $get_penawaran_others = $this->db->get_where('kons_tr_penawaran_others', ['id_penawaran' => $id_penawaran])->result_array();
        $get_penawaran_lab = $this->db->get_where('kons_tr_penawaran_lab', ['id_penawaran' => $id_penawaran])->result_array();
        $get_penawaran_subcont_tenaga_ahli = $this->db->get_where('kons_tr_penawaran_subcont_tenaga_ahli', ['id_penawaran' => $id_penawaran])->result_array();
        $get_penawaran_subcont_perusahaan = $this->db->get_where('kons_tr_penawaran_subcont_perusahaan', ['id_penawaran' => $id_penawaran])->result_array();

        $this->db->trans_begin();

        $arr_penawaran_history = [
            'id_history' => $id_history,
            'id_quotation' => $id_penawaran,
            'tipe_penawaran' => $get_penawaran['tipe_penawaran'],
            'tgl_quotation' => $get_penawaran['tgl_quotation'],
            'id_customer' => $get_penawaran['id_customer'],
            'nm_customer' => $get_penawaran['nm_customer'],
            'id_marketing' => $get_penawaran['id_marketing'],
            'nm_marketing' => $get_penawaran['nm_marketing'],
            'id_pic' => $get_penawaran['id_pic'],
            'nm_pic' => $get_penawaran['nm_pic'],
            'address' => $get_penawaran['address'],
            'id_paket' => $get_penawaran['id_paket'],
            'nm_paket' => $get_penawaran['nm_paket'],
            'id_divisi' => $get_penawaran['id_divisi'],
            'nm_divisi' => $get_penawaran['nm_divisi'],
            'upload_proposal' => $get_penawaran['upload_proposal'],
            'upload_tahapan' => $get_penawaran['upload_tahapan'],
            'upload_po' => $get_penawaran['upload_po'],
            'grand_total' => $get_penawaran['grand_total'],
            'sts_cust' => $get_penawaran['sts_cust'],
            'sts_quot' => $get_penawaran['sts_quot'],
            'ppn' => $get_penawaran['ppn'],
            'persen_disc' => $get_penawaran['persen_disc'],
            'nilai_disc' => $get_penawaran['nilai_disc'],
            'tipe_informasi_awal' => $get_penawaran['tipe_informasi_awal'],
            'detail_informasi_awal' => $get_penawaran['detail_informasi_awal'],
            'total_mandays' => $get_penawaran['total_mandays'],
            'mandays_subcont' => $get_penawaran['mandays_subcont'],
            'mandays_tandem' => $get_penawaran['mandays_tandem'],
            'mandays_internal' => $get_penawaran['mandays_internal'],
            'mandays_rate' => $get_penawaran['mandays_rate'],
            'sts_reject' => $get_penawaran['sts_reject'],
            'reject_reason' => $get_penawaran['reject_reason'],
            'sts_deal' => $get_penawaran['sts_deal'],
            'revisi' => ($get_penawaran['revisi']),
            'company' => $get_penawaran['company'],
            'nm_company' => $get_penawaran['nm_company'],
            'input_by' => $this->auth->user_id(),
            'input_date' => date('Y-m-d H:i:s')
        ];

        $arr_act_history = [];
        foreach ($get_penawaran_act as $item) {
            $arr_act_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_aktifitas' => $item['id_aktifitas'],
                'nm_aktifitas' => $item['nm_aktifitas'],
                'bobot' => $item['bobot'],
                'mandays' => $item['mandays'],
                'mandays_rate' => $item['mandays_rate'],
                'mandays_subcont' => $item['mandays_subcont'],
                'mandays_rate_subcont' => $item['mandays_rate_subcont'],
                'mandays_tandem' => $item['mandays_tandem'],
                'mandays_rate_tandem' => $item['mandays_rate_tandem'],
                'harga_aktifitas' => $item['harga_aktifitas'],
                'total_aktifitas' => $item['total_aktifitas'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $arr_akomodasi_history = [];
        foreach ($get_penawaran_akomodasi as $item) {
            $arr_akomodasi_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_item' => $item['id_item'],
                'nm_item' => $item['nm_item'],
                'qty' => $item['qty'],
                'price_unit' => $item['price_unit'],
                'total' => $item['total'],
                'keterangan' => $item['keterangan'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $arr_others_history = [];
        foreach ($get_penawaran_others as $item) {
            $arr_others_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_item' => $item['id_item'],
                'nm_item' => $item['nm_item'],
                'qty' => $item['qty'],
                'price_unit' => $item['price_unit'],
                'total' => $item['total'],
                'price_unit_budget' => $item['price_unit_budget'],
                'total_budget' => $item['total_budget'],
                'keterangan' => $item['keterangan'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $arr_lab_history = [];
        foreach ($get_penawaran_lab as $item) {
            $arr_lab_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_item' => $item['id_item'],
                'nm_item' => $item['nm_item'],
                'qty' => $item['qty'],
                'price_unit' => $item['price_unit'],
                'total' => $item['total'],
                'price_unit_budget' => $item['price_unit_budget'],
                'total_budget' => $item['total_budget'],
                'keterangan' => $item['keterangan'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $arr_subcont_tenaga_ahli_history = [];
        foreach ($get_penawaran_subcont_tenaga_ahli as $item) {
            $arr_subcont_tenaga_ahli_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_item' => $item['id_item'],
                'nm_item' => $item['nm_item'],
                'qty' => $item['qty'],
                'price_unit' => $item['price_unit'],
                'total' => $item['total'],
                'price_unit_budget' => $item['price_unit_budget'],
                'total_budget' => $item['total_budget'],
                'keterangan' => $item['keterangan'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $arr_subcont_perusahaan_history = [];
        foreach ($get_penawaran_subcont_perusahaan as $item) {
            $arr_subcont_perusahaan_history[] = [
                'id_history' => $id_history,
                'id_penawaran' => $id_penawaran,
                'id_item' => $item['id_item'],
                'nm_item' => $item['nm_item'],
                'qty' => $item['qty'],
                'price_unit' => $item['price_unit'],
                'total' => $item['total'],
                'price_unit_budget' => $item['price_unit_budget'],
                'total_budget' => $item['total_budget'],
                'keterangan' => $item['keterangan'],
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ];
        }

        $this->db->insert('kons_tr_penawaran_history', $arr_penawaran_history);

        if (!empty($arr_act_history)) {
            $this->db->insert_batch('kons_tr_penawaran_aktifitas_history', $arr_act_history);
        }
        if (!empty($arr_akomodasi_history)) {
            $this->db->insert_batch('kons_tr_penawaran_akomodasi_history', $arr_akomodasi_history);
        }
        if (!empty($arr_others_history)) {
            $this->db->insert_batch('kons_tr_penawaran_others_history', $arr_others_history);
        }
        if (!empty($arr_lab_history)) {
            $this->db->insert_batch('kons_tr_penawaran_lab_history', $arr_lab_history);
        }
        if (!empty($arr_subcont_tenaga_ahli_history)) {
            $this->db->insert_batch('kons_tr_penawaran_subcont_tenaga_ahli_history', $arr_subcont_tenaga_ahli_history);
        }
        if (!empty($arr_subcont_perusahaan_history)) {
            $this->db->insert_batch('kons_tr_penawaran_subcont_perusahaan_history', $arr_subcont_perusahaan_history);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }
}
