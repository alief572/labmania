<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Activity_log_labmania_model extends CI_Model
{
    public function get_logs($filters = [], $limit = 50, $offset = 0)
    {
        $this->db->select('l.*, u.nm_lengkap, u.username');
        $this->db->from('logs l');
        $this->db->join('users u', 'l.created_by = u.id_user', 'left');

        if (!empty($filters['module']) && $filters['module'] !== 'all') {
            $this->db->where('l.module', $filters['module']);
        }

        if (!empty($filters['action']) && $filters['action'] !== 'all') {
            $this->db->where('l.action', $filters['action']);
        }

        if (!empty($filters['ref_table']) && !empty($filters['ref_no'])) {
            $this->db->where('l.ref_table', $filters['ref_table']);
            $this->db->where('l.ref_no', $filters['ref_no']);
        }

        if (!empty($filters['from'])) {
            $this->db->where('DATE(l.created_date) >=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $this->db->where('DATE(l.created_date) <=', $filters['to']);
        }

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $this->db->group_start();
            $this->db->like('l.ref_no', $kw);
            $this->db->or_like('l.description', $kw);
            $this->db->or_like('u.nm_lengkap', $kw);
            $this->db->or_like('u.username', $kw);
            $this->db->group_end();
        }

        $this->db->order_by('l.id', 'DESC');
        $this->db->limit($limit, $offset);
        $logs = $this->db->get()->result_array();

        foreach ($logs as &$log) {
            $log['parsed_diff'] = $this->parse_diff($log['data']);
        }

        return $logs;
    }

    public function get_log_by_id($id)
    {
        $this->db->select('l.*, u.nm_lengkap, u.username');
        $this->db->from('logs l');
        $this->db->join('users u', 'l.created_by = u.id_user', 'left');
        $this->db->where('l.id', $id);
        $log = $this->db->get()->row_array();

        if ($log) {
            $log['parsed_diff'] = $this->parse_diff($log['data']);
        }

        return $log;
    }

    public function get_modules()
    {
        return $this->db->distinct()->select('module')->from('logs')->order_by('module', 'ASC')->get()->result_array();
    }

    public function get_actions()
    {
        return $this->db->distinct()->select('action')->from('logs')->order_by('action', 'ASC')->get()->result_array();
    }

    private function parse_diff($jsonData)
    {
        if (empty($jsonData)) return [];

        $data = is_string($jsonData) ? json_decode($jsonData, true) : $jsonData;
        if (!is_array($data)) return [];

        $diffs = [];

        // Format 1: Pasangan old vs new
        if (isset($data['old']) && isset($data['new']) && is_array($data['old']) && is_array($data['new'])) {
            $old = $data['old'];
            $new = $data['new'];
            $ignored = ['created_date', 'created_by', 'updated_date', 'updated_by', 'deleted_date', 'deleted_by'];

            foreach ($new as $key => $newVal) {
                if (in_array($key, $ignored)) continue;
                $oldVal = $old[$key] ?? null;

                // Smart decimal & null comparison
                if ($this->is_different($oldVal, $newVal)) {
                    $diffs[] = [
                        'field' => $key,
                        'old'   => is_array($oldVal) ? json_encode($oldVal) : (string)$oldVal,
                        'new'   => is_array($newVal) ? json_encode($newVal) : (string)$newVal
                    ];
                }
            }
        } 
        // Format 2: Data baru (Create)
        else {
            $ignored = ['created_date', 'created_by'];
            foreach ($data as $key => $val) {
                if (in_array($key, $ignored) || $key === 'detail_changes') continue;
                $diffs[] = [
                    'field' => $key,
                    'old'   => '—',
                    'new'   => is_array($val) ? json_encode($val) : (string)$val
                ];
            }
        }

        // Perubahan baris detail / izin: ['Label' => ['old' => ..., 'new' => ...]]
        if (!empty($data['detail_changes']) && is_array($data['detail_changes'])) {
            foreach ($data['detail_changes'] as $label => $c) {
                $diffs[] = ['field' => $label, 'old' => (string)($c['old'] ?? ''), 'new' => (string)($c['new'] ?? '')];
            }
        }

        return $diffs;
    }

    private function is_different($val1, $val2)
    {
        if (is_numeric($val1) && is_numeric($val2)) {
            return (float)$val1 != (float)$val2;
        }
        if ((is_null($val1) || $val1 === '' || $val1 === 0 || $val1 === '0') && 
            (is_null($val2) || $val2 === '' || $val2 === 0 || $val2 === '0')) {
            return false;
        }
        return $val1 !== $val2;
    }
}
