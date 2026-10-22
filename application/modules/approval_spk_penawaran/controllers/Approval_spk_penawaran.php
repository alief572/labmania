<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is controller for Pengajuan Rutin
 */

$status = array();
class Approval_spk_penawaran extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Approval_SPK.View';
    protected $addPermission      = 'Approval_SPK.Add';
    protected $managePermission = 'Approval_SPK.Manage';
    protected $deletePermission = 'Approval_SPK.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->template->title('Quotation');
        $this->template->page_icon('fa fa-cubes');
        $this->load->library('upload');
        $this->load->model(array('Approval_spk_penawaran/Approval_spk_penawaran_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval SPK');
        $this->template->render('index');
    }

    public function view_spk($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        $get_spk_penawaran_subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        $get_spk_penawaran_payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_spk_penawaran->id_penawaran])->row();

        $this->db->select('a.*');
        $this->db->from('customers a');
        $this->db->where('a.name <>', '');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('employee a');
        $this->db->where('a.deleted', 'N');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('employee a');
        $this->db->where('a.deleted', 'N');
        $this->db->order_by('a.nm_karyawan', 'asc');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('b.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->join('kons_master_paket b', 'b.id_paket = a.id_paket', 'left');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.nama');
        $this->db->from('ms_department a');
        $this->db->where('a.deleted_by', null);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.*');
            $this->db->from('employee a');
            $this->db->where('a.deleted', 'N');
            $this->db->where('a.id', $get_penawaran->detail_informasi_awal);
            $get_marketing_informasi_awal = $this->db->get()->row();

            $detail_informasi_awal = $get_marketing_informasi_awal->nm_karyawan;
        } else {
            $detail_informasi_awal = $get_penawaran->detail_informasi_awal;
        }



        $data = [
            'list_spk_penawaran' => $get_spk_penawaran,
            'list_spk_penawaran_subcont' => $get_spk_penawaran_subcont,
            'list_spk_penawaran_payment' => $get_spk_penawaran_payment,
            'list_penawaran' => $get_penawaran,
            'list_customer' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_all_marketing' => $get_all_marketing,
            'list_divisi' => $get_divisi,
            'list_all_aktifitas' => $get_all_aktifitas,
            'detail_informasi_awal' => $detail_informasi_awal
        ];

        $this->auth->restrict($this->viewPermission);
        $this->template->title('View SPK');
        $this->template->set($data);
        $this->template->render('view_spk');
    }

    public function get_data_spk()
    {
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search');

        $this->db->select('a.*, b.grand_total');
        $this->db->from('kons_tr_spk_penawaran a');
        $this->db->join('kons_tr_penawaran b', 'b.id_quotation = a.id_penawaran', 'left');
        $this->db->where(1, 1);
        $this->db->where('a.deleted_by', null);
        $this->db->group_start();
        $this->db->where('a.sts_spk', null);
        $this->db->or_where('a.sts_spk', '');
        $this->db->or_where('a.sts_spk', 0);
        $this->db->group_end();
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->or_like('a.nm_sales', $search['value'], 'both');
            $this->db->or_like('a.nm_project', $search['value'], 'both');
            $this->db->or_like('a.nm_customer', $search['value'], 'both');
            $this->db->or_like('b.grand_total', $search['value'], 'both');
            $this->db->group_end();
        }
        $this->db->order_by('a.input_date', 'desc');

        $get_data = $this->db->get();

        $hasil = [];

        $no = 1;
        foreach ($get_data->result() as $item) {

            $status = '<button type="button" class="btn btn-sm btn-success">NEW</button>';
            $status_spk = '<button type="button" class="btn btn-sm btn-primary">Waiting Approval</button>';

            $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $item->id_penawaran])->row();
            if ($get_penawaran->sts_cust == 0) {
                $status = '
                    <span class="btn btn-sm btn-warning" style="width: 100% !important;">
                        <b>New</b>
                    </span>
                ';
            } else {
                $status = '
                    <span class="btn btn-sm btn-info" style="width: 100% !important;">
                        <b>Repeat</b>
                    </span>
                ';
            }

            if ($item->sts_spk == '1') {
                $status_spk = '<button type="button" class="btn btn-sm btn-success">Approved</button>';
            }
            if ($item->sts_spk == '0') {
                $status_spk = '<button type="button" class="btn btn-sm btn-danger">Rejected</button>';
            }

            $option = '
            <div class="btn-group">
                <button
                    type="button"
                    class="btn btn-sm btn-accent text-primary dropdown-toggle"
                    title="Actions"
                    data-toggle="dropdown"
                    id="dropdownMenu' . $no . '"
                    aria-expanded="false">
                    <i class="fa fa-cogs"></i> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" style="min-width: 150px; padding: 5px 0;">
            ';

            if ($this->viewPermission) {
                $option .= '
                    <li>
                        <a href="' . base_url('approval_spk_penawaran/view_spk/' . $item->id_spk_penawaran) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-file text-info"></i> View
                        </a>
                    </li>
                ';
            }

            if ($this->managePermission) {
                $option .= '
                    <li>
                        <a href="' . base_url('approval_spk_penawaran/approval_spk/' . $item->id_spk_penawaran) . '" style="display: block; padding: 5px 15px; color: #333; text-decoration: none;">
                            <i class="fa fa-check text-success"></i> Approval
                        </a>
                    </li>
                ';
            }
            $option .= '</ul>';

            $nm_marketing = $item->nm_sales;

            $nm_paket = $item->nm_project;

            $nm_customer = $item->nm_customer;

            $hasil[] = [
                'no' => $no,
                'id_spk_penawaran' => $item->id_spk_penawaran,
                'nm_marketing' => $nm_marketing,
                'nm_paket' => $nm_paket,
                'nm_customer' => $nm_customer,
                'grand_total' => number_format($item->grand_total),
                'status' => $status,
                'status_spk' => $status_spk,
                'option' => $option
            ];

            $no++;
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $get_data->num_rows(),
            'recordsFiltered' => $get_data->num_rows(),
            'data' => $hasil
        ]);
    }

    public function approval_spk($id_spk_penawaran)
    {
        $id_spk_penawaran = urldecode($id_spk_penawaran);
        $id_spk_penawaran = str_replace('|', '/', $id_spk_penawaran);

        $get_spk_penawaran = $this->db->get_where('kons_tr_spk_penawaran', ['id_spk_penawaran' => $id_spk_penawaran])->row();
        $get_spk_penawaran_subcont = $this->db->get_where('kons_tr_spk_penawaran_subcont', ['id_spk_penawaran' => $id_spk_penawaran])->result();
        $get_spk_penawaran_payment = $this->db->get_where('kons_tr_spk_penawaran_payment', ['id_spk_penawaran' => $id_spk_penawaran])->result();

        $get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_spk_penawaran->id_penawaran])->row();

        $this->db->select('a.*');
        $this->db->from('customers a');
        $this->db->where('a.name <>', '');
        $this->db->where('a.id_customer', $get_penawaran->id_customer);
        $get_customer = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('employee a');
        $this->db->where('a.deleted', 'N');
        $this->db->where('a.id', $get_penawaran->id_marketing);
        $get_marketing = $this->db->get()->row();

        $this->db->select('a.*');
        $this->db->from('employee a');
        $this->db->where('a.deleted', 'N');
        $this->db->order_by('a.nm_karyawan', 'asc');
        $get_all_marketing = $this->db->get()->result();

        $this->db->select('b.nm_paket');
        $this->db->from('kons_master_konsultasi_header a');
        $this->db->join('kons_master_paket b', 'b.id_paket = a.id_paket', 'left');
        $this->db->where('a.id_konsultasi_h', $get_penawaran->id_paket);
        $get_konsultasi = $this->db->get()->row();

        $this->db->select('a.id, a.nama');
        $this->db->from('ms_department a');
        $this->db->where('a.deleted_by', null);
        $get_divisi = $this->db->get()->result();

        $this->db->select('a.*');
        $this->db->from('kons_master_aktifitas a');
        $get_all_aktifitas = $this->db->get()->result();

        $detail_informasi_awal = '';
        if ($get_penawaran->tipe_informasi_awal == 'Sales' || $get_penawaran->tipe_informasi_awal == 'Others') {
            $this->db->select('a.*');
            $this->db->from('employee a');
            $this->db->where('a.deleted', 'N');
            $this->db->where('a.id', $get_penawaran->detail_informasi_awal);
            $get_marketing_informasi_awal = $this->db->get()->row();

            $detail_informasi_awal = $get_marketing_informasi_awal->nm_karyawan;
        } else {
            $detail_informasi_awal = $get_penawaran->detail_informasi_awal;
        }



        $data = [
            'list_spk_penawaran' => $get_spk_penawaran,
            'list_spk_penawaran_subcont' => $get_spk_penawaran_subcont,
            'list_spk_penawaran_payment' => $get_spk_penawaran_payment,
            'list_penawaran' => $get_penawaran,
            'list_customer' => $get_customer,
            'list_marketing' => $get_marketing,
            'list_all_marketing' => $get_all_marketing,
            'list_divisi' => $get_divisi,
            'list_all_aktifitas' => $get_all_aktifitas,
            'detail_informasi_awal' => $detail_informasi_awal
        ];

        $this->auth->restrict($this->viewPermission);
        $this->template->title('Approval SPK');
        $this->template->set($data);
        $this->template->render('approval_spk');
    }

    public function reject_spk()
    {
        $post = $this->input->post();

        $id_spk_penawaran = $post['id_spk_penawaran'];
        $reject_reason = $post['reject_reason'];

        $this->db->trans_begin();

        $update_reject_spk = $this->db->update('kons_tr_spk_penawaran', ['sts_spk' => 0, 'reject_reason' => $reject_reason], ['id_spk_penawaran' => $id_spk_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been rejected !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }

    public function approve_spk()
    {
        $post = $this->input->post();

        $id_spk_penawaran = $post['id_spk_penawaran'];

        $update_reject_spk = $this->db->update('kons_tr_spk_penawaran', ['sts_spk' => 1], ['id_spk_penawaran' => $id_spk_penawaran]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $valid = 0;
            $pesan = 'Please try again later !';
        } else {
            $this->db->trans_commit();
            $valid = 1;
            $pesan = 'Data has been Approved !';
        }

        echo json_encode([
            'status' => $valid,
            'pesan' => $pesan
        ]);
    }
}
