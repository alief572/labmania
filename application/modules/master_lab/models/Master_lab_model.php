<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Master_lab_model extends BF_Model
{
    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    protected $gl;

    public function __construct()
    {
        $this->ENABLE_ADD     = 'Master_Lab.Add';
        $this->ENABLE_MANAGE  = 'Master_Lab.Manage';
        $this->ENABLE_VIEW    = 'Master_Lab.View';
        $this->ENABLE_DELETE  = 'Master_Lab.Delete';

        $this->gl = $this->load->database('gl_sendigs', true);
    }

    public function save_lab()
    {
        $post = $this->input->post();

        $id = (isset($post['id'])) ? $post['id'] : '';
        $isu_lingkungan = $post['isu_lingkungan'];
        $waktu = $post['waktu'];
        $harga_ssc = str_replace(',', '', $post['harga_ssc']);
        $harga_lab = str_replace(',', '', $post['harga_lab']);
        $peraturan = $post['peraturan'];
        $coa = $post['coa'];

        $get_coa = $this->gl->get_where('coa_master', ['no_perkiraan' => $coa])->row();

        $nm_coa = (!empty($get_coa)) ? $get_coa->nama : '';

        $this->db->trans_begin();

        if ($id == '') {
            $data_insert = [
                'isu_lingkungan' => $isu_lingkungan,
                'peraturan' => $peraturan,
                'waktu' => $waktu,
                'harga_ssc' => $harga_ssc,
                'harga_lab' => $harga_lab,
                'no_coa' => $coa,
                'nm_coa' => $nm_coa,
                'created_by' => $this->auth->user_id(),
                'created_date' => date('Y-m-d H:i:s')
            ];

            $insert_lab = $this->db->insert('kons_master_lab', $data_insert);
            if (!$insert_lab) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                $valid = 0;
                $pesan = 'Please try again later !';
            } else {
                $this->db->trans_commit();

                $valid = 1;
                $pesan = 'Data has been saved !';
            }
        } else {
            $data_update = [
                'isu_lingkungan' => $isu_lingkungan,
                'peraturan' => $peraturan,
                'waktu' => $waktu,
                'harga_ssc' => $harga_ssc,
                'harga_lab' => $harga_lab,
                'no_coa' => $coa,
                'nm_coa' => $nm_coa,
                'updated_by' => $this->auth->user_id(),
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $update_lab = $this->db->update('kons_master_lab', $data_update, array('id' => $id));
            if (!$update_lab) {
                $this->db->trans_rollback();

                print_r($this->db->last_query());
                exit;
            }

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                $valid = 0;
                $pesan = 'Please try again later !';
            } else {
                $this->db->trans_commit();

                $valid = 1;
                $pesan = 'Data has been updated !';
            }
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_lab($id)
    {
        $this->db->trans_begin();

        $delete_lab = $this->db->update('kons_master_lab', array('deleted_by' => $this->auth->user_id(), 'deleted_date' => date('Y-m-d H:i:s')), array('id' => $id));
        if (!$delete_lab) {
            $this->db->trans_rollback();

            print_r($this->db->last_query());
            exit;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();

            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();

            $valid = 1;
            $pesan = 'Data has been deleted !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function get_data_spec($id)
    {
        $this->db->select('a.id, a.isu_lingkungan, a.peraturan, a.waktu, a.harga_ssc, a.harga_lab, a.no_coa, a.nm_coa');
        $this->db->from('kons_master_lab a');
        $this->db->where('a.id', $id);
        $this->db->where('a.deleted_by IS NULL');
        $get_data = $this->db->get()->row();

        return $get_data;
    }

    public function get_coa_all()
    {
        $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
        $this->gl->from('coa_master a');
        $get_coa = $this->gl->get();

        return $get_coa->result();
    }

    public function get_data_lab()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.id, a.isu_lingkungan, a.peraturan, a.waktu, a.harga_ssc, a.harga_lab, a.no_coa, a.nm_coa');
        $this->db->from('kons_master_lab a');
        $this->db->where('a.deleted_by', null);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.isu_lingkungan', $search['value'], 'both');
            $this->db->or_like('a.peraturan', $search['value'], 'both');
            $this->db->or_like('a.waktu', $search['value'], 'both');
            $this->db->or_like('a.harga_ssc', $search['value'], 'both');
            $this->db->or_like('a.harga_lab', $search['value'], 'both');
            $this->db->group_end();
        }

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->order_by('a.id', 'asc');
        $this->db->limit($length, $start);
        $get_data = $this->db->get();

        $hasil = [];

        $no = (0 + $start);
        foreach ($get_data->result() as $item) {
            $no++;

            $view_btn = '';
            if (has_permission($this->ENABLE_VIEW)) {
                $view_btn = '<button type="button" class="btn-table-action-view view_lab" data-id="' . $item->id . '" title="Lihat Detail"><i class="fa fa-eye"></i> <span>View</span></button>';
            }

            $edit_btn = '';
            if (has_permission($this->ENABLE_MANAGE)) {
                $edit_btn = '<button type="button" class="btn-table-action-edit edit_lab" data-id="' . $item->id . '" title="Edit Data"><i class="fa fa-pencil-square-o"></i> <span>Edit</span></button>';
            }

            $del_btn = '';
            if (has_permission($this->ENABLE_DELETE)) {
                $del_btn = '<button type="button" class="btn-table-action-delete del_lab" data-id="' . $item->id . '" title="Hapus Data"><i class="fa fa-trash-o"></i> <span>Hapus</span></button>';
            }

            $option = '<div class="text-center" style="display: inline-flex; gap: 4px;">' . $view_btn . $edit_btn . $del_btn . '</div>';

            $coa = '<span class="text-muted">-</span>';
            if (!empty($item->no_coa)) {
                $coa = '<span class="label label-info" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-block; background-color: #0284c7;"><i class="fa fa-book"></i> (' . htmlspecialchars($item->no_coa) . ') ' . htmlspecialchars($item->nm_coa) . '</span>';
            }

            $waktu_badge = '<span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 600; padding: 5px 10px; border-radius: 6px; font-size: 12px;"><i class="fa fa-clock-o"></i> ' . htmlspecialchars($item->waktu) . ' Jam</span>';

            $hasil[] = [
                'no' => '<span class="text-muted">' . $no . '</span>',
                'isu_lingkungan' => '<div style="font-weight: 600; color: #1e293b;">' . htmlspecialchars($item->isu_lingkungan) . '</div>',
                'peraturan' => '<div style="color: #475569; font-size: 12px; line-height: 1.4;">' . nl2br(htmlspecialchars($item->peraturan)) . '</div>',
                'waktu' => $waktu_badge,
                'harga_ssc' => '<span style="font-weight: 600; color: #0f766e;">Rp ' . number_format($item->harga_ssc, 0, ',', '.') . '</span>',
                'harga_lab' => '<span style="font-weight: 600; color: #1e40af;">Rp ' . number_format($item->harga_lab, 0, ',', '.') . '</span>',
                'coa' => $coa,
                'option' => $option
            ];
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_all,
            'data' => $hasil
        ]);
    }
}
