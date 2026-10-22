<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/* 
 * @author Yunas Handra
 * @copyright Copyright (c) 2016, Yunas Handra
 * 
 * This is model class for table "log_5masterbarang"
 */

class Dashboard_model extends BF_Model
{

    /**
     * @var string  User Table Name
     */
    protected $table_name = 'log_5masterbarang';
    protected $key        = 'kodebarang';

    /**
     * @var string Field name to use for the created time column in the DB table
     * if $set_created is enabled.
     */
    protected $created_field = 'created_on';

    /**
     * @var string Field name to use for the modified time column in the DB
     * table if $set_modified is enabled.
     */
    protected $modified_field = 'modified_on';

    /**
     * @var bool Set the created time automatically on a new record (if true)
     */
    protected $set_created = true;

    /**
     * @var bool Set the modified time automatically on editing a record (if true)
     */
    protected $set_modified = true;
    /**
     * @var string The type of date/time field used for $created_field and $modified_field.
     * Valid values are 'int', 'datetime', 'date'.
     */
    /**
     * @var bool Enable/Disable soft deletes.
     * If false, the delete() method will perform a delete of that row.
     * If true, the value in $deleted_field will be set to 1.
     */
    protected $soft_deletes = true;

    protected $date_format = 'datetime';

    /**
     * @var bool If true, will log user id in $created_by_field, $modified_by_field,
     * and $deleted_by_field.
     */
    protected $log_user = true;

    /**
     * Function construct used to load some library, do some actions, etc.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function monitor_eoq()
    {
        $query = "SELECT * FROM monitor_eoq";
        return $this->db->query($query);
    }

    public function barang_masuk()
    {
        $query = "SELECT
            sum(log_transaksidt.jumlahrealisasi) as masuk
            FROM
            log_transaksidt
            INNER JOIN log_transaksiht ON log_transaksidt.notransaksi = log_transaksiht.notransaksi
            WHERE 
            log_transaksiht.post='1' AND log_transaksidt.statussaldo='1' AND log_transaksiht.tipetransaksi='2'";
        $query = $this->db->query($query);
        if ($query->num_rows() > 0) {
            return $query->row()->masuk;
        }
        return false;
    }

    public function barang_keluar()
    {
        $query = "SELECT
            sum(log_transaksidt.jumlahrealisasi) as realisasi
            FROM
            log_transaksidt
            INNER JOIN log_transaksiht ON log_transaksidt.notransaksi = log_transaksiht.notransaksi
            WHERE 
            log_transaksiht.post='1' AND log_transaksidt.statussaldo='1' AND log_transaksiht.tipetransaksi='3'";
        $query = $this->db->query($query);
        if ($query->num_rows() > 0) {
            return $query->row()->realisasi;
        }
        return false;
    }

    public function pengajuan_pending()
    {
        $query = "SELECT
            sum(log_prapodt.jumlah) as pending
            FROM
            log_prapodt
            INNER JOIN log_prapoht ON log_prapodt.nopp = log_prapoht.nopp
            WHERE 
            log_prapoht.sts_pp='0'";
        $query = $this->db->query($query);
        if ($query->num_rows() > 0) {
            return $query->row()->pending;
        }
        return false;
    }

    public function pengajuan_acc()
    {
        $query = "SELECT
            sum(log_prapodt.jumlah) as pending
            FROM
            log_prapodt
            INNER JOIN log_prapoht ON log_prapodt.nopp = log_prapoht.nopp
            WHERE
            log_prapoht.sts_pp='1'";
        $query = $this->db->query($query);
        if ($query->num_rows() > 0) {
            return $query->row()->pending;
        }
        return false;
    }

    public function count_penawaran_waiting_approval()
    {
        $this->db->select('COUNT(*) as total');
        $this->db->from('kons_tr_penawaran');
        $this->db->where('deleted_by', null);
        $this->db->where('sts_quot', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }

    public function count_penawaran_non_kons_waiting_approval()
    {
        $this->db->select('COUNT(*) as total');
        $this->db->from('kons_tr_penawaran_non_konsultasi');
        $this->db->where('deleted_by', null);
        $this->db->where('sts_quot', 0);
        $this->db->where('sts_deal <>', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }

    public function count_spk_sales_konsultan_waiting()
    {
        $this->db->select('COUNT(*) as total');
        $this->db->from('kons_tr_spk_penawaran');
        $this->db->where('deleted_by', null);
        $this->db->where('sts_spk', null);
        $this->db->where('id_penawaran IS NOT NULL');
        $this->db->where('id_penawaran <>', '');
        $this->db->group_start();
        $this->db->where('approval_konsultan_1_sts IS NULL');
        $this->db->or_where('approval_konsultan_2_sts IS NULL');
        $this->db->or_where('approval_sales_sts IS NULL');
        $this->db->group_end();
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }

    public function count_spk_project_leader_waiting()
    {
        $sql = "SELECT COUNT(*) as total FROM kons_tr_spk_penawaran
                WHERE deleted_by IS NULL
                AND sts_spk IS NULL
                AND approval_project_leader_sts IS NULL
                AND approval_sales_sts IS NOT NULL
                AND approval_konsultan_1_sts IS NOT NULL
                AND (IF(id_konsultan_2 IS NULL OR id_konsultan_2 = '', 1, approval_konsultan_2_sts) IS NOT NULL)
                AND id_penawaran IS NOT NULL
                AND id_penawaran <> ''";
        $query = $this->db->query($sql);
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }

    public function count_spk_manager_sales_waiting()
    {
        $sql = "SELECT COUNT(*) as total FROM kons_tr_spk_penawaran
                WHERE deleted_by IS NULL
                AND sts_spk IS NULL
                AND approval_manager_sales IS NULL
                AND approval_project_leader_sts IS NOT NULL
                AND approval_project_leader_sts <> ''
                AND id_penawaran IS NOT NULL
                AND id_penawaran <> ''";
        $query = $this->db->query($sql);
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }

    public function count_project_budgeting_waiting_approval()
    {
        // Match the existing waiting filter: exclude approved, rejected and NULL statuses.
        $leader_scope = get_project_budgeting_leader_scope();
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_tr_spk_penawaran b', 'b.id_spk_penawaran = a.id_spk_penawaran', 'left');
        apply_project_budgeting_leader_scope($this->db, 'b', $leader_scope);
        $this->db->where('a.sts <>', 1);
        $this->db->where('a.sts <>', 2);
        return (int) $this->db->count_all_results();
    }

    public function count_spk_direktur_waiting()
    {
        $sql = "SELECT COUNT(*) as total FROM kons_tr_spk_penawaran
                WHERE deleted_by IS NULL
                AND sts_spk IS NULL
                AND approval_level2_sts IS NULL
                AND approval_manager_sales = 1
                AND id_penawaran IS NOT NULL
                AND id_penawaran <> ''";
        $query = $this->db->query($sql);
        if ($query->num_rows() > 0) {
            return $query->row()->total;
        }
        return 0;
    }
}
