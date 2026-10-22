<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Consultation_report_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Consultation_Report.Add');
        $this->ENABLE_MANAGE  = has_permission('Consultation_Report.Manage');
        $this->ENABLE_VIEW    = has_permission('Consultation_Report.View');
        $this->ENABLE_DELETE  = has_permission('Consultation_Report.Delete');
    }

    public function get_data_spk() {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->where('a.approval_level2_sts', 1);
        if(!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');
        $this->db->limit($length, $start);
        $get_data = $this->db->get();

        $this->db->select('a.*');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->where('a.approval_level2_sts', 1);
        if(!empty($search)) {
            $this->db->group_start();
            $this->db->like('a.id_spk_penawaran', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_project_leader', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');
        $get_data_all = $this->db->get();

        $hasil = [];

        $no = 0 + $start;
        foreach($get_data->result() as $item) {
            $no++;

            $ttl_mandays = 0;
            $get_mandays = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $item->id_spk_penawaran])->result();
            foreach($get_mandays as $item_mandays) {
                $ttl_mandays += ($item_mandays->mandays + $item_mandays->mandays_tandem + $item_mandays->mandays_subcont);
            }

            $action = '<a href="'.base_url('consultation_report/add/' . urlencode(str_replace('/', '|', $item->id_spk_penawaran))).'" class="btn btn-sm btn-primary" title="Input Consultation"><i class="fa fa-arrow-up"></i></a>';

            $hasil[] = [
                'no' => $no,
                'no_spk' => $item->id_spk_penawaran,
                'nm_customer' => $item->nm_customer,
                'nm_project' => $item->nm_project,
                'project_leader' => ucfirst($item->nm_project_leader),
                'mandays' => $ttl_mandays,
                'actual' => 0,
                'action' => $action
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
