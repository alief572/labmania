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
class Master_lab extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Master_Lab.View';
    protected $addPermission      = 'Master_Lab.Add';
    protected $managePermission = 'Master_Lab.Manage';
    protected $deletePermission = 'Master_Lab.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Master Lab');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Master_lab/Master_lab_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Master Lab');
        $this->template->render('index');
    }

    public function add_data()
    {
        $list_coa = $this->Master_lab_model->get_coa_all();

        $this->template->set('list_coa', $list_coa);
        $this->template->render('add_data_lab');
    }

    public function view_lab()
    {
        $id = $this->input->post('id');

        $get_data_lab = $this->Master_lab_model->get_data_spec($id);

        $list_coa = $this->Master_lab_model->get_coa_all();

        $this->template->set('data_lab', $get_data_lab);
        $this->template->set('list_coa', $list_coa);
        $this->template->render('view_data_lab');
    }

    public function edit_lab()
    {
        $id = $this->input->post('id');

        $get_data_lab = $this->Master_lab_model->get_data_spec($id);

        $list_coa = $this->Master_lab_model->get_coa_all();

        $this->template->set('data_lab', $get_data_lab);
        $this->template->set('list_coa', $list_coa);
        $this->template->render('edit_data_lab');
    }

    public function del_lab()
    {
        $id = $this->input->post('id');

        $this->Master_lab_model->del_lab($id);
    }

    public function save_lab()
    {
        $this->Master_lab_model->save_lab();
    }

    public function get_data_lab()
    {
        $this->Master_lab_model->get_data_lab();
    }
}
