<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Perbaikan_data_model extends BF_Model
{
    protected $otherdb;
    public function __construct()
    {
        parent::__construct();
        $this->otherdb = $this->load->database('sendigs_finance', TRUE);
        // $this->otherdb =  $this->otherdb->query("SET NAMES 'utf8'");

        date_default_timezone_set('Asia/Bangkok');
    }
    public function no_sendigs($tipe, $no)
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
        }

        return $no_doc;
    }
}
