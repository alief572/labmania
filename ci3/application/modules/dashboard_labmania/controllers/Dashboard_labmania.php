<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_labmania extends Labmania_Controller
{
    // Hak akses per method (lihat Labmania_Controller::enforce)
    protected $perm_map = [
        'index' => ['dashboard', 'lihat'],
        'kpi_detail' => ['dashboard', 'lihat'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_labmania_model', 'dash_model');
    }

    public function index()
    {
        $this->template->title('Dashboard');
        $this->template->set('breadcrumbs', '<b>Dashboard</b>');

        $kpis = $this->dash_model->get_kpis();
        $upcoming = $this->dash_model->get_upcoming_schedule();
        $todos = $this->dash_model->get_todos();

        $data = [
            'kpis'     => $kpis,
            'upcoming' => $upcoming,
            'todos'    => $todos
        ];

        $this->template->render('index', $data);
    }

    // Isi modal rincian kartu KPI (FR-41), dipanggil AJAX dari index
    public function kpi_detail($k = '')
    {
        if (!in_array($k, ['final', 'pend', 'prof', 'draft'], true)) show_404();
        $this->load->view('kpi_detail', ['k' => $k] + $this->dash_model->get_kpi_detail($k));
    }
}
