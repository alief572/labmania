<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class History_spk_penawaran_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function get_data_history($search = null, $start = 0, $length = 10)
    {
        $this->db->select('a.*, COALESCE(b.nm_paket, a.nm_project) as nm_projects');
        $this->db->from('kons_tr_spk_penawaran_history a');
        $this->db->join('kons_master_konsultasi_header b', 'b.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.deleted_by IS NULL');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_penawaran', $search, 'both');
            $this->db->or_like('a.input_date', $search, 'both');
            $this->db->or_like('a.nm_customer', $search, 'both');
            $this->db->or_like('a.nm_project', $search, 'both');
            $this->db->or_like('a.nm_sales', $search, 'both');
            $this->db->group_end();
        }

        $this->db->order_by('a.input_date', 'desc');

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->limit($length, $start);
        $get_data = $this->db->get();

        if ($get_data === false) {
            log_message('error', 'Query error: ' . $this->db->last_query());
            return [
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }

        $result = [];
        $no = $start + 1;
        foreach ($get_data->result() as $item) {
            $result[] = [
                'no' => $no,
                'id_history' => $item->id_history,
                'id_spk_penawaran' => $item->id_spk_penawaran,
                'id_penawaran' => $item->id_penawaran,
                'tgl_spk' => $item->input_date,
                'nm_sales' => $item->nm_sales,
                'nm_customer' => $item->nm_customer,
                'nm_project' => $item->nm_projects,
                'nilai_kontrak' => $item->nilai_kontrak,
                'revisi' => $item->revisi,
                'status' => $item->sts_spk,
                'option' => '<a href="' . base_url('history_spk_penawaran/view_spk/' . urlencode(str_replace('/', '|', $item->id_history))) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i></a>'
            ];
            $no++;
        }

        return [
            'draw' => 0,
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_all,
            'data' => $result
        ];
    }

    public function get_detail_history($id_history)
    {
        $result = [];

        $header = $this->db->get_where('kons_tr_spk_penawaran_history', ['id_history' => $id_history])->row();
        if (!$header) {
            return null;
        }

        $aktifitas = $this->db->get_where('kons_tr_spk_aktifitas_history', ['id_spk_penawaran' => $header->id_spk_penawaran, 'id_history' => $id_history])->result();

        $subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont_history', ['id_spk_penawaran' => $header->id_spk_penawaran, 'id_history' => $id_history])->result();

        $payment = $this->db->get_where('kons_tr_spk_penawaran_payment_history', ['id_spk_penawaran' => $header->id_spk_penawaran, 'id_history' => $id_history])->result();

        return [
            'header' => $header,
            'aktifitas' => $aktifitas,
            'subcont' => $subcont,
            'payment' => $payment
        ];
    }
}
