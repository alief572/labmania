<?php

class divisions_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();
        // Your own constructor code

    }

    public function get_data_divisions()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*,b.name as company_name');
        $this->db->from('hr_sentral.divisions a');
        $this->db->join('hr_sentral.companies b', 'b.id = a.company_id', 'left');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->like('a.name', $search['value'], 'both');
            $this->db->like('b.name', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->limit($length, $start);
        $query = $this->db->get();

        $this->db->select('a.*,b.name as company_name');
        $this->db->from('hr_sentral.divisions a');
        $this->db->join('hr_sentral.companies b', 'b.id = a.company_id', 'left');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id', $search['value'], 'both');
            $this->db->like('a.name', $search['value'], 'both');
            $this->db->like('b.name', $search['value'], 'both');
            $this->db->group_end();
        }
        $query_all = $this->db->get();

        $hasil = [];

        $int    = 0;
        foreach ($query->result() as $datas) {
            $int++;

            $button = "<a href='" . site_url('divisions/view/' . $datas->id) . "' class='btn btn-sm btn-primary' title='View Data' data-role='qtip'><i class='fa fa-eye'></i></a>";

            $hasil[] = [
                'id' => $datas->id,
                'name' => $datas->name,
                'company_name' => $datas->company_name,
                'option' => $button
            ];
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $query_all->num_rows(),
            'recordsFiltered' => $query_all->num_rows(),
            'data' => $hasil
        ]);
    }
}
