<?php

/**
 * ------------------------------------------------------------------------
 * Class Name : Master Biaya
 * ------------------------------------------------------------------------
 *
 * @author     DandoRidwanto
 * @copyright  2018
 *
 * Last Update : Monday, 23 June 2018
 *
 */
// awd
class Master_biaya extends Admin_Controller
{
    protected $viewPermission     = 'Master_Biaya.View';
    protected $addPermission      = 'Master_Biaya.Add';
    protected $managePermission = 'Master_Biaya.Manage';
    protected $deletePermission = 'Master_Biaya.Delete';

    protected $gl;

    function __construct()
    {
        parent::__construct();
        $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->output->set_header("Pragma: no-cache");

        $this->load->model(array('Master_biaya/Master_biaya_model'));
        $this->gl = $this->load->database('gl_sendigs', true);
    }

    public function index()
    {

        $get_biaya = $this->db->get_where('kons_master_biaya', ['deleted_by' => null])->result();

        $this->template->title('Master Biaya');
        $this->template->render('index');
    }

    public function add()
    {
        $get_coa = $this->Master_biaya_model->get_coa_all();

        $this->template->set('list_coa', $get_coa);
        $this->template->render('add');
    }

    public function edit()
    {
        $id = $this->input->post('id');

        $get_biaya = $this->db->get_where('kons_master_biaya', ['id' => $id])->row();
        $get_coa = $this->Master_biaya_model->get_coa_all();

        $this->template->set('data_biaya', $get_biaya);
        $this->template->set('list_coa', $get_coa);
        $this->template->render('edit');
    }

    public function save_biaya()
    {
        $post = $this->input->post();

        $get_coa = $this->gl->get_where('coa_master', ['no_perkiraan' => $post['coa']])->row();

        $nm_coa = (isset($get_coa) && !empty($get_coa)) ? $get_coa->nama : '';

        $this->db->trans_begin();

        if ($post['id'] == '') {
            $this->db->insert('kons_master_biaya', [
                'nm_biaya' => $post['nm_biaya'],
                'tipe_biaya' => $post['tipe_biaya'],
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
            $this->db->update('kons_master_biaya', [
                'nm_biaya' => $post['nm_biaya'],
                'tipe_biaya' => $post['tipe_biaya'],
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

    public function get_data_biaya()
    {
        $this->Master_biaya_model->get_data_biaya();
    }

    public function del_biaya()
    {
        $id = $this->input->post('id');

        $this->db->trans_begin();

        $this->db->update('kons_master_biaya', ['deleted_by' => $this->auth->user_id(), 'deleted_date' => date('Y-m-d H:i:s')], ['id' => $id]);

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
}
