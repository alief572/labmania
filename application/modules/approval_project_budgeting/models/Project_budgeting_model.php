<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_budgeting_model extends BF_Model
{

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
        $srcMtr            = "SELECT MAX(id_spk_penawaran) as maxP FROM kons_tr_spk_penawaran WHERE id_spk_penawaran LIKE '%SPK-BUDGET/" . date('y') . "/" . date('m') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 3);
        $urutan2++;
        $urut2            = sprintf('%03s', $urutan2);
        $kode_trans        = "SPK-BUDGET/" . date('y') . "/" . date('m') . "/" . $urut2;

        return $kode_trans;
    }
}
