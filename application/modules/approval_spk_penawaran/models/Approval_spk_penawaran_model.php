<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Approval_spk_penawaran_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Approval_SPK.Add');
        $this->ENABLE_MANAGE  = has_permission('Approval_SPK.Manage');
        $this->ENABLE_VIEW    = has_permission('Approval_SPK.View');
        $this->ENABLE_DELETE  = has_permission('Approval_SPK.Delete');
    }

    function generate_id_spk_penawaran()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_spk_penawaran) as maxP FROM kons_tr_spk_penawaran WHERE id_spk_penawaran LIKE 'SPK" . $Ym . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 7, 4);
        $urutan2++;
        $urut2            = sprintf('%04s', $urutan2);
        $kode_trans        = "SPK" . $Ym . $urut2;

        return $kode_trans;
    }
}
