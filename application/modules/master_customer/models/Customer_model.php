<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Customer_model extends BF_Model
{

  public function __construct()
  {
      parent::__construct();
      $this->table_name = 'customer';
      $this->key        = 'id';
      $this->code       = 'id';
  }

 public function get_data($array_where){
  if(!empty($array_where)){
    $query = $this->db->get_where($this->table_name, $array_where);
  }
      else{
    $query = $this->db->get($this->table_name);
  }
  
  return $query->result();
}

  function getById($id)
  {
     return $this->db->get_where($this->table_name,array($this->code => $id))->row_array();
  }

  public function get_data_customer()
  {
      $draw = $this->input->post('draw');
      $start = $this->input->post('start');
      $length = $this->input->post('length');
      $search = $this->input->post('search');
      $order = $this->input->post('order');

      $columns = [
          0 => 'no',
          1 => 'nm_customer',
          2 => 'kredibilitas',
          3 => 'produk_jual',
          4 => 'country_code',
          5 => 'sts_aktif',
          6 => 'option'
      ];

      // Base Query
      $this->db->from('customer');
      $this->db->where('deleted_date IS NULL');

      // Total records count (without filter)
      $tempdb = clone $this->db;
      $recordsTotal = $tempdb->count_all_results();

      // Apply Search Filter
      if (!empty($search['value'])) {
          $s = $search['value'];
          $this->db->group_start();
          $this->db->like('nm_customer', $s, 'both');
          $this->db->or_like('kredibilitas', $s, 'both');
          $this->db->or_like('produk_jual', $s, 'both');
          $this->db->or_like('country_code', $s, 'both');
          $this->db->group_end();
      }

      // Filtered records count
      $tempdb = clone $this->db;
      $recordsFiltered = $tempdb->count_all_results();

      // Fetch Data
      $this->db->select('*');
      if (isset($order[0]['column']) && isset($columns[$order[0]['column']])) {
          $colIdx = $order[0]['column'];
          if ($colIdx != 0 && $colIdx != 6) {
              $this->db->order_by($columns[$colIdx], $order[0]['dir']);
          } else {
              $this->db->order_by('id_customer', 'desc');
          }
      } else {
          $this->db->order_by('id_customer', 'desc');
      }

      if ($length != -1) {
          $this->db->limit($length, $start);
      }

      $query = $this->db->get();

      $hasil = [];
      $no = $start + 1;

      $ENABLE_MANAGE = has_permission('Master_Customer.Manage');
      $ENABLE_DELETE = has_permission('Master_Customer.Delete');

      foreach ($query->result() as $record) {
          if ($record->sts_aktif == 'N') {
              $status = 'Non-Active';
              $status_ = 'red';
          } else {
              $status = 'Active';
              $status_ = 'green';
          }
          
          $statusBadge = "<span class='badge bg-" . $status_ . "'>" . $status . "</span>";

          $detail = '<a href="' . base_url('master_customer/add/' . $record->id_customer . '/view') . '" class="btn btn-warning btn-sm" title="Detail"><i class="fa fa-eye"></i></a>';
          
          $edit = '';
          if ($ENABLE_MANAGE) {
              $edit = '<a href="' . base_url('master_customer/add/' . $record->id_customer) . '" class="btn btn-primary btn-sm" title="Edit"><i class="fa fa-edit"></i></a>';
          }

          $delete = '';
          if ($ENABLE_DELETE) {
              $delete = '<button type="button" class="btn btn-danger btn-sm delete" title="Delete" data-id="' . $record->id_customer . '"><i class="fa fa-trash"></i></button>';
          }

          $buttons = $detail . ' ' . $edit . ' ' . $delete;

          $hasil[] = [
              'no' => $no,
              'nm_customer' => strtoupper($record->nm_customer),
              'kredibilitas' => strtoupper($record->kredibilitas),
              'produk_jual' => strtoupper($record->produk_jual),
              'country_code' => strtoupper($record->country_code),
              'sts_aktif' => $statusBadge,
              'option' => $buttons
          ];

          $no++;
      }

      echo json_encode([
          'draw' => intval($draw),
          'recordsTotal' => intval($recordsTotal),
          'recordsFiltered' => intval($recordsFiltered),
          'data' => $hasil
      ]);
  }

}