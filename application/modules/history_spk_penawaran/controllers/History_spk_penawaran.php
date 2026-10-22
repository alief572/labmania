<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class History_spk_penawaran extends Admin_Controller
{
    protected $viewPermission     = 'History_SPK_Penawaran.View';
    protected $addPermission      = 'History_SPK_Penawaran.Add';
    protected $managePermission = 'History_SPK_Penawaran.Manage';
    protected $deletePermission = 'History_SPK_Penawaran.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('History SPK Penawaran');
        $this->template->page_icon('fa fa-file-text');
        $this->load->library('upload');
        $this->load->model('history_spk_penawaran/History_spk_penawaran_model');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('History SPK Penawaran List');
        $this->template->render('index');
    }

    public function get_data()
    {
        try {
            $this->auth->restrict($this->viewPermission);

            $draw = $this->input->post('draw');
            $start = $this->input->post('start');
            $length = $this->input->post('length');
            $search = $this->input->post('search');

            $search_value = !empty($search['value']) ? $search['value'] : null;

            $result = $this->History_spk_penawaran_model->get_data_history($search_value, $start, $length);

            echo json_encode([
                'draw' => intval($draw),
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data' => $result['data']
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function view_spk($id_history)
    {
        $this->auth->restrict($this->viewPermission);

        $id_history = urldecode($id_history);
        $id_history = str_replace('|', '/', $id_history);

        $get_spk_history = $this->db->get_where('kons_tr_spk_penawaran_history', ['id_history' => $id_history])->row();

        if (!$get_spk_history) {
            show_404();
        }

        $this->db->select('a.*, COALESCE(b.nm_aktifitas, a.nm_aktifitas) as nama_aktifitas');
        $this->db->from('kons_tr_spk_aktifitas_history a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_spk_penawaran', $get_spk_history->id_spk_penawaran);
        $this->db->where('a.id_history', $id_history);
        $this->db->order_by('a.id', 'asc');
        $get_list_aktifitas = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran_subcont_history a');
        $this->db->where('a.id_spk_penawaran', $get_spk_history->id_spk_penawaran);
        $this->db->where('a.id_history', $id_history);
        $this->db->order_by('a.id', 'asc');
        $get_list_subcont = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran_payment_history a');
        $this->db->where('a.id_spk_penawaran', $get_spk_history->id_spk_penawaran);
        $this->db->where('a.id_history', $id_history);
        $this->db->order_by('a.id', 'asc');
        $get_list_payment = $this->db->get()->result();

        $data = [
            'header' => $get_spk_history,
            'list_aktifitas' => $get_list_aktifitas,
            'list_subcont' => $get_list_subcont,
            'list_payment' => $get_list_payment
        ];

        $this->template->title('View SPK Penawaran - History');
        $this->template->set($data);
        $this->template->render('view_spk');
    }
}