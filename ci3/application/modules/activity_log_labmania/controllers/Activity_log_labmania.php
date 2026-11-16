<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Activity_log_labmania extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['activity_log', 'lihat'],
        'detail' => ['activity_log', 'lihat', true],
    ];

    public function __construct()
    {
        parent::__construct();
        // LOG-FR-09: hanya group Manajemen / Admin Developer, diperiksa di server
        if (!can_view_activity_log()) {
            show_error('Anda tidak memiliki akses ke Activity Log.', 403, 'Akses ditolak');
        }
        $this->load->model('Activity_log_labmania_model', 'log_model');
    }

    public function index()
    {
        $this->template->title('Activity Log');
        $this->template->set('breadcrumbs', '<b>Activity Log</b>');

        $filters = [
            'module'  => $this->input->get('module') ?: 'all',
            'action'  => $this->input->get('action') ?: 'all',
            'from'    => $this->input->get('from') ?: '',
            'to'      => $this->input->get('to') ?: '',
            'keyword' => $this->input->get('q') ?: '',
            'ref_table' => $this->input->get('ref_table') ?: '',
            'ref_no'  => $this->input->get('ref_no') ?: ''
        ];

        $logs = $this->log_model->get_logs($filters, 100);
        $modules = $this->log_model->get_modules();
        $actions = $this->log_model->get_actions();

        $data = [
            'filters' => $filters,
            'logs'    => $logs,
            'modules' => $modules,
            'actions' => $actions
        ];

        $this->template->render('index', $data);
    }

    public function detail($id)
    {
        $log = $this->log_model->get_log_by_id($id);
        if (!$log) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'msg' => 'Log tidak ditemukan']));
        }

        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'status' => 1,
            'data'   => $log
        ]));
    }
}
