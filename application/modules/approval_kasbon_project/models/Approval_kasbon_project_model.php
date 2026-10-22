<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Approval_kasbon_project_model extends BF_Model
{

    protected $otherdb;
    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Approval_Kasbon_Project.Add');
        $this->ENABLE_MANAGE  = has_permission('Approval_Kasbon_Project.Manage');
        $this->ENABLE_VIEW    = has_permission('Approval_Kasbon_Project.View');
        $this->ENABLE_DELETE  = has_permission('Approval_Kasbon_Project.Delete');

        $this->otherdb = $this->load->database('sendigs_finance', TRUE);
    }

    function generate_id_kasbon_subcont($no_tambah)
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_kasbon_subcont) as maxP FROM kons_tr_kasbon_project_subcont WHERE id_kasbon_subcont LIKE '%/REQ/V/" . date('Y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 4);
        $urutan2 += $no_tambah;
        $urut2            = sprintf('%04s', $urutan2);
        $kode_trans        = $urut2 . '/REQ/V/' . date('Y');

        return $kode_trans;
    }

    function generate_id_kasbon_akomodasi($no_tambah)
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_kasbon_akomodasi) as maxP FROM kons_tr_kasbon_project_akomodasi WHERE id_kasbon_akomodasi LIKE '%/REQ/A/" . date('Y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 4);
        $urutan2 += $no_tambah;
        $urut2            = sprintf('%04s', $urutan2);
        $kode_trans        = $urut2 . '/REQ/A/' . date('Y');

        return $kode_trans;
    }

    function generate_id_kasbon_others($no_tambah)
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_kasbon_others) as maxP FROM kons_tr_kasbon_project_others WHERE id_kasbon_others LIKE '%/REQ/O/" . date('Y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 4);
        $urutan2 += $no_tambah;
        $urut2            = sprintf('%04s', $urutan2);
        $kode_trans        = $urut2 . '/REQ/O/' . date('Y');

        return $kode_trans;
    }

    public function generate_id_req_ovb_akomodasi()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id_request_ovb) as maxP FROM kons_tr_kasbon_req_ovb_akomodasi_header WHERE id_request_ovb LIKE '%/REQ/OVB/A/" . date('Y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 4);
        $urutan2++;
        $urut2            = sprintf('%04s', $urutan2);
        $kode_trans        = $urut2 . '/REQ/OVB/A/' . date('Y');

        return $kode_trans;
    }

    public function no_sendigs($tipe)
    {
        $no_doc = '';
        $newcode = '';
        $query_data = 'SELECT * FROM ms_generate WHERE tipe = "' . $tipe . '"';
        $data = $this->otherdb->query($query_data)->row();
        if ($data !== false) {
            if (stripos($data->info, 'YEAR', 0) !== false) {
                if ($data->info3 != date("Y")) {
                    $years = date("Y");
                    $number = 1;
                    $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
                } else {
                    $years = $data->info3;
                    $number = ($data->info2 + 1);
                    $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
                }
                $newcode = str_ireplace('XXXX', $newnumber, $data->info);
                $newcode = str_ireplace('YEAR', $years, $newcode);
                $newdata = array('info2' => $number, 'info3' => $years);
            } else {
                $number = ($data->info2 + 1);
                $newnumber = sprintf('%0' . $data->info4 . 'd', $number);
                $newcode = str_ireplace('XXXX', $newnumber, $data->info);
                $newdata = array('info2' => $number);
            }
            $this->otherdb->update('ms_generate', $newdata, array('tipe' => $tipe));

            $no_doc = $newcode;

            return $no_doc;
        } else {
            return false;
        }
    }
}
