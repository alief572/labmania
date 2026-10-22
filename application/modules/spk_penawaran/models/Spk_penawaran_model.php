<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Spk_penawaran_model extends BF_Model
{

    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    protected $dbhr;

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('SPK_penawaran.Add');
        $this->ENABLE_MANAGE  = has_permission('SPK_penawaran.Manage');
        $this->ENABLE_VIEW    = has_permission('SPK_penawaran.View');
        $this->ENABLE_DELETE  = has_permission('SPK_penawaran.Delete');

        $this->dbhr = $this->load->database('dbhr', true);
    }

    function generate_id_spk_penawaran()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_spk_penawaran) as maxP FROM kons_tr_spk_penawaran WHERE id_spk_penawaran LIKE '%/" . date('y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 3);
        $urutan2++;
        $urut2            = sprintf('%03s', $urutan2);
        $kode_trans        = $urut2 . '/STM/MKT-SPK/' . int_to_roman(date('m')) . '/' . date('y');

        return $kode_trans;
    }

    function generate_id_spk_penawaran_non_kons()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_spk_penawaran) as maxP FROM kons_tr_spk_non_kons WHERE id_spk_penawaran LIKE '%/" . date('y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 11, 3);
        $urutan2++;
        $urut2            = sprintf('%03s', $urutan2);
        $kode_trans        = $urut2 . '/STM-NON-KONS/MKT-SPK/' . int_to_roman(date('m')) . '/' . date('y');

        return $kode_trans;
    }

    public function render_status_spk($item)
    {
        $approval_position = '';
        $approval_position_arr = [];
        if ($item->approval_sales_sts == null) {
            $approval_position_arr[] = 'Sales';
        }
        if ($item->approval_sales_sts !== null && $item->approval_konsultan_1_sts == null && $item->id_konsultan_1 !== '') {
            $approval_position_arr[] = 'Konsultan 1';
        }
        if ($item->approval_sales_sts !== null && $item->approval_konsultan_2_sts == null && $item->id_konsultan_2 !== '') {
            $approval_position_arr[] = 'Konsultan 2';
        }

        if (empty($approval_position_arr) && $item->approval_level2_sts == null) {
            $approval_position = 'Direktur';
        }
        if (empty($approval_position_arr) && $item->approval_manager_sales == null) {
            $approval_position = 'Manager Sales';
        }
        if (empty($approval_position_arr) && $item->approval_project_leader_sts == null) {
            $approval_position = 'Project Leader';
        }

        $status_spk = '';

        if (!empty($approval_position_arr)) {
            $status_spk = '<span class="badge bg-blue">Waiting Approval : ' . implode(' & ', $approval_position_arr) . '</span>';
        }
        if (!empty($approval_position)) {
            $status_spk = '<span class="badge bg-blue">Waiting Approval : ' . $approval_position . '</span>';
        }

        if ($item->sts_spk == '1') {
            $status_spk = '<span class="badge bg-green">Approved</span>';
        }
        if ($item->sts_spk == '0') {
            $status_spk = '<span class="badge bg-red">Rejected</span>';
        }

        if ($item->reject_sales_sts !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Sales</span>';
        }
        if ($item->reject_konsultan_1_sts !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Konsultan 1</span>';
        }
        if ($item->reject_konsultan_2_sts !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Konsultan 2</span>';
        }
        if ($item->reject_project_leader_sts !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Project Leader</span>';
        }
        if ($item->reject_manager_sales_sts !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Manager Sales</span>';
        }
        if ($item->reject_level2_by !== null) {
            $status_spk = '<span class="badge bg-red" style="font-weight: bold;"> Rejected by : Direktur</span>';
        }

        return $status_spk;
    }

    public function render_status($item)
    {


        $status = '<span class="badge bg-green">New</span>';

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $item->id_penawaran])->row();
        if (!empty($get_penawaran) && $get_penawaran->sts_cust == 0) {
            $status = '
                <span class="badge bg-yellow" style="width: 100% !important;">
                    <b>New</b>
                </span>
            ';
        } else {
            $status = '
                <span class="badge bg-light-blue" style="width: 100% !important;">
                    <b>Repeat</b>
                </span>
            ';
        }

        return $status;
    }


    public function get_new_penawaran_non_kons()
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_non_konsultasi a');
        $this->db->join('kons_tr_spk_non_kons b', 'b.id_penawaran = a.id_penawaran', 'left');
        $this->db->where('a.sts_deal', '1');
        $this->db->where('a.sts_quot', '1');
        $this->db->where('b.id_penawaran', null);
        $get_data = $this->db->get()->result();

        return $get_data;
    }

    public function get_penawaran_non_kons($id_penawaran = null)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_non_konsultasi a');
        if (!empty($id_penawaran)) {
            $this->db->where('a.id_penawaran', $id_penawaran);
            $get_data = $this->db->get()->row();
        } else {
            $get_data = $this->db->get()->result();
        }

        return $get_data;
    }

    public function get_penawaran_detail_non_kons($id_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_detail_penawaran_non_konsultasi a');
        $this->db->where('a.id_header', $id_penawaran);
        $get_data = $this->db->get()->result();

        return $get_data;
    }

    public function get_list_employee($id = null)
    {
        $this->dbhr->select('a.id, a.name');
        $this->dbhr->from('employees a');
        $this->dbhr->where('a.flag_active', 'Y');
        if (!empty($id)) {
            $this->dbhr->where('a.id', $id);
            $get_data = $this->dbhr->get()->row();
        } else {
            $get_data = $this->dbhr->get()->result();
        }


        return $get_data;
    }

    public function render_action_non_kons($item)
    {
        $view_btn = '';
        $print_btn = '';
        if ($this->ENABLE_VIEW) {
            $view_btn = '<a href="' . base_url('spk_penawaran/view_non_kons/' . str_replace('/', '|', $item->id_spk_penawaran)) . '" class="btn btn-sm btn-info" title="View SPK Non Konsultasi"><i class="fa fa-eye"></i></a>';

            $print_btn = '<a href="javascript:void(0);" class="btn btn-sm btn-primary" title="Print SPK Non Konsultasi"><i class="fa fa-print"></i></a>';
        }

        $edit_btn = '';
        if ($this->ENABLE_MANAGE && $item->sts_spk !== '1' && empty($item->approval_sales_sts)) {
            $edit_btn = '<a href="' . base_url('spk_penawaran/edit_non_kons/' . str_replace('/', '|', $item->id_spk_penawaran)) . '" class="btn btn-sm btn-warning" title="Edit SPK Non Konsultasi"><i class="fa fa-pencil"></i></a>';
        }

        $delete_btn = '';
        if ($this->ENABLE_DELETE && $item->sts_spk !== '1' && empty($item->approval_sales_sts)) {
            $delete_btn = '<button type="button" class="btn btn-sm btn-danger del_spk_non_kons" data-id_spk_penawaran="' . $item->id_spk_penawaran . '" title="Delete SPK Non Konsultasi"><i class="fa fa-trash"></i></button>';
        }



        $action = $view_btn . ' ' . $edit_btn . ' ' . $delete_btn . ' ' . $print_btn;

        return $action;
    }

    public function get_spk_non_kons($id_spk_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_non_kons a');
        $this->db->where('a.id_spk_penawaran', $id_spk_penawaran);
        $get_data = $this->db->get()->row();

        return $get_data;
    }

    /**
     * Get employee by ID from HR database
     */
    public function get_employee($id)
    {
        if (empty($id)) {
            return null;
        }
        $this->dbhr->select('a.id, a.name as nm_karyawan');
        $this->dbhr->from('employees a');
        $this->dbhr->where('a.id', $id);
        return $this->dbhr->get()->row();
    }

    /**
     * Get penawaran aktifitas with joined activity name
     */
    public function get_penawaran_aktifitas($id_quotation)
    {
        $this->db->select('a.id, a.id_aktifitas, a.mandays, a.mandays_rate, a.bobot, a.mandays_tandem, a.mandays_rate_tandem, a.harga_aktifitas, a.total_aktifitas, b.nm_aktifitas as aktifitas_nm');
        $this->db->from('kons_tr_penawaran_aktifitas a');
        $this->db->join('kons_master_aktifitas b', 'b.id_aktifitas = a.id_aktifitas', 'left');
        $this->db->where('a.id_penawaran', $id_quotation);
        return $this->db->get()->result();
    }

    /**
     * Get konsultasi header with paket name
     */
    public function get_konsultasi($id_paket)
    {
        $this->db->select('a.*, b.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->join('kons_master_paket b', 'b.id_paket = a.id_paket', 'left');
        $this->db->where('a.id_konsultasi_h', $id_paket);
        return $this->db->get()->row();
    }

    /**
     * Get division by ID from HR database
     */
    public function get_divisi($id)
    {
        if (empty($id)) {
            return null;
        }
        return $this->dbhr->get_where(DBHR . '.divisions', ['id' => $id])->row();
    }

    /**
     * Check if SPK penawaran ID already exists
     */
    public function is_spk_exists($id_spk_penawaran)
    {
        return $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->num_rows() > 0;
    }

    public function generate_id_history()
    {
        $Ym = date('Ym');
        $srcMtr = "SELECT MAX(id_history) as maxP FROM kons_tr_spk_penawaran_history WHERE id_history LIKE '%/" . date('y') . "%' ";
        $resultMtr = $this->db->query($srcMtr)->result_array();
        $angkaUrut2 = isset($resultMtr[0]['maxP']) ? $resultMtr[0]['maxP'] : null;
        $urutan2 = $angkaUrut2 ? (int)substr($angkaUrut2, 0, 3) : 0;
        $urutan2++;
        $urut2 = sprintf('%03s', $urutan2);
        $kode_trans = $urut2 . '/SPK-HIST/' . date('m') . '/' . date('y');
        return $kode_trans;
    }

    public function get_revisi($id_spk_penawaran)
    {
        $this->db->select_max('revisi');
        $this->db->where('id_spk_penawaran', $id_spk_penawaran);
        $result = $this->db->get('kons_tr_spk_penawaran_history')->row();
        return isset($result->revisi) ? $result->revisi + 1 : 1;
    }

    public function save_to_history($id_spk_penawaran, $revisi)
    {
        $header = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        if (!$header) {
            log_message('error', 'save_to_history: SPK not found - ' . $id_spk_penawaran);
            return false;
        }

        $id_history = $this->generate_id_history();

        $history_header = (array)$header;
        $history_header['id_history'] = $id_history;
        $history_header['revisi'] = $revisi;

        unset(
            $history_header['id'],
            $history_header['edited_by'],
            $history_header['edited_date']
        );

        // Filter keys to only contain columns existing in history table
        $header_fields = $this->db->list_fields('kons_tr_spk_penawaran_history');
        foreach ($history_header as $key => $value) {
            if (!in_array($key, $header_fields)) {
                unset($history_header[$key]);
            }
        }

        $this->db->trans_begin();

        // Insert header history
        $insert_header = $this->db->insert('kons_tr_spk_penawaran_history', $history_header);
        if (!$insert_header) {
            $this->db->trans_rollback();
            log_message('error', 'save_to_history: header insert failed for ' . $id_spk_penawaran . ' - ' . json_encode($this->db->error()));
            return false;
        }

        // Insert aktifitas history
        $aktifitas = $this->db->get_where('kons_tr_spk_aktifitas', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        if (!empty($aktifitas)) {
            $aktifitas_fields = $this->db->list_fields('kons_tr_spk_aktifitas_history');
            foreach ($aktifitas as $item) {
                $item_arr = (array)$item;
                $item_arr['id_history'] = $id_history;
                unset($item_arr['id']);
                
                foreach ($item_arr as $key => $value) {
                    if (!in_array($key, $aktifitas_fields)) {
                        unset($item_arr[$key]);
                    }
                }
                
                $insert = $this->db->insert('kons_tr_spk_aktifitas_history', $item_arr);
                if (!$insert) {
                    $this->db->trans_rollback();
                    log_message('error', 'save_to_history: aktifitas insert failed for ' . $id_spk_penawaran . ' - ' . json_encode($this->db->error()));
                    return false;
                }
            }
        }


        // Insert payment history
        $payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        if (!empty($payment)) {
            $payment_fields = $this->db->list_fields('kons_tr_spk_penawaran_payment_history');
            foreach ($payment as $item) {
                $item_arr = (array)$item;
                $item_arr['id_history'] = $id_history;
                unset($item_arr['id']);
                
                foreach ($item_arr as $key => $value) {
                    if (!in_array($key, $payment_fields)) {
                        unset($item_arr[$key]);
                    }
                }
                
                $insert = $this->db->insert('kons_tr_spk_penawaran_payment_history', $item_arr);
                if (!$insert) {
                    $this->db->trans_rollback();
                    log_message('error', 'save_to_history: payment insert failed for ' . $id_spk_penawaran . ' - ' . json_encode($this->db->error()));
                    return false;
                }
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            log_message('error', 'save_to_history: transaction failed for ' . $id_spk_penawaran);
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    /**
     * Insert SPK penawaran with all related data (aktifitas, subcont, payment)
     */
    public function insert_spk_penawaran($arr_insert, $data_aktifitas, $data_payment, $id_quotation, $tipe_informasi_awal)
    {
        $this->db->trans_begin();

        $this->db->insert('kons_tr_spk_penawaran', $arr_insert);

        $this->db->update('kons_tr_penawaran', ['id_spk_penawaran' => $arr_insert['id_spk_penawaran']], ['id_quotation' => $id_quotation]);

        if (!empty($data_aktifitas)) {
            $this->db->insert_batch('kons_tr_spk_aktifitas', $data_aktifitas);
        }



        if (!empty($data_payment)) {
            $this->db->insert_batch('kons_tr_spk_penawaran_payment', $data_payment);
        }

        $this->db->update('kons_tr_penawaran', ['sts_cust' => $tipe_informasi_awal], ['id_quotation' => $id_quotation]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }
}
