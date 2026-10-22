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
class Monitoring_expense extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Expense_Report_Project.View';
    protected $addPermission      = 'Expense_Report_Project.Add';
    protected $managePermission = 'Expense_Report_Project.Manage';
    protected $deletePermission = 'Expense_Report_Project.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Expense Report Project');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Monitoring_expense/Monitoring_expense_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    // View Page Function

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Expense Report Project');
        $this->template->render('index');
    }

    public function get_data_expense() {
        $this->Monitoring_expense_model->get_data_expense();
    }

    // End Update Data Function
}
