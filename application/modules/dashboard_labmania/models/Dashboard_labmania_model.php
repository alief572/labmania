<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_labmania_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('penjualan_program/penjualan_program_model', 'pj_model');
    }

    /**
     * Penjualan Program (selain Batal) + hasil costing yang sama dengan form Penjualan Program.
     * Kunci hasil: semua kolom penjualan_program + nama_paket, nama_company, c (calculate_costing).
     */
    private function pj_with_calc(array $statuses)
    {
        $rows = $this->db->select('pj.*, pp.judul as nama_paket, c.name_customer as nama_company')
            ->from('penjualan_program pj')
            ->join('master_program mp', 'pj.id_master_program = mp.id', 'left')
            ->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left')
            ->join('master_customer c', 'pj.id_customer = c.id_customer', 'left')
            ->where_in('pj.status', $statuses)
            ->order_by('pj.tgl_mulai', 'ASC')
            ->get()->result_array();

        foreach ($rows as &$r) {
            $biaya = $this->db->select('b.*, s.nama as nama_satuan')
                ->from('penjualan_program_biaya b')
                ->join('ms_satuan s', 'b.id_satuan = s.id', 'left')
                ->where('b.id_penjualan', $r['id'])
                ->get()->result_array();
            $r['c'] = $this->pj_model->calculate_costing($r['durasi_hari'], $r['target_peserta'], $r['harga_jual_peserta'], $biaya);
        }
        return $rows;
    }

    private function draft_mp()
    {
        return $this->db->select('mp.*, pp.judul as nama_paket')
            ->from('master_program mp')
            ->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left')
            ->where('mp.status', 'draft')->get()->result_array();
    }

    public function get_kpis()
    {
        $finals = $this->pj_with_calc(['final']);
        $k = ['total_final' => count($finals), 'public_count' => 0, 'b2b_count' => 0,
              'total_pendapatan' => 0, 'total_profit' => 0, 'total_peserta' => 0];

        foreach ($finals as $f) {
            $k[$f['jenis'] === 'b2b' ? 'b2b_count' : 'public_count']++;
            $k['total_pendapatan'] += $f['c']['pendapatan'];
            $k['total_profit']     += $f['c']['profit'];
            $k['total_peserta']    += (int)$f['target_peserta'];
        }
        $k['margin'] = $k['total_pendapatan'] > 0 ? round($k['total_profit'] / $k['total_pendapatan'] * 100, 1) : 0;
        $k['total_draft'] = count($this->draft_mp()) + count($this->pj_with_calc(['draft']));
        return $k;
    }

    // Rincian satu kartu KPI: 'final' | 'pend' | 'prof' | 'draft'
    public function get_kpi_detail($k)
    {
        if ($k === 'draft') {
            return ['mp' => $this->draft_mp(), 'pj' => $this->pj_with_calc(['draft'])];
        }
        return ['finals' => $this->pj_with_calc(['final'])];
    }

    public function get_upcoming_schedule()
    {
        $this->db->select('pj.*, pp.judul as nama_paket, c.name_customer as nama_company');
        $this->db->from('penjualan_program pj');
        $this->db->join('master_program mp', 'pj.id_master_program = mp.id', 'left');
        $this->db->join('paket_produk pp', 'mp.id_paket_produk = pp.id', 'left');
        $this->db->join('master_customer c', 'pj.id_customer = c.id_customer', 'left');
        $this->db->where('pj.status', 'final');
        $this->db->where('pj.tgl_selesai >=', date('Y-m-d'));
        $this->db->order_by('pj.tgl_mulai', 'ASC');
        $this->db->limit(5);
        return $this->db->get()->result_array();
    }

    // Perlu tindakan: Draft, rugi, dan titik impas di atas rencana. Tiap item: type (mp|pj), id, nomor, judul, msg, is_warn
    public function get_todos()
    {
        $todos = [];
        foreach ($this->draft_mp() as $d) {
            $todos[] = ['type' => 'mp', 'id' => $d['id'], 'nomor' => $d['nomor'], 'judul' => $d['nama_paket'],
                'msg' => 'Master Program masih Draft — finalkan agar bisa dijual', 'is_warn' => false];
        }
        foreach ($this->pj_with_calc(['draft', 'final']) as $p) {
            $c = $p['c'];
            $base = ['type' => 'pj', 'id' => $p['id'], 'nomor' => $p['nomor'], 'judul' => $p['nama_paket']];
            if ($p['status'] === 'draft') {
                $todos[] = $base + ['msg' => 'Penjualan Program masih Draft', 'is_warn' => false];
            }
            if ($c['pendapatan'] && $c['profit'] < 0) {
                $todos[] = $base + ['msg' => 'Rugi Rp ' . number_format(abs($c['profit']), 0, ',', '.'), 'is_warn' => true];
            } elseif ($c['bep_peserta'] === null || $c['bep_peserta'] > (int)$p['target_peserta']) {
                $todos[] = $base + ['msg' => $c['bep_peserta'] === null
                    ? 'Titik impas tidak tercapai'
                    : 'Titik impas ' . $c['bep_peserta'] . ' peserta, rencana ' . (int)$p['target_peserta'], 'is_warn' => true];
            }
        }
        return $todos;
    }
}
