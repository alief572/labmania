<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Helper Standar Audit Log & Activity Log
 * Sesuai aturan AGENTS.md
 */

if (!function_exists('write_log')) {
    function write_log($module, $action, $description, $data = null, $ref_table = null, $ref_id = null, $ref_no = null)
    {
        $ci = &get_instance();
        if (!isset($ci->db)) {
            $ci->load->database();
        }

        $userId = 1;
        if (isset($ci->session)) {
            $appSession = $ci->session->userdata('app_session');
            if (!empty($appSession['id_user'])) {
                $userId = (int)$appSession['id_user'];
            }
        }

        $ipAddress = $ci->input->ip_address();

        // Bersihkan dan normalisasi data diff jika berupa array
        $finalData = null;
        if (!empty($data) && is_array($data)) {
            $finalData = clean_log_data($data);
        } else if (!empty($data) && is_string($data)) {
            $finalData = $data;
        }

        $insertData = [
            'module'       => $module,
            'sub_module'   => null,
            'action'       => $action,
            'description'  => $description,
            'ref_table'    => $ref_table,
            'ref_id'       => $ref_id ? (int)$ref_id : null,
            'ref_no'       => $ref_no,
            'data'         => $finalData ? (is_array($finalData) ? json_encode($finalData, JSON_UNESCAPED_UNICODE) : $finalData) : null,
            'created_by'   => $userId,
            'created_date' => date('Y-m-d H:i:s'),
            'ip_address'   => $ipAddress
        ];

        return $ci->db->insert('logs', $insertData);
    }
}

/**
 * Normalisasi data untuk Smart Comparison (mengabaikan metadata tanggal, normalisasi desimal & NULL vs 0)
 */
if (!function_exists('clean_log_data')) {
    function clean_log_data(array $payload)
    {
        $ignoreFields = ['created_at', 'created_date', 'created_by', 'updated_at', 'updated_date', 'updated_by', 'deleted_at', 'deleted_date', 'deleted_by'];

        if (isset($payload['old']) && isset($payload['new']) && is_array($payload['old']) && is_array($payload['new'])) {
            $oldDiff = [];
            $newDiff = [];

            $allKeys = array_unique(array_merge(array_keys($payload['old']), array_keys($payload['new'])));

            foreach ($allKeys as $key) {
                if (in_array(strtolower($key), $ignoreFields)) {
                    continue;
                }

                $oldVal = isset($payload['old'][$key]) ? $payload['old'][$key] : null;
                $newVal = isset($payload['new'][$key]) ? $payload['new'][$key] : null;

                // Smart Comparison: normalisasi nilai
                if (is_value_equal($oldVal, $newVal, $key)) {
                    continue;
                }

                $oldDiff[$key] = $oldVal;
                $newDiff[$key] = $newVal;
            }

            $result = [
                'old' => $oldDiff,
                'new' => $newDiff
            ];

            if (isset($payload['detail_changes']) && is_array($payload['detail_changes'])) {
                $result['detail_changes'] = $payload['detail_changes'];
            }

            return $result;
        }

        return $payload;
    }
}

/**
 * Smart equality check untuk mendeteksi perubahan substansial
 */
if (!function_exists('is_value_equal')) {
    function is_value_equal($v1, $v2, $key = '')
    {
        if ($v1 === $v2) return true;

        // Toleransi NULL / empty string vs 0 untuk kolom angka/nominal
        $numericKeywords = ['diskon', 'harga', 'total', 'qty', 'ppn', 'kurs', 'cost', 'biaya', 'profit', 'target', 'tarif'];
        $isNumericField = false;
        foreach ($numericKeywords as $kw) {
            if (stripos($key, $kw) !== false) {
                $isNumericField = true;
                break;
            }
        }

        if ($isNumericField) {
            $num1 = ($v1 === null || $v1 === '') ? 0 : (float)$v1;
            $num2 = ($v2 === null || $v2 === '') ? 0 : (float)$v2;
            if (abs($num1 - $num2) < 0.0001) {
                return true;
            }
        }

        // Toleransi desimal (0.00 vs 0)
        if (is_numeric($v1) && is_numeric($v2)) {
            if (abs((float)$v1 - (float)$v2) < 0.0001) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Hak akses Activity Log & "Lihat Riwayat" (LOG-FR-09): izin 'Lihat' pada menu Activity Log
 * (Permission Group). Seed awal: Manajemen dan Admin Developer; group 'admin' lolos semua.
 */
if (!function_exists('can_view_activity_log')) {
    function can_view_activity_log()
    {
        $ci = &get_instance();
        $ci->load->helper('akses');
        return lm_can('activity_log', 'lihat');
    }
}

/** Sel kolom "Dibuat / Diubah" (LOG-FR-02) dari kolom jejak yang dilekatkan Master_data_model::with_audit(). */
if (!function_exists('hist_cell')) {
    function hist_cell($r)
    {
        if (empty($r['_dibuat_pada'])) return '<span class="muted">—</span>';
        $fmt = function ($t) { return date('d-m-Y H:i', strtotime($t)); };
        // Cukup satu jejak terakhir: "Diubah" bila pernah diubah, selain itu "Dibuat".
        $diubah = !empty($r['_diubah_pada']);
        $label  = $diubah ? 'Diubah' : 'Dibuat';
        $oleh   = $diubah ? ($r['_diubah_oleh'] ?? '—') : ($r['_dibuat_oleh'] ?? '—');
        $waktu  = $diubah ? $r['_diubah_pada'] : $r['_dibuat_pada'];
        return '<span class="hist-l">' . $label . ' <b>' . htmlspecialchars((string)$oleh) . '</b><br>' . $fmt($waktu) . '</span>';
    }
}

/** Item menu gear "Lihat Riwayat" (LOG-FR-07); kosong bila pengguna tidak berhak (LOG-FR-09). */
if (!function_exists('riwayat_btn')) {
    function riwayat_btn($table, $ref_no)
    {
        if (!can_view_activity_log() || $ref_no === null || $ref_no === '') return '';
        $url = site_url('activity_log_labmania') . '?ref_table=' . rawurlencode($table) . '&ref_no=' . rawurlencode((string)$ref_no);
        return '<button type="button" class="icon-btn" onclick="location.href=\'' . htmlspecialchars($url, ENT_QUOTES) . '\'" title="Lihat Riwayat"></button>';
    }
}
