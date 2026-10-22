<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @author Yunas Handra
 * @copyright Copyright (c) 2018, Yunas Handra
 *
 * This is model class for table "Customer"
 */

class Invoicing_model extends BF_Model
{
    protected $viewPermission     = 'Invoicing.View';
    protected $addPermission      = 'Invoicing.Add';
    protected $managePermission = 'Invoicing.Manage';
    protected $deletePermission = 'Invoicing.Delete';

    public function generate_id()
    {
        $Ym             = date('ym');
        $srcMtr            = "SELECT MAX(id) as maxP FROM kons_tr_plan_tagih_header WHERE id LIKE '%/" . date('y') . "%' ";
        $resultMtr        = $this->db->query($srcMtr)->result_array();
        $angkaUrut2        = $resultMtr[0]['maxP'];
        $urutan2        = (int)substr($angkaUrut2, 0, 3);
        $urutan2++;
        $urut2            = sprintf('%03s', $urutan2);
        $kode_trans        = $urut2 . '/PLN-TGH/' . int_to_roman(date('m')) . '/' . date('y');

        return $kode_trans;
    }

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $length = $this->input->post('length');
        $start = $this->input->post('start');
        $search = $this->input->post('search');

        $this->db->select('a.*, b.id_customer, b.nm_customer, b.id_project, b.nm_project, b.id_project_leader, b.nm_project_leader, c.nm_sales');
        $this->db->from('kons_tr_actual_plan_tagih a');
        $this->db->join('kons_tr_plan_tagih_header b', 'b.id_spk_penawaran = a.id_spk_penawaran');
        $this->db->join('kons_tr_spk_penawaran c', 'c.id_spk_penawaran = a.id_spk_penawaran');
        $this->db->where_in('a.tagih_mundur', ['1', '2']);
        if (!empty($search['value'])) {
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
        }
        $this->db->order_by('a.id', 'desc');
        $this->db->limit($length, $start);

        $get_data = $this->db->get();

        $this->db->select('a.*, b.id_customer, b.nm_customer, b.id_project, b.nm_project, b.id_project_leader, b.nm_project_leader, c.nm_sales');
        $this->db->from('kons_tr_actual_plan_tagih a');
        $this->db->join('kons_tr_plan_tagih_header b', 'b.id_spk_penawaran = a.id_spk_penawaran');
        $this->db->join('kons_tr_spk_penawaran c', 'c.id_spk_penawaran = a.id_spk_penawaran');
        $this->db->where_in('a.tagih_mundur', ['1', '2']);
        if (!empty($search['value'])) {
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
        }
        $this->db->order_by('a.id', 'desc');

        $get_data_all = $this->db->get();

        $hasil = [];
        $no = (0 + $start);

        foreach ($get_data->result() as $item) {
            $no++;

            $status = '';
            if ($item->tagih_mundur == '1') {
                $status = '<button type="button" class="btn btn-sm btn-success">Tagih</button>';
            }
            if ($item->tagih_mundur == '2') {
                $status = '<button type="button" class="btn btn-sm btn-danger">Mundur</button>';
            }

            $hasil[] = [
                'no' => $no,
                'id_actual_plan_tagih' => $item->id,
                'company' => '',
                'no_spk' => $item->id_spk_penawaran,
                'customer' => $item->nm_customer,
                'project' => $item->nm_project,
                'project_leader' => $item->nm_project_leader,
                'sales' => $item->nm_sales,
                'status' => $status,
                'option' => ''
            ];
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data_all->num_rows(),
            'recordsFiltered' => $get_data_all->num_rows(),
            'data' => $hasil
        ]);
    }
}
