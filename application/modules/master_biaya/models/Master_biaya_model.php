<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Master_biaya_model extends BF_Model
{
    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    protected $gl;

    public function __construct()
    {
        $this->ENABLE_ADD     = 'Master_Biaya.Add';
        $this->ENABLE_MANAGE  = 'Master_Biaya.Manage';
        $this->ENABLE_VIEW    = 'Master_Biaya.View';
        $this->ENABLE_DELETE  = 'Master_Biaya.Delete';

        $this->gl = $this->load->database('gl_sendigs', true);
    }

    public function get_data_biaya()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');
        $order = $this->input->post('order');

        // Column mapping for ordering (0-based index from Datatables columns)
        $columns = [
            0 => 'no', // Not sortable
            1 => 'a.nm_biaya',
            2 => 'IF(a.tipe_biaya = 1, "Akomodasi", "Others")',
            3 => 'a.no_coa',
            4 => 'option' // Not sortable
        ];

        // Base Query
        $this->db->from('kons_master_biaya a');
        $this->db->where('a.deleted_by IS NULL');

        // 1. Total records (without search)
        $tempdb = clone $this->db;
        $recordsTotal = $tempdb->count_all_results();

        // 2. Apply Search Filter
        if (!empty($search['value'])) {
            $s = $search['value'];
            $this->db->group_start();
            $this->db->like('a.nm_biaya', $s, 'both');
            $this->db->or_like('IF(a.tipe_biaya = 1, "Akomodasi", "Others")', $s, 'both');
            $this->db->or_like('a.no_coa', $s, 'both');
            $this->db->or_like('a.nm_coa', $s, 'both');
            $this->db->group_end();
        }

        // 3. Filtered records count
        $tempdb = clone $this->db;
        $recordsFiltered = $tempdb->count_all_results();

        // 4. Select and Fetch Data
        $this->db->select('a.id, a.nm_biaya, a.no_coa, a.nm_coa, IF(a.tipe_biaya = 1, "Akomodasi", "Others") as tipe');

        // Ordering
        if (isset($order[0]['column']) && isset($columns[$order[0]['column']])) {
            $colIdx = $order[0]['column'];
            if ($colIdx != 0 && $colIdx != 4) { // Skip 'no' and 'option'
                $this->db->order_by($columns[$colIdx], $order[0]['dir']);
            } else {
                $this->db->order_by('a.id', 'desc');
            }
        } else {
            $this->db->order_by('a.id', 'desc');
        }

        // Paging
        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $get_data_biaya = $this->db->get();

        $hasil = [];
        $no = $start + 1;

        foreach ($get_data_biaya->result() as $item) {

            $edit = '';
            $delete = '';

            if (has_permission($this->ENABLE_MANAGE)) {
                $edit = '<button type="button" class="btn btn-sm btn-warning edit_biaya_modal" data-id="' . $item->id . '" title="Edit Biaya"><i class="fa fa-pencil"></i></button>';
            }

            if (has_permission($this->ENABLE_DELETE)) {
                $delete = '<button type="button" class="btn btn-sm btn-danger del_biaya" data-id="' . $item->id . '" title="Delete Biaya"><i class="fa fa-trash"></i></button>';
            }

            $buttons = $edit . ' ' . $delete;

            $coa = '';
            if ($item->no_coa !== null && $item->nm_coa !== null) {
                $coa = '(' . $item->no_coa . ') - ' . $item->nm_coa;
            }

            $hasil[] = [
                'no' => $no,
                'nm_biaya' => $item->nm_biaya,
                'tipe_biaya' => $item->tipe,
                'coa' => $coa,
                'option' => $buttons
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => $hasil
        ]);
    }

    public function get_coa_all()
    {
        $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
        $this->gl->from('coa_master a');
        $get_coa = $this->gl->get();

        return $get_coa->result();
    }
}
