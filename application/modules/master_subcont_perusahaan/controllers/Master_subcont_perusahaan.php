<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @author Yunaz
 * @copyright Copyright (c) 2018, Yunaz
 *
 * This is controller for Customer
 */

class Master_subcont_perusahaan extends Admin_Controller
{

    protected $viewPermission     = 'Master_Subcont_Perusahaan.View';
    protected $addPermission      = 'Master_Subcont_Perusahaan.Add';
    protected $managePermission = 'Master_Subcont_Perusahaan.Manage';
    protected $deletePermission = 'Master_Subcont_Perusahaan.Delete';

    protected $gl;

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->template->title('Manage Data Supplier');
        $this->template->page_icon('fa fa-table');
        $this->load->model(array('Master_subcont_perusahaan/Master_subcont_perusahaan_model'));

        date_default_timezone_set("Asia/Bangkok");

        $this->gl = $this->load->database('gl_sendigs', true);
    }

    public function index()
    {
        $get_biaya = $this->db->get_where('kons_master_subcont_perusahaan', ['deleted_by' => null])->result();

        $this->template->title('Master Subcont Perusahaan');
        $this->template->render('index');
    }

    public function add()
    {
        $list_coa = $this->Master_subcont_perusahaan_model->get_coa_all();

        $this->template->set('list_coa', $list_coa);
        $this->template->render('add');
    }

    public function view()
    {
        $id = $this->input->post('id');

        $get_biaya = $this->db->get_where('kons_master_subcont_perusahaan', ['id' => $id])->row();

        $this->template->set('data_biaya', $get_biaya);
        $this->template->render('view');
    }

    public function edit()
    {
        $id = $this->input->post('id');

        $get_biaya = $this->db->get_where('kons_master_subcont_perusahaan', ['id' => $id])->row();

        $list_coa = $this->Master_subcont_perusahaan_model->get_coa_all();

        $this->template->set('list_coa', $list_coa);
        $this->template->set('data_biaya', $get_biaya);
        $this->template->render('edit');
    }

    public function save_biaya()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        if ($post['id'] == '') {

            $get_coa = $this->gl->get_where('coa_master', ['no_perkiraan' => $post['coa']])->row();

            $nm_coa = (!empty($get_coa)) ? $get_coa->nama : '';

            $this->db->insert('kons_master_subcont_perusahaan', [
                'nm_biaya' => $post['nm_biaya'],
                'tipe_biaya' => 1,
                'no_coa' => $post['coa'],
                'nm_coa' => $nm_coa,
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ]);

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                $valid = 0;
                $pesan = 'Sorry, saving data is failed !';
            } else {
                $this->db->trans_commit();

                $valid = 1;
                $pesan = 'Saving data is success !';
            }
        } else {
            $get_coa = $this->gl->get_where('coa_master', ['no_perkiraan' => $post['coa']])->row();

            $nm_coa = (!empty($get_coa)) ? $get_coa->nama : '';
            $this->db->update('kons_master_subcont_perusahaan', [
                'nm_biaya' => $post['nm_biaya'],
                'tipe_biaya' => 1,
                'no_coa' => $post['coa'],
                'nm_coa' => $nm_coa,
                'input_by' => $this->auth->user_id(),
                'input_date' => date('Y-m-d H:i:s')
            ], [
                'id' => $post['id']
            ]);

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                $valid = 0;
                $pesan = 'Sorry, update data is failed !';
            } else {
                $this->db->trans_commit();

                $valid = 1;
                $pesan = 'Update data is success !';
            }
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function del_biaya()
    {
        $id = $this->input->post('id');

        $this->db->trans_begin();

        $this->db->update('kons_master_subcont_perusahaan', ['deleted_by' => $this->auth->user_id(), 'deleted_date' => date('Y-m-d H:i:s')], ['id' => $id]);

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

    public function get_data_biaya()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.id, a.nm_biaya, a.no_coa, a.nm_coa');
        $this->db->from('kons_master_subcont_perusahaan a');
        $this->db->where('a.deleted_by', null);
        if (!empty($search['value'])) {
            $this->db->group_start();
            $this->db->like('a.nm_biaya', $search['value'], 'both');
            $this->db->or_like('a.no_coa', $search['value'], 'both');
            $this->db->or_like('a.nm_coa', $search['value'], 'both');
            $this->db->group_end();
        }

        $db_clone = clone $this->db;
        $count_all = $db_clone->count_all_results();

        $this->db->order_by('a.id', 'desc');
        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $get_data_biaya = $this->db->get();

        $hasil = [];
        $no = $start + 1;

        foreach ($get_data_biaya->result() as $item) {

            $view_btn = '';
            if (has_permission($this->viewPermission)) {
                $view_btn = '<button type="button" class="btn-table-action-view view_biaya_modal" data-id="' . $item->id . '" title="Lihat Detail"><i class="fa fa-eye"></i> <span>View</span></button>';
            }

            $edit_btn = '';
            if (has_permission($this->managePermission)) {
                $edit_btn = '<button type="button" class="btn-table-action-edit edit_biaya_modal" data-id="' . $item->id . '" title="Edit Data"><i class="fa fa-pencil-square-o"></i> <span>Edit</span></button>';
            }

            $del_btn = '';
            if (has_permission($this->deletePermission)) {
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
            'recordsTotal' => $count_all,
            'recordsFiltered' => $count_all,
            'data' => $hasil
        ]);
    }
}
