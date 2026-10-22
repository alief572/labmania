<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class History_penawaran_model extends BF_Model
{
    public function generate_history_id()
    {
        $this->db->select('a.id_history');
        $this->db->from('kons_tr_penawaran_history a');
        $this->db->like('a.id_history', 'HST-' . date('Ym'), 'after');
        $this->db->order_by('a.id_history', 'DESC');
        $this->db->limit(1);
        $get_data = $this->db->get()->row();

        if (empty($get_data)) {
            $last_id = 0;
        } else {
            $last_id = $get_data->id_history;
            $last_id = substr($last_id, 11, 5);
        }
        $last_id = intval($last_id) + 1;
        $new_id = 'HST-' . date('Ym') . '-' . str_pad($last_id, 5, '0', STR_PAD_LEFT);

        return $new_id;
    }
}
