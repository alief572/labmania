<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Wilayah extends CI_Controller
{
    private $cache_dir;

    public function __construct()
    {
        parent::__construct();
        $this->cache_dir = APPPATH . 'cache/wilayah/';
        if (!is_dir($this->cache_dir)) {
            @mkdir($this->cache_dir, 0777, true);
        }
    }

    /**
     * Ambil daftar seluruh Provinsi di Indonesia dari https://wilayah.id/
     */
    public function provinces()
    {
        $cache_file = $this->cache_dir . 'provinces.json';
        
        // Cache selama 14 hari
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 86400 * 14)) {
            $data = json_decode(file_get_contents($cache_file), true);
        } else {
            $url = 'https://wilayah.id/api/provinces.json';
            $response = $this->_fetch($url);
            if ($response && !empty($response['data'])) {
                @file_put_contents($cache_file, json_encode($response));
                $data = $response;
            } else if (file_exists($cache_file)) {
                $data = json_decode(file_get_contents($cache_file), true);
            } else {
                // Fallback default provinsi utama bila offline
                $data = [
                    'data' => [
                        ['code' => '31', 'name' => 'DKI JAKARTA'],
                        ['code' => '32', 'name' => 'JAWA BARAT'],
                        ['code' => '36', 'name' => 'BANTEN'],
                        ['code' => '33', 'name' => 'JAWA TENGAH'],
                        ['code' => '34', 'name' => 'DI YOGYAKARTA'],
                        ['code' => '35', 'name' => 'JAWA TIMUR'],
                    ]
                ];
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 1,
                'data'   => isset($data['data']) ? $data['data'] : []
            ]));
    }

    /**
     * Ambil daftar Kabupaten/Kota berdasarkan kode provinsi dari https://wilayah.id/
     */
    public function regencies($prov_code = '')
    {
        $prov_code = trim((string)$prov_code);
        if (empty($prov_code)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 0, 'data' => []]));
        }

        $cache_file = $this->cache_dir . 'regencies_' . $prov_code . '.json';

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 86400 * 14)) {
            $data = json_decode(file_get_contents($cache_file), true);
        } else {
            $url = 'https://wilayah.id/api/regencies/' . urlencode($prov_code) . '.json';
            $response = $this->_fetch($url);
            if ($response && !empty($response['data'])) {
                @file_put_contents($cache_file, json_encode($response));
                $data = $response;
            } else if (file_exists($cache_file)) {
                $data = json_decode(file_get_contents($cache_file), true);
            } else {
                $data = ['data' => []];
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 1,
                'data'   => isset($data['data']) ? $data['data'] : []
            ]));
    }

    private function _fetch($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Labmania-ERP/1.0');
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated in PHP 8.5 as CurlHandle auto-closes


        if ($http_code === 200 && $result) {
            return json_decode($result, true);
        }
        return null;
    }
}
