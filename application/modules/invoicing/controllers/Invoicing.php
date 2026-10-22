<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Invoicing extends Admin_Controller
{
    protected $viewPermission     = 'Invoicing.View';
    protected $addPermission      = 'Invoicing.Add';
    protected $managePermission = 'Invoicing.Manage';
    protected $deletePermission = 'Invoicing.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Invoicing/Invoicing_model'
        ));
        $this->template->title('Invoicing');
        $this->template->page_icon('fa fa-building-o');

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('Invoicing');
        $this->template->render('index');
    }

    public function get_data_spk()
    {
        $this->Invoicing_model->get_data_spk();
    }
}
