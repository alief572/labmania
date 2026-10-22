<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Penagihan extends Admin_Controller
{
    protected $viewPermission     = 'Penagihan.View';
    protected $addPermission      = 'Penagihan.Add';
    protected $managePermission = 'Penagihan.Manage';
    protected $deletePermission = 'Penagihan.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Penagihan/Penagihan_model'
        ));
        $this->template->title('Penagihan');
        $this->template->page_icon('fa fa-building-o');

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->title('Penagihan');
        $this->template->render('index');
    }
}
