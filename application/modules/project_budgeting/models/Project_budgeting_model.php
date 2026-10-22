<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_budgeting_model extends BF_Model
{

    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Project_Budgeting.Add');
        $this->ENABLE_MANAGE  = has_permission('Project_Budgeting.Manage');
        $this->ENABLE_VIEW    = has_permission('Project_Budgeting.View');
        $this->ENABLE_DELETE  = has_permission('Project_Budgeting.Delete');
    }

    function generate_id_spk_budgeting()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_spk_budgeting) as maxP FROM kons_tr_spk_budgeting WHERE id_spk_budgeting LIKE '%SPK-BUDGET/" . date('y') . "/" . date('m') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 17, 3);
        $urutan2++;
        $urut2            = sprintf('%03s', $urutan2);
        $kode_trans        = "SPK-BUDGET/" . date('y') . "/" . date('m') . "/" . $urut2;

        return $kode_trans;
    }

    public function get_spk_budgeting($id_spk_budgeting)
    {
        $this->db->from('kons_tr_spk_budgeting a');
        $this->db->join('kons_master_konsultasi_header b', 'b.id_konsultasi_h = a.id_project', 'left');
        $this->db->where('a.id_spk_budgeting', $id_spk_budgeting);
        return $this->db->get()->row();
    }
}
