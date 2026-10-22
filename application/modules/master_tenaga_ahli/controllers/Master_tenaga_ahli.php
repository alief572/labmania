<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @author Yunaz
 * @copyright Copyright (c) 2018, Yunaz
 *
 * This is controller for Customer
 */

class Master_tenaga_ahli extends Admin_Controller
{

    protected $viewPermission     = 'Master_Tenaga_Ahli.View';
    protected $addPermission      = 'Master_Tenaga_Ahli.Add';
    protected $managePermission = 'Master_Tenaga_Ahli.Manage';
    protected $deletePermission = 'Master_Tenaga_Ahli.Delete';

    protected $gl;

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->template->title('Manage Data Supplier');
        $this->template->page_icon('fa fa-table');

        $this->load->model(array('Master_tenaga_ahli/Master_tenaga_ahli_model'));

        $this->gl = $this->load->database('gl_sendigs', true);

        date_default_timezone_set("Asia/Bangkok");
    }

    public function index()
    {
        $get_biaya = $this->db->get_where('kons_master_tenaga_ahli', ['deleted_by' => null])->result();

        $this->template->title('Master Tenaga Ahli');
        $this->template->render('index');
    }

    public function add()
    {
        $get_coa = $this->Master_tenaga_ahli_model->get_coa_all();

        $this->template->set('list_coa', $get_coa);
        $this->template->render('add');
    }

    public function view()
    {
        $id = $this->input->post('id');

        $get_biaya = $this->db->get_where('kons_master_tenaga_ahli', ['id' => $id])->row();

        $this->template->set('data_biaya', $get_biaya);
        $this->template->render('view');
    }

    public function edit()
    {
        $id = $this->input->post('id');

        $get_biaya = $this->db->get_where('kons_master_tenaga_ahli', ['id' => $id])->row();
        $get_coa = $this->Master_tenaga_ahli_model->get_coa_all();

        $this->template->set('list_coa', $get_coa);
        $this->template->set('data_biaya', $get_biaya);
        $this->template->render('edit');
    }

    public function save_biaya()
    {
        $post = $this->input->post();

        $get_coa = $this->gl->get_where('coa_master', ['no_perkiraan' => $post['coa']])->row();

        $nm_coa = (isset($get_coa) && !empty($get_coa)) ? $get_coa->nama : '';

        $this->db->trans_begin();

        if ($post['id'] == '') {
            $this->db->insert('kons_master_tenaga_ahli', [
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
            $this->db->update('kons_master_tenaga_ahli', [
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

        $this->db->update('kons_master_tenaga_ahli', ['deleted_by' => $this->auth->user_id(), 'deleted_date' => date('Y-m-d H:i:s')], ['id' => $id]);

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
        $this->Master_tenaga_ahli_model->get_data_biaya();
    }
}
