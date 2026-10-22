<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Approval_penawaran_model extends BF_Model
{
    protected $dbhr;

    public function __construct()
    {
        $this->dbhr = $this->load->database('dbhr', true);
    }

    public function get_penawaran($id_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_penawaran_non_konsultasi a');
        $this->db->where('a.id_penawaran', $id_penawaran);
        $get_data = $this->db->get()->row();

        return $get_data;
    }

    public function get_penawaran_detail($id_penawaran)
    {
        $this->db->select('a.*');
        $this->db->from('kons_tr_detail_penawaran_non_konsultasi a');
        $this->db->where('a.id_header', $id_penawaran);
        $get_data = $this->db->get()->result();

        return $get_data;
    }
    public function list_divisi()
    {
        $this->dbhr->select('a.id as id_divisi, a.name as nm_divisi, b.name as nm_company');
        $this->dbhr->from('departments a');
        $this->dbhr->join('companies b', 'b.id = a.company_id', 'left');
        $this->dbhr->where_in('a.company_id', ['COM003', 'COM006', 'COM012']);
        $this->dbhr->order_by('a.name', 'asc');
        $get_data = $this->dbhr->get()->result();

        return $get_data;
    }

    public function list_customer()
    {
        $this->db->select('a.id_customer, a.nm_customer');
        $this->db->from('customer a');
        $this->db->where('a.deleted', 'N');
        $this->db->order_by('a.nm_customer', 'asc');
        $get_data = $this->db->get()->result();

        return $get_data;
    }

    public function list_company()
    {
        $this->db->select('a.id as id_company, a.nm_company');
        $this->db->from('kons_tr_company a');
        $this->db->order_by('a.nm_company', 'asc');
        $get_data = $this->db->get()->result();

        return $get_data;
    }

    public function list_employee()
    {
        $this->dbhr->select('a.id, a.name as nm_karyawan');
        $this->dbhr->from('employees a');
        $this->dbhr->where('a.flag_active', 'Y');
        $get_employees = $this->dbhr->get()->result();

        return $get_employees;
    }
}
