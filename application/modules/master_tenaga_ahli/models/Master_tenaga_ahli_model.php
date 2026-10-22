<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Master_tenaga_ahli_model extends BF_Model
{
    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    protected $gl;

    public function __construct()
    {
        $this->ENABLE_ADD     = 'Master_Tenaga_Ahli.Add';
        $this->ENABLE_MANAGE  = 'Master_Tenaga_Ahli.Manage';
        $this->ENABLE_VIEW    = 'Master_Tenaga_Ahli.View';
        $this->ENABLE_DELETE  = 'Master_Tenaga_Ahli.Delete';

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
            2 => 'a.no_coa',
            3 => 'option' // Not sortable
        ];

        // Base Query
        $this->db->from('kons_master_tenaga_ahli a');
        $this->db->where('a.deleted_by IS NULL');

        // 1. Total records (without search)
        $tempdb = clone $this->db;
        $recordsTotal = $tempdb->count_all_results();

        // 2. Apply Search Filter
        if (!empty($search['value'])) {
            $s = $search['value'];
            $this->db->group_start();
            $this->db->like('a.nm_biaya', $s, 'both');
            $this->db->or_like('a.no_coa', $s, 'both');
            $this->db->or_like('a.nm_coa', $s, 'both');
            $this->db->group_end();
        }

        // 3. Filtered records count
        $tempdb = clone $this->db;
        $recordsFiltered = $tempdb->count_all_results();

        // 4. Select and Fetch Data
        $this->db->select('a.id, a.nm_biaya, a.no_coa, a.nm_coa');

        // Ordering
        if (isset($order[0]['column']) && isset($columns[$order[0]['column']])) {
            $colIdx = $order[0]['column'];
            if ($colIdx != 0 && $colIdx != 3) { // Skip 'no' and 'option'
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

            $view_btn = '';
            if (has_permission($this->ENABLE_VIEW)) {
                $view_btn = '<button type="button" class="btn-table-action-view view_biaya_modal" data-id="' . $item->id . '" title="Lihat Detail"><i class="fa fa-eye"></i> <span>View</span></button>';
            }

            $edit_btn = '';
            if (has_permission($this->ENABLE_MANAGE)) {
                $edit_btn = '<button type="button" class="btn-table-action-edit edit_biaya_modal" data-id="' . $item->id . '" title="Edit Data"><i class="fa fa-pencil-square-o"></i> <span>Edit</span></button>';
            }

            $del_btn = '';
            if (has_permission($this->ENABLE_DELETE)) {
                $del_btn = '<button type="button" class="btn-table-action-delete del_biaya" data-id="' . $item->id . '" title="Hapus Data"><i class="fa fa-trash-o"></i> <span>Hapus</span></button>';
            }

            $option = '<div class="text-center" style="display: inline-flex; gap: 4px;">' . $view_btn . $edit_btn . $del_btn . '</div>';

            $coa = '<span class="text-muted">-</span>';
            if (!empty($item->no_coa)) {
                $coa = '<span class="label label-info" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-block; background-color: #0284c7;"><i class="fa fa-book"></i> (' . htmlspecialchars($item->no_coa) . ') ' . htmlspecialchars($item->nm_coa) . '</span>';
            }

            $hasil[] = [
                'no' => '<span class="text-muted">' . $no . '</span>',
                'nm_biaya' => '<div style="font-weight: 600; color: #1e293b;">' . htmlspecialchars($item->nm_biaya) . '</div>',
                'coa' => $coa,
                'option' => $option
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
