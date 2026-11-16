<?php defined('BASEPATH') || exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Labmania_Controller extends Admin_Controller
{
    /**
     * Peta hak akses per method: 'method' => [kode menu, aksi, (true = respons JSON)].
     *  - aksi 'simpan' dibaca sebagai 'ubah' bila POST berisi id, selain itu 'tambah'.
     *  - '*' = berlaku untuk method yang tidak tercantum.
     * Method yang tidak tercantum dan tanpa '*' ditolak (fail closed).
     */
    protected $perm_map = [];

    public function __construct()
    {
        parent::__construct();
        $this->template->set_theme('labmania');
        $this->template->set_layout('default');

        $this->load->helper('akses');
        $this->enforce();
    }

    protected function enforce()
    {
        $method = $this->router->fetch_method();
        $rule = $this->perm_map[$method] ?? ($this->perm_map['*'] ?? null);
        if ($rule === null) $this->deny(false);

        $kode = str_replace('{seg3}', (string)$this->uri->segment(3), $rule[0]); // mis. master_data/attr_data/{tipe}
        $aksi = $rule[1];
        if ($aksi === 'simpan') $aksi = $this->input->post('id') ? 'ubah' : 'tambah';
        if (!lm_can($kode, $aksi)) $this->deny(!empty($rule[2]));
    }

    // 403: JSON untuk endpoint AJAX, halaman galat untuk halaman biasa
    protected function deny($json)
    {
        if ($json) {
            $this->output->set_status_header(403)->set_content_type('application/json')
                ->set_output(json_encode(['status' => 0, 'msg' => 'Anda tidak memiliki izin untuk aksi ini']));
            $this->output->_display();
            exit;
        }
        show_error('Anda tidak memiliki izin untuk mengakses halaman ini.', 403, 'Akses ditolak');
    }
}
