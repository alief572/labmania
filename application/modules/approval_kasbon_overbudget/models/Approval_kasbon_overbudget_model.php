<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Approval_kasbon_overbudget_model extends BF_Model
{
    /**
     * Build the UNION ALL query for overbudget data.
     * Each sub-select properly GROUP BY a.id_request_ovb to avoid
     * row duplication from LEFT JOIN to kons_tr_spk_penawaran.
     */
    private function _build_union_query($search_value)
    {
        $search_escaped = $this->db->escape_like_str($search_value);

        $tables = [
            ['header' => 'kons_tr_kasbon_req_ovb_akomodasi_header', 'detail' => 'kons_tr_kasbon_req_ovb_akomodasi_detail', 'tipe' => '2'],
            ['header' => 'kons_tr_kasbon_req_ovb_subcont_header', 'detail' => 'kons_tr_kasbon_req_ovb_subcont_detail', 'tipe' => '1'],
            ['header' => 'kons_tr_kasbon_req_ovb_others_header', 'detail' => 'kons_tr_kasbon_req_ovb_others_detail', 'tipe' => '3'],
            ['header' => 'kons_tr_kasbon_req_ovb_lab_header', 'detail' => 'kons_tr_kasbon_req_ovb_lab_detail', 'tipe' => '4'],
            ['header' => 'kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_header', 'detail' => 'kons_tr_kasbon_req_ovb_subcont_tenaga_ahli_detail', 'tipe' => '5'],
            ['header' => 'kons_tr_kasbon_req_ovb_subcont_perusahaan_header', 'detail' => 'kons_tr_kasbon_req_ovb_subcont_perusahaan_detail', 'tipe' => '6'],
        ];

        $unions = [];
        foreach ($tables as $t) {
            $search_condition = '';
            if (!empty($search_value)) {
                $search_condition = '
                    AND (
                        a.id_spk_budgeting LIKE "%' . $search_escaped . '%"
                        OR a.id_spk_penawaran LIKE "%' . $search_escaped . '%"
                        OR a.id_penawaran LIKE "%' . $search_escaped . '%"
                        OR (SELECT b.nm_customer FROM kons_tr_spk_penawaran b WHERE b.id_spk_penawaran = a.id_spk_penawaran LIMIT 1) LIKE "%' . $search_escaped . '%"
                    )';
            }

            $unions[] = '
                SELECT
                    a.id_request_ovb,
                    a.id_spk_budgeting,
                    a.id_spk_penawaran,
                    a.sts,
                    a.id_penawaran,
                    (SELECT b.nm_customer FROM kons_tr_spk_penawaran b WHERE b.id_spk_penawaran = a.id_spk_penawaran LIMIT 1) AS nama_customer,
                    (SELECT COALESCE(SUM(d.budget_tambahan * d.qty_budget_tambahan), 0) FROM ' . $t['detail'] . ' d WHERE d.id_request_ovb = a.id_request_ovb) AS nominal,
                    "' . $t['tipe'] . '" AS tipe
                FROM
                    ' . $t['header'] . ' a
                WHERE
                    a.sts IS NULL
                    AND a.id_request_ovb IS NOT NULL
                    AND a.id_request_ovb <> ""
                    ' . $search_condition;
        }

        return implode("\n UNION ALL \n", $unions);
    }

    public function get_data_overbudget()
    {
        $draw   = $this->input->post('draw');
        $start  = (int) $this->input->post('start');
        $length = (int) $this->input->post('length');
        $search = $this->input->post('search');

        $search_value = isset($search['value']) ? $search['value'] : '';

        $union_sql = $this->_build_union_query($search_value);

        // Count total filtered records
        $count_sql = 'SELECT COUNT(*) AS total FROM (' . $union_sql . ') z';
        $count_result = $this->db->query($count_sql)->row();
        $total_filtered = (int) $count_result->total;

        // Get paginated data
        $data_sql = $union_sql . '
            ORDER BY id_request_ovb DESC
            LIMIT ' . $length . ' OFFSET ' . $start;
        $get_data = $this->db->query($data_sql);

        $hasil = array();
        $no = $start;
        foreach ($get_data->result_array() as $item) {
            $no++;

            $view = '<button type="button" class="btn btn-sm btn-info detail" data-id="' . $item['id_request_ovb'] . '" data-tipe="' . $item['tipe'] . '" title="View Overbudget"><i class="fa fa-eye"></i></button>';

            $approval = '<button type="button" class="btn btn-sm btn-success approval" data-id="' . $item['id_request_ovb'] . '" data-tipe="' . $item['tipe'] . '" title="Approve Overbudget"><i class="fa fa-check"></i></button>';

            $option = $view . ' ' . $approval;

            $status = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';
            if ($item['sts'] == 1) {
                $status = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item['sts'] == 2) {
                $status = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }

            $hasil[] = [
                'no'              => $no,
                'id_request'      => $item['id_request_ovb'],
                'id_spk_budgeting' => $item['id_spk_budgeting'],
                'id_spk_penawaran' => $item['id_spk_penawaran'],
                'id_penawaran'    => $item['id_penawaran'],
                'nama_customer'   => $item['nama_customer'],
                'nominal'         => number_format($item['nominal'], 2),
                'status'          => $status,
                'option'          => $option
            ];
        }

        echo json_encode([
            'draw'            => intval($draw),
            'recordsTotal'    => $total_filtered,
            'recordsFiltered' => $total_filtered,
            'data'            => $hasil
        ]);
    }
}
