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
class Consultation_report extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Consultation_Report.View';
    protected $addPermission      = 'Consultation_Report.Add';
    protected $managePermission = 'Consultation_Report.Manage';
    protected $deletePermission = 'Consultation_Report.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Consultation Report');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Consultation_report/Consultation_report_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Consultation Report');
        $this->template->render('index');
    }

    public function add($id_spk_penawaran) {

        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        $get_spk_penawaran_detail = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $data = [
            'header' => $get_spk_penawaran,
            'detail' => $get_spk_penawaran_detail
        ];

        $this->auth->restrict($this->viewPermission);
        $this->template->title('Consultation Report');
        $this->template->set($data);
        $this->template->render('add');
    }

    public function add_detail($id){
        $get_detail = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id' => $id])->row();
        $get_header = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $get_detail->id_spk_penawaran])->row();

        $data = [
            'header' => $get_header,
            'detail' => $get_detail
        ];

        $this->template->set($data);
        $this->template->render('add_detail');
    }

    public function save_cons() {
        $post = $this->input->post();

        $data_header = [];
        if(isset($post['activity_detail'])) {
            foreach($post['activity_detail'] as $item) {
                if($item['tanggal'] !== '' && $item['time_from'] !== '' && $item['time_to'] !== '' && $item['pic'] !== '') {
                    $data_header[] = [
                        'id_spk_penawaran' => $item['id_spk_penawaran'],
                        'id_detail' => $item['id'],
                        'no_mandays' => $item['no_mandays'],
                        'due_date' => $item['tanggal'],
                        'due_date_time_from' => $item['time_from'],
                        'due_date_time_to' => $item['time_to'],
                        'pic' => $item['pic'],
                        'created_by' => $this->auth->user_id(),
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        $data_detail = [];
        
    }

    public function get_data_spk() {
        $this->Consultation_report_model->get_data_spk();
    }
}
