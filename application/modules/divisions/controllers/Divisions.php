<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Divisions extends Admin_Controller
{
	protected $viewPermission   = 'Master_Department.View';
	protected $addPermission    = 'Master_Department.Add';
	protected $managePermission = 'Master_Department.Manage';
	protected $deletePermission = 'Master_Department.Delete';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Divisions/master_model');
		$this->load->model('Divisions/divisions_model');
	}

	public function index()
	{
		$this->auth->restrict($this->viewPermission);
		$data = array(
			'title'			=> 'Indeks Of Divisions',
			'action'		=> 'index'
		);
		$this->template->render('index', $data);
	}
	public function add()
	{
		if ($this->input->post()) {
			$Arr_Kembali			= array();
			$data					= $this->input->post();
			$data['id']				= $this->master_model->code_otomatis('divisions', 'DIV');
			$data_session			= $this->session->userdata;
			$data['created_by']		= $data_session['User']['username'];
			$data['created']		= date('Y-m-d H:i:s');
			if ($this->master_model->simpan('divisions', $data)) {
				$Arr_Kembali		= array(
					'status'		=> 1,
					'pesan'			=> 'Add Divisions Success. Thank you & have a nice day.......'
				);
				history('Add Data Divisions' . $data['name']);
			} else {
				$Arr_Kembali		= array(
					'status'		=> 2,
					'pesan'			=> 'Add Divisions failed. Please try again later......'
				);
			}
			echo json_encode($Arr_Kembali);
		} else {
			$controller			= ucfirst(strtolower($this->uri->segment(1)));
			$Arr_Akses			= getAcccesmenu($controller);
			if ($Arr_Akses['create'] != '1') {
				$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
				redirect(site_url('menu'));
			}
			$arr_Where			= '';
			$get_Data			= $this->master_model->getCompanies($arr_Where);
			$data = array(
				'title'			=> 'Add Divisions',
				'action'		=> 'add',
				'data_companies' => $get_Data
			);
			$this->load->view('Divisions/add', $data);
		}
	}
	public function edit($id = '')
	{
		if ($this->input->post()) {
			//echo"<pre>";print_r($this->input->post());exit;
			$data					= $this->input->post();
			$Arr_Kembali			= array();
			unset($data['id']);
			$data_session			= $this->session->userdata;
			$data['modified_by']	= $data_session['User']['username'];
			$data['modified']		= date('Y-m-d H:i:s');
			if ($this->master_model->getUpdate('divisions', $data, 'id', $this->input->post('id'))) {
				$Arr_Kembali		= array(
					'status'		=> 1,
					'pesan'			=> 'Edit Divisions Success. Thank you & have a nice day.......'
				);
				history('Edit Data Divisions' . $data['name']);
			} else {
				$Arr_Kembali		= array(
					'status'		=> 2,
					'pesan'			=> 'Add Departement failed. Please try again later......'
				);
			}
			echo json_encode($Arr_Kembali);
		} else {
			$controller			= ucfirst(strtolower($this->uri->segment(1)));
			$Arr_Akses			= getAcccesmenu($controller);
			if ($Arr_Akses['update'] != '1') {
				$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
				redirect(site_url('divisions'));
			}
			$arr_Where			= '';
			$get_Data			= $this->master_model->getCompanies($arr_Where);

			$detail				= $this->master_model->getData('hr_sentral.divisions', 'id', $id);
			$data = array(
				'title'			=> 'Edit Divisions',
				'action'		=> 'edit',
				'data_companies' => $get_Data,
				'row'			=> $detail
			);

			$this->load->view('Divisions/edit', $data);
		}
	}

	function delete($id)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['delete'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('divisions'));
		}

		$this->db->where('id', $id);
		$this->db->delete("divisions");
		if ($this->db->affected_rows() > 0) {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-success\" id=\"flash-message\">Data has been successfully deleted...........!!</div>");
			history('Delete Data divisions id' . $id);
			redirect(site_url('divisions'));
		}
	}

	public function view($id = '')
	{
		$this->auth->restrict($this->viewPermission);
		$arr_Where			= '';
		$get_Data			= $this->master_model->getCompanies($arr_Where);
		$detail				= $this->master_model->getData('hr_sentral.divisions', 'id', $id);
		$data = array(
			'title'			=> 'Edit Divisions',
			'action'		=> 'edit',
			'data_companies' => $get_Data,
			'row'			=> $detail
		);

		$this->template->render('view', $data);
	}

	public function get_data_divisions()
	{
		$this->divisions_model->get_data_divisions();
	}
}
