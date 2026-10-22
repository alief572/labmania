<?php
defined('BASEPATH') or exit('No direct script access allowed');
$data_status = array();
class Pembayaran_material extends Admin_Controller
{
	protected $viewPermission   = 'Payment.View';
	protected $addPermission    = 'Payment.Add';
	protected $managePermission = 'Payment.Manage';
	protected $deletePermission = 'Payment.Delete';

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('upload', 'Image_lib'));
		$this->load->model(array(
			'pembayaran_material/master_model',
			'pembayaran_material/Pembayaran_material_model',
			'all/All_model',
			'pembayaran_material/Jurnal_model'
		));
		$this->data_status = array('0' => 'Pengajuan', '1' => 'Approve', '2' => 'Selesai');
	}
	//==================================================================================================================
	//==================================================REQUEST PEMBAYARAN==============================================
	//==================================================================================================================
	function index()
	{
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$data_listnm 			= $this->pembayaran_material_model->get_data_json_request_payment_nm();
		$data = array(
			'title'			=> 'Indeks Of Request Payment',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'results'		=> $data_list,
			'resultsnm'		=> $data_listnm,
			'data_status'	=> $this->data_status
		);
		history('View Request Payment');
		$this->load->view('Pembayaran_material/index_request_payment', $data);
	}
	function delete_request($id, $tipetrans)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['delete'] != '1') {
			$param = array(
				'save' => false
			);
			echo json_encode($param);
			die();
		}
		$this->db->trans_begin();
		if ($tipetrans == "2") {
			$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $id . "'")->row();
			$this->db->query("delete from purchase_order_request_payment_nm where id='" . $id . "'");
			$this->All_model->DataUpdate('tran_po_detail', array('status_pay' => ''), array('status_pay' => $data->no_request));
		} else {
			$data = $this->db->query("select * from purchase_order_request_payment where id='" . $id . "'")->row();
			$this->db->query("delete from purchase_order_request_payment where id='" . $id . "'");
			$this->All_model->DataUpdate('tran_material_po_detail', array('status_pay' => ''), array('status_pay' => $data->no_request));
		}
		if ($data->id_top != '') $this->All_model->DataUpdate('billing_top', array('proses_inv' => '0'), array('id' => $data->id_top));
		$this->db->trans_complete();
		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			$result         = TRUE;
		} else {
			$this->db->trans_rollback();
			$result = FALSE;
		}
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}
	public function view_request($id, $tipetrans)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		if ($tipetrans == "2") {
			$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*,a.nm_barang as nm_material, b.nm_supplier FROM tran_po_detail a LEFT JOIN tran_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		} else {
			$data = $this->db->query("select * from purchase_order_request_payment where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*, b.nm_supplier FROM tran_material_po_detail a LEFT JOIN tran_material_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		}
		$data_payterm 	= $this->db->query("select * from billing_top where no_po='" . $data->no_po . "'")->result();
		$payterm  = $this->db->query("select data2,name from list_help where group_by='top' and data2='" . $data->tipe . "'")->row();

		$def_ppn = $this->All_model->getppn();
		$def_pph = $this->All_model->getpph();
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$combo_coa_pph = $this->All_model->Getcomboparamcoa('pph_pembelian');
		$data = array(
			'title'			=> 'View Request Payment',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'def_ppn'		=> $def_ppn,
			'def_pph'		=> $def_pph,
			'datapoh'		=> $datapoh,
			'datapod'		=> $datapod,
			'data_payterm'	=> $data_payterm,
			'data'			=> $data,
			'payterm'		=> $payterm,
			'tipetrans'		=> $tipetrans,
			'combo_coa_pph'	=> $combo_coa_pph,
			'status'		=> "view",
		);
		history('View Request Payment');
		$this->load->view('Pembayaran_material/form_request', $data);
	}
	public function edit_request($id, $tipetrans)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		if ($tipetrans == "2") {
			$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*, a.nm_barang as nm_material, b.nm_supplier FROM tran_po_detail a LEFT JOIN tran_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		} else {
			$data = $this->db->query("select * from purchase_order_request_payment where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*, b.nm_supplier FROM tran_material_po_detail a LEFT JOIN tran_material_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		}
		$data_payterm 	= $this->db->query("select * from billing_top where no_po='" . $data->no_po . "'")->result();
		$payterm  = $this->db->query("select data2,name from list_help where group_by='top' and data2='" . $data->tipe . "'")->row();
		$def_ppn = $this->All_model->getppn();
		$def_pph = $this->All_model->getpph();
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$combo_coa_pph = $this->All_model->Getcomboparamcoa('pph_pembelian');
		$data = array(
			'title'			=> 'Edit Request Payment',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'def_ppn'		=> $def_ppn,
			'def_pph'		=> $def_pph,
			'datapoh'		=> $datapoh,
			'datapod'		=> $datapod,
			'data_payterm'	=> $data_payterm,
			'data'			=> $data,
			'tipetrans'		=> $tipetrans,
			'combo_coa_pph'	=> $combo_coa_pph,
			'payterm'		=> $payterm
		);
		history('Edit Request Payment');
		$this->load->view('Pembayaran_material/form_request', $data);
	}
	public function request_payment_save()
	{
		$id_req = $this->input->post("id_req");
		$request_date = $this->input->post("request_date");
		$no_po = $this->input->post("no_po");
		$id_supplier = $this->input->post("id_supplier");
		$nilai_ppn = $this->input->post("nilai_ppn");
		$curs_header = $this->input->post("curs_header");
		$nilai_total = $this->input->post("nilai_total");
		$total_bayar = $this->input->post("total_bayar");
		$po_belum_dibayar = $this->input->post("po_belum_dibayar");
		$sisa_dp = $this->input->post("sisa_dp");
		$no_request = $this->input->post("no_request");
		$no_invoice = $this->input->post("no_invoice");
		$nilai_invoice = $this->input->post("nilai_invoice");
		$keterangan = $this->input->post("keterangan");
		$potongan_dp = $this->input->post("potongan_dp");
		$potongan_claim = $this->input->post("potongan_claim");
		$keterangan_potongan = $this->input->post("keterangan_potongan");
		$request_payment = $this->input->post("request_payment");
		$invoice_ppn = $this->input->post("invoice_ppn");
		$nilai_pph_invoice = $this->input->post("nilai_pph_invoice");
		$nilai_po_invoice = $this->input->post("nilai_po_invoice");
		$tipe = $this->input->post("tipe");
		$tipetrans = $this->input->post("tipetrans");
		$payfor = $this->input->post("payfor");
		$coa_pph = $this->input->post("coa_pph");
		$bank_transfer = $this->input->post("bank_transfer");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');

		$this->db->trans_begin();
		$dataheader =  array(
			'nilai_po_invoice' => $nilai_po_invoice,
			'nilai_pph_invoice' => $nilai_pph_invoice,
			'request_payment' => $request_payment,
			'request_date' => $request_date,
			'no_invoice' => $no_invoice,
			'nilai_invoice' => $nilai_invoice,
			'keterangan' => $keterangan,
			'tipe' => $tipe,
			'invoice_ppn' => $invoice_ppn,
			'potongan_dp' => $potongan_dp,
			'potongan_claim' => $potongan_claim,
			'keterangan_potongan' => $keterangan_potongan,
			'coa_pph' => $coa_pph,
			'bank_transfer' => $bank_transfer,
			'modified_on' => date('Y-m-d H:i:s'),
			'modified_by' => $Username
		);
		if ($tipetrans == "2") {
			$this->All_model->DataUpdate('purchase_order_request_payment_nm', $dataheader, array('id' => $id_req));
			$this->All_model->DataUpdate('tran_po_detail', array('status_pay' => ''), array('status_pay' => $no_request));
			if (!empty($payfor)) {
				foreach ($payfor as $val) {
					if ($val != "") $this->All_model->DataUpdate('tran_po_detail', array('status_pay' => $no_request), array('id' => $val));
				}
			}
		} else {
			$this->All_model->DataUpdate('purchase_order_request_payment', $dataheader, array('id' => $id_req));
			$this->All_model->DataUpdate('tran_material_po_detail', array('status_pay' => ''), array('status_pay' => $no_request));
			if (!empty($payfor)) {
				foreach ($payfor as $val) {
					if ($val != "") $this->All_model->DataUpdate('tran_material_po_detail', array('status_pay' => $no_request), array('id' => $val));
				}
			}
		}
		$this->db->trans_complete();
		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			$keterangan     = "SUKSES, simpan data ";
			$result         = TRUE;
		} else {
			$this->db->trans_rollback();
			$keterangan     = "GAGAL, simpan data ";
			$result = FALSE;
			history('Save Edit Request Payment, No ' . $id_req);
		}
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}
	public function approve_request($id, $tipetrans)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		if ($tipetrans == "2") {
			$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*, a.nm_barang as nm_material, b.nm_supplier FROM tran_po_detail a LEFT JOIN tran_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		} else {
			$data = $this->db->query("select * from purchase_order_request_payment where id='" . $id . "'")->row();
			$datapoh = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
			$datapod = array();
			$payterm = array();
			if (!empty($datapoh)) {
				$datapod = $this->db->query("SELECT a.*, b.nm_supplier FROM tran_material_po_detail a LEFT JOIN tran_material_po_header b ON a.no_po=b.no_po WHERE a.no_po='" . $data->no_po . "' AND a.deleted='N'")->result();
			}
		}
		$data_payterm 	= $this->db->query("select * from billing_top where no_po='" . $data->no_po . "'")->result();
		$payterm  = $this->db->query("select data2,name from list_help where group_by='top' and data2='" . $data->tipe . "'")->row();
		$def_ppn = $this->All_model->getppn();
		$def_pph = $this->All_model->getpph();
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$combo_coa_pph = $this->All_model->Getcomboparamcoa('pph_pembelian');
		$data = array(
			'title'			=> 'Edit Request Payment',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'def_ppn'		=> $def_ppn,
			'def_pph'		=> $def_pph,
			'datapoh'		=> $datapoh,
			'datapod'		=> $datapod,
			'data_payterm'	=> $data_payterm,
			'data'			=> $data,
			'tipetrans'		=> $tipetrans,
			'status'		=> 'approve',
			'combo_coa_pph'	=> $combo_coa_pph,
			'payterm'		=> $payterm
		);
		history('Approval Request Payment');
		$this->load->view('Pembayaran_material/form_request', $data);
	}
	public function save_approve_request($id, $tipetrans)
	{
		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		if ($tipetrans == "2") {
			$this->All_model->DataUpdate('purchase_order_request_payment_nm', array('status' => '1', 'approved_by' => $Username, 'approved_on' => $dateTime), array('id' => $id));
		} else {
			$this->All_model->DataUpdate('purchase_order_request_payment', array('status' => '1', 'approved_by' => $Username, 'approved_on' => $dateTime), array('id' => $id));
		}
		$result = true;
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
		//		redirect('pembayaran_material');
	}

	//==================================================================================================================
	//===============================================PEMBAYARAN=========================================================
	//==================================================================================================================
	public function payment_list()
	{
		$this->template->title('Payment');
		$this->template->render('index_payment_new');
	}

	public function form_payment_new()
	{

		$id_payment = explode(';', $_GET['id_payment']);

		$get_payment = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->get()
			->result();
		$get_supplier = $this->db->get('new_supplier')->result();
		$get_bank = $this->db->get_where('coa_master', ['kode_bank <>' => '', 'kode_bank <>' => null])->result();
		$get_mata_uang = $this->db->get_where('mata_uang', ['deleted_by' => 0, 'activation' => 'active'])->result();

		$data = [
			'id_payment' => implode(',', $id_payment),
			'result_payment' => $get_payment,
			'list_supplier' => $get_supplier,
			'list_bank' => $get_bank,
			'list_mata_uang' => $get_mata_uang
		];
		$this->template->set('results', $data);
		$this->template->render('form_payment_new');
	}


	public function save_payment_new()
	{
		$id_req = $this->input->post("id_req");
		$payment_date = $this->input->post("payment_date");
		$bank_coa = $this->input->post("bank_coa");
		$bank_nilai = $this->input->post("bank_nilai");
		$curs = $this->input->post("curs");
		$id_supplier = $this->input->post("id_supplier");
		$curs_header = $this->input->post("curs_header");

		$biaya_admin_forex = $this->input->post("biaya_admin_forex");
		$biaya_admin = $this->input->post("biaya_admin");
		$curs_admin = $this->input->post("curs_admin");

		$biaya_admin_forex2 = $this->input->post("biaya_admin_forex2");
		$biaya_admin2 = $this->input->post("biaya_admin2");
		$curs_admin2 = $this->input->post("curs_admin2");
		$bank_coa_admin = $this->input->post("bank_coa_admin");

		$nilai_bayar_bank = $this->input->post("nilai_bayar_bank");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		$alokasi_dp = $this->input->post("alokasi_dp");
		$alokasi_hutang = $this->input->post("alokasi_hutang");
		$tipetrans = $this->input->post("tipetrans");
		$selisih_kurs1 = 0;
		$this->db->trans_begin();
		$jenis_jurnal = 'BUK20';
		if ($curs_header != 'IDR') $jenis_jurnal = 'BUK21';
		try {

			$no_payment = $this->All_model->GetAutoGenerate('format_payment');
			$nomor_jurnal = $jenis_jurnal . $no_payment . rand(100, 999);

			$dataheader =  array(
				'no_payment' => $no_payment,
				'id_supplier' => $id_supplier,
				'curs_header' => $curs_header,
				'payment_date' => $payment_date,
				'bank_coa' => $bank_coa,
				'nilai_bayar_bank' => $nilai_bayar_bank,
				'curs' => $curs,
				'bank_nilai' => $bank_nilai,
				'modul' => 'PO',
				'biaya_admin_forex' => $biaya_admin_forex,
				'biaya_admin' => $biaya_admin,
				'curs_admin' => $curs_admin,
				'biaya_admin_forex2' => $biaya_admin_forex2,
				'biaya_admin2' => $biaya_admin2,
				'curs_admin2' => $curs_admin2,
				'bank_coa_admin' => $bank_coa_admin,
				'status' => '1',
				'created_on' => date('Y-m-d H:i:s'),
				'created_by' => $Username
			);

			$this->All_model->dataSave('purchase_order_request_payment_header', $dataheader);
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('1','4','8') order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			$total = 0;
			foreach ($datajurnal1 as $rec) {
				// CASH BANK
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => $nilai_bayar_bank,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK EXPENSE
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK
				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin,
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin2,
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
			}
			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);
			foreach ($id_req as $keys) {
				$this->All_model->DataUpdate('purchase_order_request_payment', array('status' => '2', 'payment_date' => $payment_date, 'no_payment' => $no_payment), array('id' => $keys));
				$data = $this->db->query("select * from purchase_order_request_payment where id='" . $keys . "'")->row();
				$selisih_kurs = 0;
				$nilai_terima_barang_idr = 0;
				$datapoheader = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->terima_barang_idr != 0) {
					$kurs_hutang = $datapoheader->kurs_terima;
					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $data->nilai_po_invoice * $datapoheader->kurs_terima);

					if ($selisih_kurs < 0) {
						$selisih_kurs1 = $selisih_kurs * (-1);
					} else {
						$selisih_kurs1 = $selisih_kurs;
					}
				} else {

					$kurs_hutang = $data->kurs_receive_invoice;

					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $data->nilai_po_invoice * $data->kurs_receive_invoice);

					if ($selisih_kurs < 0) {
						$selisih_kurs1 = $selisih_kurs * (-1);
					} else {
						$selisih_kurs1 = $selisih_kurs;
					}
				}


				// update PO
				$nilai_dp_kurs = 0;
				$addsql = "";
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $curs);
					$addsql = ", nilai_dp_kurs=" . $nilai_dp_kurs . "";
				}

				if ($data->tipe == 'TR-01') {
					$this->db->query("update tran_material_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . "),
				bayar_kurs=(bayar_kurs+" . ($data->nilai_po_invoice) . "),
				bayar_idr=(bayar_idr+" . ($data->nilai_po_invoice * $curs) . ")
				" .
						($data->tipe == 'TR-01' ?
							",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
							", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
						" where no_po='" . $data->no_po . "'");
				}

				if ($data->tipe == 'TR-02') {
					$this->db->query("update tran_material_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . "),
				bayar_kurs=(bayar_kurs+" . ($data->nilai_po_invoice) . "),
				bayar_idr=(bayar_idr+" . ($data->nilai_po_invoice * $curs) . "),
				sisa_hutang_kurs=(sisa_hutang_kurs-" . ($data->nilai_po_invoice) . "),
				sisa_hutang_idr=(sisa_hutang_idr-" . ($data->nilai_po_invoice * $curs) . ")				
				" .
						($data->tipe == 'TR-01' ?
							",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
							", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
						" where no_po='" . $data->no_po . "'");
				}

				$keterangan		= 'Pembayaran ' . $no_payment;
				$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
				$data_supplier 	= $this->db->query("select * from supplier where id_supplier='" . $data->id_supplier . "'")->row();
				$data_coa2 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='2'")->row();

				if ($data->curs_header == 'IDR') {
					$coahutang = '2101-01-01';
				} else {
					$coahutang = '2101-01-04';
				}

				if ($data->tipe == 'TR-01') {
					$datahutang = array(
						'tipe'       	 => 'BUK',
						'nomor'       	 => $Nomor_JV,
						'tanggal'        => $tanggal,
						'no_perkiraan'   => $coahutang,
						'keterangan'     => $keterangan,
						'no_reff'     	 => $data->no_po,
						'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
						'kredit'         => 0,
						'id_supplier'    => $data->id_supplier,
						'nama_supplier'  => $data_supplier->nm_supplier,
						'no_request'     => $no_payment,
						'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
						'kredit_usd'	=> 0,

					);
					$this->db->insert('tr_kartu_hutang', $datahutang);
				}


				if ($data->tipe == 'TR-02') {
					$datahutang = array(
						'tipe'       	 => 'BUK',
						'nomor'       	 => $Nomor_JV,
						'tanggal'        => $tanggal,
						'no_perkiraan'   => $coahutang,
						'keterangan'     => $keterangan,
						'no_reff'     	 => $data->no_po,
						'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $kurs_hutang),
						'kredit'         => 0,
						'id_supplier'    => $data->id_supplier,
						'nama_supplier'  => $data_supplier->nm_supplier,
						'no_request'     => $no_payment,
						'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
						'kredit_usd'	=> 0,

					);
					$this->db->insert('tr_kartu_hutang', $datahutang);
				}

				$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('2','3','5','6','7','9') order by parameter_no")->result();
				foreach ($datajurnal1 as $rec) {
					if ($data->modul == 'PO') {
						// UANG MUKA
						if ($rec->parameter_no == "2") {
							if ($data->tipe == 'TR-01') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => round(($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'nilai_valas_debet' => ($data->nilai_po_invoice + $data->invoice_ppn),
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								if ($data->potongan_dp > 0) {
									$det_Jurnaltes1[] = array(
										'nomor' => $nomor_jurnal,
										'tanggal' => $payment_date,
										'tipe' => 'BUK',
										'no_perkiraan' => $rec->no_perkiraan,
										'keterangan' => $data->keterangan,
										'no_request' => $data->no_po,
										'debet' => 0,
										'kredit' => 0,
										'nilai_valas_debet' => 0,
										'nilai_valas_kredit' => 0,
										'no_reff' => $no_payment,
										'jenis_jurnal' => $jenis_jurnal,
										'nocust' => $data->id_supplier,
										'stspos' => '1'
									);
								}
							}
						}
						// HUTANG
						if ($rec->parameter_no == "3") {
							if ($data->tipe == 'TR-02') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => round((($data->nilai_po_invoice + $data->invoice_ppn) * $kurs_hutang)),
									'nilai_valas_debet' => ($data->nilai_po_invoice + $data->invoice_ppn),
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => 0,
									'nilai_valas_debet' => 0,
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}

					if ($data->modul == 'FORWARDER') {
						// HUTANG FORWARDER
						if ($rec->parameter_no == "5") {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => 'FORWARDER ',
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => round(($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
								'nilai_valas_debet' => 0,
								'nilai_valas_kredit' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// PPN
					if ($rec->parameter_no == "6") {
						/*
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $data->keterangan, 'no_request' => $data->no_po, 'kredit' => 0, 'debet' => ($data->invoice_ppn*$curs), 'no_reff' => $no_payment, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier
					);
*/
					}
					// PPH
					if ($rec->parameter_no == "7") {
						if ($data->nilai_pph_invoice <> 0) {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $data->coa_pph,
								'keterangan' => $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => round($data->nilai_pph_invoice * $curs),
								'debet' => 0,
								'nilai_valas_debet' => 0,
								'nilai_valas_kredit' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// SELISIH KURS
					if ($rec->parameter_no == "9") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => round($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
							'debet' => round($selisih_kurs >= 0 ? $selisih_kurs : 0),
							'nilai_valas_debet' => 0,
							'nilai_valas_kredit' => 0,
							'no_reff' => $no_payment,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);

			//auto jurnal

			foreach ($det_Jurnaltes1 as $vals) {
				$datadetail = array(
					'tipe'			=> 'BUK',
					'nomor'			=> $Nomor_JV,
					'tanggal'		=> $tanggal,
					'no_perkiraan'	=> $vals['no_perkiraan'],
					'keterangan'	=> $vals['keterangan'],
					'no_reff'		=> $vals['no_reff'],
					'debet'			=> $vals['debet'],
					'kredit'		=> $vals['kredit'],
					'nilai_valas_debet'			=> $vals['nilai_valas_debet'],
					'nilai_valas_kredit'		=> $vals['nilai_valas_kredit'],
				);
				$total = ($total + $vals['debet']);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$keterangan		= 'Pembayaran ' . $no_payment;
			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'jenis_ap'	        => 'V',
				'bayar_kepada'		=> $data_supplier->nm_supplier,
				'kdcab'				=> '101',
				'jenis_reff' 		=> 'BUK',
				'no_reff' 			=> $no_payment,
				'note'				=> $keterangan,
				'user_id'			=> $Username,
				'ho_valid'			=> '',
			);

			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);


			//end auto jurnal

			$this->db->trans_complete();
			if ($this->db->trans_status()) {
				$this->db->trans_commit();
				$result         = TRUE;
				history('Save Payment');
			} else {
				$this->db->trans_rollback();
				$result = FALSE;
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			$result = FALSE;
		}

		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}
	public function view_payment_new($id)
	{
		$list_id_payment = [];
		$get_id_payment = $this->db->select('a.id')->get_where('payment_approve a', ['a.id_payment' => $id])->result();
		foreach ($get_id_payment as $item_id_payment) {
			$list_id_payment[] = $item_id_payment->id;
		}
		$list_id_payment = implode(';', $list_id_payment);

		$id_payment = explode(';', $list_id_payment);

		$get_payment = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->get()
			->result();
		$get_supplier = $this->db->get('new_supplier')->result();
		$get_bank = $this->db->get_where('coa_master', ['kode_bank <>' => '', 'kode_bank <>' => null])->result();
		$get_mata_uang = $this->db->get_where('mata_uang', ['deleted_by' => 0, 'activation' => 'active'])->result();

		$get_payment_header = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->group_by('a.id_payment')
			->get()
			->row();

		$bank_charge = 0;
		$get_bank_charge = $this->db->get_where('tr_payment_paid a', ['a.id' => $id])->row();
		if (!empty($get_bank_charge)) {
			$bank_charge = $get_bank_charge->bank_charge;
		}

		$data = [
			'id_payment' => implode(',', $id_payment),
			'result_header' => $get_payment_header,
			'result_payment' => $get_payment,
			'list_supplier' => $get_supplier,
			'list_bank' => $get_bank,
			'list_mata_uang' => $get_mata_uang,
			'bank_charge' => $bank_charge
		];
		$this->template->set('results', $data);
		$this->template->render('view_payment_new');
	}
	public function print_payment_new($id)
	{
		$controller	= "pembayaran_material/payment_list";
		$Arr_Akses	= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		$headers = $this->db->query("select * from purchase_order_request_payment_header where id='" . $id . "'")->row();
		$results = $this->pembayaran_material_model->get_data_json_request_payment("no_payment='" . $headers->no_payment . "'");
		$data_Group	= $this->master_model->getArray('groups', array(), 'id', 'name');
		$datacoa	= $this->All_model->GetCoaCombo();
		$data = array(
			'title'			=> 'Form Payment',
			'action'		=> 'index',
			'datacoa'		=> $datacoa,
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'results'		=> $results,
			'headers'			=> $headers,
		);
		history('Form Payment');
		$this->load->view('Pembayaran_material/print_payment_new.php', $data);
	}
	public function view_payment_new_nonmaterial($id)
	{
		$controller	= "pembayaran_material/payment_list";
		$Arr_Akses	= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		$headers = $this->db->query("select * from purchase_order_request_payment_header where id='" . $id . "'")->row();
		$results = $this->pembayaran_material_model->get_data_json_request_payment_nm("no_payment='" . $headers->no_payment . "'");
		$data_Group	= $this->master_model->getArray('groups', array(), 'id', 'name');
		$datacoa	= $this->All_model->GetCoaCombo();
		$data = array(
			'title'			=> 'Form Payment',
			'action'		=> 'index',
			'datacoa'		=> $datacoa,
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'results'		=> $results,
			'data'			=> $headers,
		);
		history('Form Payment');
		$this->load->view('Pembayaran_material/form_payment_new_nonmaterial.php', $data);
	}
	public function print_payment_new_nonmaterial($id)
	{
		$controller	= "pembayaran_material/payment_list";
		$Arr_Akses	= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		$headers = $this->db->query("select * from purchase_order_request_payment_header where id='" . $id . "'")->row();
		$results = $this->pembayaran_material_model->get_data_json_request_payment_nm("no_payment='" . $headers->no_payment . "'");
		$data_Group	= $this->master_model->getArray('groups', array(), 'id', 'name');
		$datacoa	= $this->All_model->GetCoaCombo();
		$data = array(
			'title'			=> 'Form Payment',
			'action'		=> 'index',
			'datacoa'		=> $datacoa,
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'results'		=> $results,
			'headers'			=> $headers,
		);
		history('Form Payment');
		$this->load->view('Pembayaran_material/print_payment_new_nonmaterial.php', $data);
	}

	public function payment_save()
	{
		$id_req = $this->input->post("id_req");
		$payment_date = $this->input->post("payment_date");
		$bank_coa = $this->input->post("bank_coa");
		$bank_nilai = $this->input->post("bank_nilai");
		$curs = $this->input->post("curs");
		$no_request = $this->input->post("no_request");

		$biaya_admin_forex = $this->input->post("biaya_admin_forex");
		$biaya_admin = $this->input->post("biaya_admin");
		$curs_admin = $this->input->post("curs_admin");

		$biaya_admin_forex2 = $this->input->post("biaya_admin_forex2");
		$biaya_admin2 = $this->input->post("biaya_admin2");
		$curs_admin2 = $this->input->post("curs_admin2");

		$nilai_bayar_bank = $this->input->post("nilai_bayar_bank");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		$alokasi_dp = $this->input->post("alokasi_dp");
		$alokasi_hutang = $this->input->post("alokasi_hutang");

		$tipetrans = $this->input->post("tipetrans");
		$this->db->trans_begin();
		$dataheader =  array(
			'payment_date' => $payment_date,
			'bank_coa' => $bank_coa,
			'bank_nilai' => $bank_nilai,
			'biaya_admin_forex' => $biaya_admin_forex,
			'biaya_admin' => $biaya_admin,
			'curs_admin' => $curs_admin,

			'curs' => $curs,
			'nilai_bayar_bank' => $nilai_bayar_bank,
			//			'alokasi_dp' => $alokasi_dp,
			//			'alokasi_hutang' => $alokasi_hutang,
			'biaya_admin_forex2' => $biaya_admin_forex2,
			'biaya_admin2' => $biaya_admin2,
			'curs_admin2' => $curs_admin2,
			'status' => '2',
			'modified_on' => date('Y-m-d H:i:s'),
			'modified_by' => $Username
		);
		$jenis_jurnal = 'BUK20';
		$nomor_jurnal = $jenis_jurnal . $no_request . rand(100, 999);

		// update request
		if ($tipetrans == "2") {
			//non material

			$this->All_model->DataUpdate('purchase_order_request_payment_nm', $dataheader, array('id' => $id_req));
			$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $id_req . "'")->row();
			$selisih_kurs = 0;
			$nilai_terima_barang_kurs = 0;
			if ($data->modul == 'PO') {
				$datapoheader = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->nilai_terima_barang_kurs != 0) {
					$selisih_kurs = ((($data->nilai_po_invoice - $data->potongan_dp) * $data->curs) - $datapoheader->nilai_terima_barang_kurs);
					$nilai_terima_barang_kurs = $datapoheader->nilai_terima_barang_kurs;
				}
				// update PO
				$nilai_dp_kurs = 0;
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $data->curs);
				}
				$this->db->query("update tran_po_header set nilai_dp_kurs=" . $nilai_dp_kurs . ",nilai_terima_barang_kurs=0, total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $data->curs) . ")
				" . ($data->tipe == 'TR-01' ? ",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" : ", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") . " where no_po='" . $data->no_po . "'");
			}
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			foreach ($datajurnal1 as $rec) {
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($data->modul == 'PO') {
					if ($rec->parameter_no == "2") {
						if ($data->tipe == 'TR-01') {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => ($data->nilai_po_invoice * $data->curs),
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						} else {
							if ($data->potongan_dp > 0) {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $no_request . '. ' . $data->keterangan,
									'no_request' => $data->no_po,
									'debet' => 0,
									'kredit' => 0,
									'no_reff' => $no_request,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}
					if ($rec->parameter_no == "3") {
						if ($data->tipe == 'TR-02') {
							if ($nilai_terima_barang_kurs == 0) $nilai_terima_barang_kurs = (($data->nilai_po_invoice + $data->invoice_ppn) * $data->curs);
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => ($nilai_terima_barang_kurs),
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						} else {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => 0,
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
				}
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($data->modul == 'FORWARDER') {
					if ($rec->parameter_no == "5") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $no_request . '. ' . 'FORWARDER ',
							'no_request' => $data->no_po,
							'kredit' => 0,
							'debet' => ($bank_nilai),
							'no_reff' => $no_request,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
				if ($rec->parameter_no == "6") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => ($data->invoice_ppn * $data->curs),
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($rec->parameter_no == "7") {
					//pph
					/* revisi
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $no_request.'. '.$data->keterangan, 'no_request' => $data->no_po, 'kredit' => ($data->nilai_pph_invoice*$data->curs), 'debet' => 0, 'no_reff' => $no_request, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier
					);
*/
					if ($data->nilai_pph_invoice <> 0) {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $data->coa_pph,
							'keterangan' => $no_request . '. ' . $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => ($data->nilai_pph_invoice * $data->curs),
							'debet' => 0,
							'no_reff' => $no_request,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}

				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => $data->biaya_admin,
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => $data->biaya_admin2,
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($rec->parameter_no == "9") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => ($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
						'debet' => ($selisih_kurs >= 0 ? $selisih_kurs : 0),
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);
		} else {

			$this->All_model->DataUpdate('purchase_order_request_payment', $dataheader, array('id' => $id_req));
			$data = $this->db->query("select * from purchase_order_request_payment where id='" . $id_req . "'")->row();
			$selisih_kurs = 0;
			$nilai_terima_barang_idr = 0;
			if ($data->modul == 'PO') {
				$datapoheader = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->total_terima_barang_idr != 0) {
					if ($data->curs <> 1) {
						$kursterimabarang = ($datapoheader->total_terima_barang_idr / $datapoheader->nilai_terima_barang_kurs);
						$nilai_terima_barang_idr = ($data->nilai_po_invoice * $kursterimabarang);
						$selisih_kurs = ((($data->nilai_po_invoice - $data->potongan_dp) * $data->curs) - $nilai_terima_barang_idr);
					} else {
						$nilai_terima_barang_idr = (($data->nilai_po_invoice - $data->potongan_dp) * $data->curs);
					}
				}
				// update PO
				$nilai_dp_kurs = 0;
				$addsql = "";
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $data->curs);
				} else {					//$addsql=",nilai_terima_barang_kurs=(nilai_terima_barang_kurs-".$data->nilai_po_invoice."),total_terima_barang_idr=(total_terima_barang_idr-".(($data->nilai_po_invoice-$data->potongan_dp)*$data->curs).")";
				}
				$this->db->query("update tran_material_po_header set nilai_dp_kurs=" . $nilai_dp_kurs . "
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $data->curs) . ")
				" . ($data->tipe == 'TR-01' ? ",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" : ", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") . " where no_po='" . $data->no_po . "'");
			}
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			foreach ($datajurnal1 as $rec) {
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($data->modul == 'PO') {
					if ($rec->parameter_no == "2") {
						if ($data->tipe == 'TR-01') {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => ($data->nilai_po_invoice * $data->curs),
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						} else {
							if ($data->potongan_dp > 0) {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $no_request . '. ' . $data->keterangan,
									'no_request' => $data->no_po,
									'debet' => 0,
									'kredit' => 0,
									'no_reff' => $no_request,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}
					if ($rec->parameter_no == "3") {
						if ($data->tipe == 'TR-02') {
							if ($nilai_terima_barang_idr == 0) $nilai_terima_barang_idr = (($data->nilai_po_invoice + $data->invoice_ppn) * $data->curs);
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => ($nilai_terima_barang_idr),
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						} else {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $no_request . '. ' . $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => 0,
								'no_reff' => $no_request,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
				}
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($data->modul == 'FORWARDER') {
					if ($rec->parameter_no == "5") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $no_request . '. ' . 'FORWARDER ',
							'no_request' => $data->no_po,
							'kredit' => 0,
							'debet' => ($bank_nilai),
							'no_reff' => $no_request,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
				if ($rec->parameter_no == "6") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => 0,
						'debet' => ($data->invoice_ppn * $data->curs),
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($rec->parameter_no == "7") {
					//pph
					if ($data->nilai_pph_invoice <> 0) {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $data->coa_pph,
							'keterangan' => $no_request . '. ' . $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => ($data->nilai_pph_invoice * $data->curs),
							'debet' => 0,
							'no_reff' => $no_request,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}

					/* revisi
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $no_request.'. '.$data->keterangan, 'no_request' => $data->no_po, 'kredit' => ($data->nilai_pph_invoice*$data->curs), 'debet' => 0, 'no_reff' => $no_request, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier, 'stspos' => '1'
					);
*/
				}
				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => $data->biaya_admin,
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => $data->biaya_admin2,
						'debet' => 0,
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
				if ($rec->parameter_no == "9") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $no_request . '. ' . $data->keterangan,
						'no_request' => $data->no_po,
						'kredit' => ($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
						'debet' => ($selisih_kurs >= 0 ? $selisih_kurs : 0),
						'no_reff' => $no_request,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $data->id_supplier,
						'stspos' => '1'
					);
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);
			//auto jurnal
			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);
			$total = 0;
			foreach ($det_Jurnaltes1 as $vals) {
				$datadetail = array(
					'tipe'			=> 'BUK',
					'nomor'			=> $Nomor_JV,
					'tanggal'		=> $tanggal,
					'no_perkiraan'	=> $vals['no_perkiraan'],
					'keterangan'	=> $vals['keterangan'],
					'no_reff'		=> $vals['no_reff'],
					'debet'			=> $vals['debet'],
					'kredit'		=> $vals['kredit'],
				);
				$total = ($total + $vals['debet']);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$keterangan		= 'Pembayaran ' . $no_reff;
			$data_supplier 	= $this->db->query("select * from supplier where id_supplier='" . $data->id_supplier . "'")->row();
			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'jenis_ap'	        => 'V',
				'bayar_kepada'		=> $data_supplier->nm_supplier,
				'kdcab'				=> '101',
				'jenis_reff' 		=> 'BUK',
				'no_reff' 			=> $no_reff,
				'note'				=> $keterangan,
				'user_id'			=> $Username,
				'ho_valid'			=> '',
			);

			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
			$datahutang = array(
				'tipe'       	 => 'BUK',
				'nomor'       	 => $Nomor_JV,
				'tanggal'        => $tanggal,
				'no_perkiraan'   => $data_coa->no_perkiraan,
				'keterangan'     => $keterangan,
				'no_reff'     	 => $data->no_po,
				'debet'      	 => $this->input->post("request_payment"),
				'kredit'         => 0,
				'id_supplier'    => $data->id_supplier,
				'nama_supplier'  => $data_supplier->nm_supplier,
				'no_request'     => $no_request,
			);
			$this->db->insert('tr_kartu_hutang', $datahutang);

			//end auto jurnal

		}

		$this->db->trans_complete();
		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			$result         = TRUE;
		} else {
			$this->db->trans_rollback();
			$result = FALSE;
			history('Save Payment');
		}
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}
	//==================================================================================================================
	//================================================== PAYMENT JURNAL ================================================
	//==================================================================================================================
	public function payment_jurnal()
	{

		$controller			= ucfirst(strtolower($this->uri->segment(1)) . '/' . strtolower($this->uri->segment(2)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}
		$this->data_status 	= array('0' => 'Baru', '1' => 'Posting');
		$results 			= $this->pembayaran_material_model->get_data_json_jurnal(" (jenis_jurnal='BUK20') ");
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		//        $data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$data = array(
			'title'			=> 'Payment List Jurnal',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'data_status'	=> $this->data_status,
			'results'		=> $results,
		);
		history('List Payment Jurnal');
		$this->load->view('Pembayaran_material/index_jurnal', $data);
	}
	public function ros_jurnal()
	{
		$data = $this->pembayaran_material_model->get_data_json_jurnal(" (jenis_jurnal='JV033' or jenis_jurnal='JV032') ");
		$this->template->set('results', $data);
		$this->data_status = array('0' => 'Baru', '1' => 'Posting');
		$ENABLE_ADD     = has_permission('ROS_Jurnal.Add');
		$ENABLE_MANAGE  = has_permission('ROS_Jurnal.Manage');
		$ENABLE_VIEW    = has_permission('ROS_Jurnal.View');
		$ENABLE_DELETE  = has_permission('ROS_Jurnal.Delete');
		$this->template->set('ENABLE_ADD', $ENABLE_ADD);
		$this->template->set('ENABLE_MANAGE', $ENABLE_MANAGE);
		$this->template->set('ENABLE_VIEW', $ENABLE_VIEW);
		$this->template->set('ENABLE_DELETE', $ENABLE_DELETE);
		$this->template->set('data_status', $this->data_status);
		$this->template->title('ROS Jurnal');
		$this->template->render('index_jurnal');
	}
	public function closing_po_jurnal()
	{
		$data = $this->pembayaran_material_model->get_data_json_jurnal(" (jenis_jurnal='JV034') ");
		$this->template->set('results', $data);
		$this->data_status = array('0' => 'Baru', '1' => 'Posting');
		$ENABLE_ADD     = has_permission('Closing_PO_Jurnal.Add');
		$ENABLE_MANAGE  = has_permission('Closing_PO_Jurnal.Manage');
		$ENABLE_VIEW    = has_permission('Closing_PO_Jurnal.View');
		$ENABLE_DELETE  = has_permission('Closing_PO_Jurnal.Delete');
		$this->template->set('ENABLE_ADD', $ENABLE_ADD);
		$this->template->set('ENABLE_MANAGE', $ENABLE_MANAGE);
		$this->template->set('ENABLE_VIEW', $ENABLE_VIEW);
		$this->template->set('ENABLE_DELETE', $ENABLE_DELETE);
		$this->template->set('data_status', $this->data_status);
		$this->template->title('ROS Jurnal');
		$this->template->render('index_jurnal');
	}
	public function view_jurnal($id)
	{
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}

		$data = $this->db->query("select * from jurnaltras where nomor='" . $id . "' order by kredit,debet,no_perkiraan")->result();
		$datacoa	= $this->All_model->GetCoaCombo();
		$datapayterm  = $this->db->query("select data2,name from list_help where group_by='top' order by urut")->result();
		$payterm = array();
		foreach ($datapayterm as $key => $val) {
			$payterm[$val->data2] = $val->name;
		}
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$data = array(
			'title'			=> 'View Jurnal',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'payterm'		=> $payterm,
			'datacoa'		=> $datacoa,
			'data'			=> $data,
			'status'		=> "view",
		);
		history('View Jurnal');
		$this->load->view('Pembayaran_material/form_jurnal', $data);
	}
	public function edit_jurnal($id)
	{

		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$Arr_Akses			= getAcccesmenu($controller);
		if ($Arr_Akses['read'] != '1') {
			$this->session->set_flashdata("alert_data", "<div class=\"alert alert-warning\" id=\"flash-message\">You Don't Have Right To Access This Page, Please Contact Your Administrator....</div>");
			redirect(site_url('dashboard'));
		}

		$data = $this->db->query("select * from jurnaltras where nomor='" . $id . "' order by kredit,debet,no_perkiraan")->result();
		$datacoa	= $this->All_model->GetCoaCombo();
		$datapayterm  = $this->db->query("select data2,name from list_help where group_by='top' order by urut")->result();
		$payterm = array();
		foreach ($datapayterm as $key => $val) {
			$payterm[$val->data2] = $val->name;
		}
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$data = array(
			'title'			=> 'Edit Jurnal',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'akses_menu'	=> $Arr_Akses,
			'payterm'		=> $payterm,
			'datacoa'		=> $datacoa,
			'data'			=> $data,
		);
		history('Edit Jurnal');
		$this->load->view('Pembayaran_material/form_jurnal', $data);
	}
	public function jurnal_save()
	{
		$id = $this->input->post("id");
		$no_perkiraan = $this->input->post("no_perkiraan");
		$keterangan = $this->input->post("keterangan");
		$debet = $this->input->post("debet");
		$kredit = $this->input->post("kredit");

		$tanggal		= $this->input->post('tanggal');
		$tipe			= $this->input->post('tipe');
		$no_reff        = $this->input->post('no_reff');
		$no_request		= $this->input->post('no_request');
		$jenis_jurnal	= $this->input->post('jenis_jurnal');
		$nocust         = $this->input->post('nocust');
		$total			= 0;
		$total_po		= $this->input->post('total_po');
		$data_vendor 	= $this->db->query("select * from supplier where id_supplier='" . $nocust . "'")->row();
		$nama_vendor = $data_vendor->nm_supplier;
		$Bln 			= substr($tanggal, 5, 2);
		$Thn 			= substr($tanggal, 0, 4);
		if ($tipe == 'BUK') $Nomor_JV = $this->Jurnal_model->get_no_buk('101');
		if ($tipe == 'JV') $Nomor_JV = $this->jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl);

		$session = $this->session->userdata('app_session');
		$data_session	= $this->session->userdata;

		$this->db->trans_begin();
		for ($i = 0; $i < count($id); $i++) {
			$dataheader =  array(
				'stspos' => "1",
				'no_perkiraan' => $no_perkiraan[$i],
				'keterangan' => $keterangan[$i],
				'debet' => $debet[$i],
				'kredit' => $kredit[$i]
			);
			$total = ($total + $debet[$i]);
			$this->All_model->DataUpdate('jurnaltras', $dataheader, array('id' => $id[$i]));

			if ($debet[$i] == 0 && $kredit[$i] == 0) {
			} else {
				$datadetail = array(
					'tipe'        	=> $tipe,
					'nomor'       	=> $Nomor_JV,
					'tanggal'     	=> $tanggal,
					'no_reff'     	=> $no_reff,
					'no_perkiraan'	=> $no_perkiraan[$i],
					'keterangan' 	=> $keterangan[$i],
					'debet' 		=> $debet[$i],
					'kredit' 		=> $kredit[$i]
				);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}
		}
		$keterangan		= 'Pembayaran';
		if ($tipe == 'BUK') {
			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'kdcab'				=> '101',
				'jenis_reff'	    => 'BUK',
				'no_reff' 		    => $no_reff,
				'customer' 		    => $nama_vendor,
				'bayar_kepada'      => $nama_vendor,
				'jenis_ap'			=> 'V',
				'note'				=> $keterangan,
				'user_id'			=> $session['username'],
				'ho_valid'			=> '',
				'batal'			    => '0'
			);
			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
			$datahutang = array(
				'tipe'       	 => $tipe,
				'nomor'       	 => $Nomor_JV,
				'tanggal'        => $tanggal,
				'no_perkiraan'    => $data_coa->no_perkiraan,
				'keterangan'      => $keterangan,
				'no_reff'     	  => $no_reff,
				'debet'      	  => $total_po,
				'kredit'          => 0,
				'id_supplier'     => $nocust,
				'nama_supplier'   => $nama_vendor,
				'no_request'      => $no_request,
			);
			$this->db->insert('tr_kartu_hutang', $datahutang);
		}
		if ($tipe == 'JV') {
			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tgl,
				'jml'	            => $total,
				'koreksi_no'		=> '-',
				'kdcab'				=> '101',
				'jenis'			    => 'JV',
				'keterangan' 		=> $keterangan,
				'bulan'				=> $Bln,
				'tahun'				=> $Thn,
				'user_id'			=> $data_session['ORI_User']['username'],
				'memo'			    => $no_reff,
				'tgl_jvkoreksi'	    => $tgl,
				'ho_valid'			=> ''
			);

			$this->db->insert(DBACC . '.javh', $dataJVhead);
			$Qry_Update_Cabang_acc = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);
		}


		$this->db->trans_complete();
		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			$result         = TRUE;
			history('Save Jurnal');
		} else {
			$this->db->trans_rollback();
			$result = FALSE;
		}
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}

	public function save_payment_new_nonmaterial()
	{
		$id_req = $this->input->post("id_req");
		$payment_date = $this->input->post("payment_date");
		$bank_coa = $this->input->post("bank_coa");
		$bank_nilai = $this->input->post("bank_nilai");
		$curs = $this->input->post("curs");
		$id_supplier = $this->input->post("id_supplier");
		$curs_header = $this->input->post("curs_header");

		$biaya_admin_forex = $this->input->post("biaya_admin_forex");
		$biaya_admin = $this->input->post("biaya_admin");
		$curs_admin = $this->input->post("curs_admin");

		$biaya_admin_forex2 = $this->input->post("biaya_admin_forex2");
		$biaya_admin2 = $this->input->post("biaya_admin2");
		$curs_admin2 = $this->input->post("curs_admin2");
		$bank_coa_admin = $this->input->post("bank_coa_admin");

		$nilai_bayar_bank = $this->input->post("nilai_bayar_bank");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		$alokasi_dp = $this->input->post("alokasi_dp");
		$alokasi_hutang = $this->input->post("alokasi_hutang");
		$tipetrans = $this->input->post("tipetrans");
		$this->db->trans_begin();
		$jenis_jurnal = 'BUK20';
		if ($curs_header != 'IDR') $jenis_jurnal = 'BUK21';
		try {

			$no_payment = $this->All_model->GetAutoGenerate('format_payment');
			$nomor_jurnal = $jenis_jurnal . $no_payment . rand(100, 999);

			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);

			$dataheader =  array(
				'no_payment' => $no_payment,
				'id_supplier' => $id_supplier,
				'curs_header' => $curs_header,
				'payment_date' => $payment_date,
				'bank_coa' => $bank_coa,
				'nilai_bayar_bank' => $nilai_bayar_bank,
				'curs' => $curs,
				'bank_nilai' => $bank_nilai,
				'modul' => 'PO',
				'tipe' => 'nonmaterial',
				'biaya_admin_forex' => $biaya_admin_forex,
				'biaya_admin' => $biaya_admin,
				'curs_admin' => $curs_admin,
				'biaya_admin_forex2' => $biaya_admin_forex2,
				'biaya_admin2' => $biaya_admin2,
				'curs_admin2' => $curs_admin2,
				'bank_coa_admin' => $bank_coa_admin,
				'status' => '1',
				'created_on' => date('Y-m-d H:i:s'),
				'created_by' => $Username
			);

			$this->All_model->dataSave('purchase_order_request_payment_header', $dataheader);
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('1','4','8') order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			foreach ($datajurnal1 as $rec) {
				// CASH BANK
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK EXPENSE
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK
				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin,
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin2,
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
			}
			foreach ($id_req as $keys) {
				$this->All_model->DataUpdate('purchase_order_request_payment_nm', array('status' => '2', 'payment_date' => $payment_date, 'no_payment' => $no_payment), array('id' => $keys));
				$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $keys . "'")->row();
				$selisih_kurs = 0;
				$nilai_terima_barang_idr = 0;
				$datapoheader = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->terima_barang_idr != 0) {
					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $datapoheader->terima_barang_idr);
				}
				// update PO
				$nilai_dp_kurs = 0;
				$addsql = "";
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $curs);
					$addsql = ", nilai_dp_kurs=" . $nilai_dp_kurs . "";
				}
				$this->db->query("update tran_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . ")
				" .
					($data->tipe == 'TR-01' ?
						",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
						", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
					" where no_po='" . $data->no_po . "'");

				$keterangan		= 'Pembayaran ' . $no_payment;
				$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
				$data_supplier 	= $this->db->query("select * from supplier where id_supplier='" . $data->id_supplier . "'")->row();
				$datahutang = array(
					'tipe'       	 => 'BUK',
					'nomor'       	 => $Nomor_JV,
					'tanggal'        => $tanggal,
					'no_perkiraan'   => $data_coa->no_perkiraan,
					'keterangan'     => $keterangan,
					'no_reff'     	 => $data->no_po,
					'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
					'kredit'         => 0,
					'id_supplier'    => $data->id_supplier,
					'nama_supplier'  => $data_supplier->nm_supplier,
					'no_request'     => $no_payment,
					'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
					'kredit_usd'	=> 0,
				);
				$this->db->insert('tr_kartu_hutang', $datahutang);


				if ($data->curs_header == 'IDR') {
					$coahutang = '2101-01-01';
				} else {
					$coahutang = '2101-01-04';
				}


				$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('2','3','5','6','7','9') order by parameter_no")->result();
				foreach ($datajurnal1 as $rec) {
					if ($data->modul == 'PO') {
						// UANG MUKA
						if ($rec->parameter_no == "2") {
							if ($data->tipe == 'TR-01') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								if ($data->potongan_dp > 0) {
									$det_Jurnaltes1[] = array(
										'nomor' => $nomor_jurnal,
										'tanggal' => $payment_date,
										'tipe' => 'BUK',
										'no_perkiraan' => $coahutang,
										'keterangan' => $data->keterangan,
										'no_request' => $data->no_po,
										'debet' => 0,
										'kredit' => 0,
										'no_reff' => $no_payment,
										'jenis_jurnal' => $jenis_jurnal,
										'nocust' => $data->id_supplier,
										'stspos' => '1'
									);
								}
							}
						}
						// HUTANG
						if ($rec->parameter_no == "3") {
							if ($data->tipe == 'TR-02') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}

					if ($data->modul == 'FORWARDER') {
						// HUTANG FORWARDER
						if ($rec->parameter_no == "5") {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => 'FORWARDER ',
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// PPN
					if ($rec->parameter_no == "6") {
						/*
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $data->keterangan, 'no_request' => $data->no_po, 'kredit' => 0, 'debet' => ($data->invoice_ppn*$curs), 'no_reff' => $no_payment, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier
					);
*/
					}
					// PPH
					if ($rec->parameter_no == "7") {
						if ($data->nilai_pph_invoice <> 0) {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => ($data->nilai_pph_invoice * $curs),
								'debet' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// SELISIH KURS
					if ($rec->parameter_no == "9") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => ($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
							'debet' => ($selisih_kurs >= 0 ? $selisih_kurs : 0),
							'no_reff' => $no_payment,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);


			//auto jurnal
			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);
			$total = 0;
			foreach ($det_Jurnaltes1 as $vals) {
				$datadetail = array(
					'tipe'			=> 'BUK',
					'nomor'			=> $Nomor_JV,
					'tanggal'		=> $tanggal,
					'no_perkiraan'	=> $vals['no_perkiraan'],
					'keterangan'	=> $vals['keterangan'],
					'no_reff'		=> $vals['no_reff'],
					'debet'			=> $vals['debet'],
					'kredit'		=> $vals['kredit'],
				);
				$total = ($total + $vals['debet']);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'jenis_ap'	        => 'V',
				'bayar_kepada'		=> $data_supplier->nm_supplier,
				'kdcab'				=> '101',
				'jenis_reff' 		=> 'BUK',
				'no_reff' 			=> $no_payment,
				'note'				=> $keterangan,
				'user_id'			=> $Username,
				'ho_valid'			=> '',
			);

			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			//end auto jurnal

			$this->db->trans_complete();
			if ($this->db->trans_status()) {
				$this->db->trans_commit();
				$result         = TRUE;
				history('Save Payment');
			} else {
				$this->db->trans_rollback();
				$result = FALSE;
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			$result = FALSE;
		}

		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}

	public function list_request_payment($jenis_payment)
	{
		if ($jenis_payment == 1) {
			$results = $this->db
				->select('a.id, a.created_on, a.no_doc, a.currency, a.jumlah, a.keperluan')
				->from('payment_approve a')
				->join('tr_expense b', 'b.no_doc = a.no_doc')
				->where('a.status <>', 2)
				->where('b.exp_inv_po', 1)
				->group_by('a.id')
				->order_by('a.created_on', 'DESC')
				->get()
				->result();
		} else {
			$results = $this->db
				->select('a.id, a.created_on, a.no_doc, a.currency, a.jumlah, a.keperluan')
				->from('payment_approve a')
				->join('tr_expense b', 'b.no_doc = a.no_doc', 'left')
				->where('a.status <>', 2)
				->group_start()
				->where('b.exp_inv_po <>', 1)
				->or_where('b.exp_inv_po', null)
				->group_end()
				->group_by('a.id')
				->order_by('a.created_on', 'DESC')
				->get()
				->result();
		}

		$this->template->set('results', $results);
		$this->template->title('List Request Payment');
		$this->template->render('list_request_payment');
	}

	public function check_payment()
	{
		$id = $this->input->post('id');
		$checked = $this->input->post('checked');

		$this->db->trans_start();

		if ($checked == 1) {
			$this->db->insert('tr_choosed_payment', [
				'id_user' => $this->auth->user_id(),
				'id_payment' => $id
			]);
		} else {
			$this->db->delete('tr_choosed_payment', ['id_user' => $this->auth->user_id(), 'id_payment' => $id]);
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
		} else {
			$this->db->trans_commit();
		}
	}

	public function clear_choosed_payment()
	{
		$id_user = $this->auth->user_id();

		$this->db->trans_start();

		$this->db->delete('tr_choosed_payment', ['id_user' => $id_user]);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$valid = 0;
		} else {
			$this->db->trans_commit();
			$valid = 1;
		}

		echo json_encode([
			'status' => $valid
		]);
	}

	public function proses_payment()
	{
		$id_user = $this->auth->user_id();

		$arr_choosed_payment = [];
		$get_choosed_payment = $this->db->query("SELECT * FROM tr_choosed_payment WHERE id_user = '" . $id_user . "'")->result();
		foreach ($get_choosed_payment as $item) {
			$arr_choosed_payment[] = $item->id_payment;
		}

		echo json_encode([
			'count_choosed_payment' => count($get_choosed_payment),
			'arr_choosed_payment' => implode(';', $arr_choosed_payment)
		]);
	}

	public function used_choosed_payment()
	{
		$this->db->trans_start();

		$this->db->delete('tr_choosed_payment', ['id_user' => $this->auth->user_id()]);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
		} else {
			$this->db->trans_commit();
		}
	}

	public function payment_modal()
	{
		$id = $this->input->post('id');

		$get_payment = $this->db->get_where('payment_approve', array('id' => $id))->row();

		$get_coa_bank = $this->db->select('a.no_perkiraan, a.nama')->from('coa_master a')->like('a.nama', 'Bank ', 'after')->get()->result();

		if ($get_payment->tipe == 'kasbon') {
			$get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $get_payment->no_doc))->row();

			$this->template->set('data_kasbon', $get_kasbon_header);
			$this->template->set('data_payment', $get_payment);
			$this->template->set('list_coa_bank', $get_coa_bank);
			$this->template->render('kasbon_modal');
		}
		if ($get_payment->tipe == 'expense') {
			$get_expense_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $get_payment->no_doc))->row();

			$this->template->set('data_expense', $get_expense_header);
			$this->template->set('data_payment', $get_payment);
			$this->template->set('list_coa_bank', $get_coa_bank);
			$this->template->render('expense_modal');
		}
	}

	public function save_payment()
	{
		$post = $this->input->post();

		$this->load->library('upload');

		$config['upload_path'] = './uploads/bukti_pembayaran/';
		$config['allowed_types'] = '*';
		$config['remove_spaces'] = TRUE;
		$config['encrypt_name'] = TRUE;

		$bukti_transfer = '';
		$this->upload->initialize($config);
		if ($this->upload->do_upload('bukti_transfer')) {
			$uploadData = $this->upload->data();
			$bukti_transfer = $uploadData['file_name'];
		}

		$config2['upload_path'] = './uploads/upload_document_payment/';
		$config2['allowed_types'] = '*';
		$config2['remove_spaces'] = TRUE;
		$config2['encrypt_name'] = TRUE;

		$upload_document = '';
		$this->upload->initialize($config2);
		if ($this->upload->do_upload('upload_document')) {
			$uploadData = $this->upload->data();
			$upload_document = $uploadData['file_name'];
		}

		$data_update = [
			'status' => 2,
			'nilai_bayar' => str_replace(',', '', $post['nilai_bayar']),
			'tanggal_pembayaran' => $post['tanggal_pembayaran'],
			'id_bank_pembayaran' => $post['bank'],
			'keterangan_pembayaran' => $post['keterangan'],
			'bukti_transfer' => $bukti_transfer,
			'upload_document' => $upload_document
		];

		$this->db->trans_begin();

		$this->db->update('payment_approve', $data_update, array('id' => $post['no_payment']));

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();

			$valid = 0;
			$msg = 'Please try again later !';
		} else {
			$this->db->trans_commit();

			$valid = 1;
			$msg = 'Data has been paid !';
		}

		echo json_encode([
			'status' => $valid,
			'msg' => $msg
		]);
	}

	public function revisi_payment()
	{
		$id = $this->input->post('id');

		$this->db->trans_begin();

		$data_update = array(
			'nilai_bayar' => null,
			'tanggal_pembayaran' => null,
			'id_bank_pembayaran' => null,
			'bukti_transfer' => null,
			'upload_document' => null,
			'keterangan_pembayaran' => null,
			'status' => 1
		);
		$this->db->update('payment_approve', $data_update, array('id' => $id));

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();

			$valid = 0;
			$msg = 'Please try again later !';
		} else {
			$this->db->trans_commit();

			$valid = 1;
			$msg = 'Data has been updated successfully !';
		}

		echo json_encode([
			'status' => $valid,
			'msg' => $msg
		]);
	}

	public function print2($id)
	{
		ob_clean();
		ob_start();
		$this->auth->restrict($this->managePermission);
		$id = $this->uri->segment(3);
		$data['data_payment'] = $this->db->get_where('payment_approve', array('id' => $id))->row();
		if($data['data_payment']->tipe == 'kasbon') {
			$get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $data['data_payment']->no_doc))->row();
			if($get_kasbon_header->tipe == '1') {
				$tipe = 'Kasbon Subcont';
			}
			if($get_kasbon_header->tipe == '2') {
				$tipe = 'Kasbon Akomodasi';
			}
			if($get_kasbon_header->tipe == '3') {
				$tipe = 'Kasbon Others';
			}
		} else {
			$tipe = ucfirst($data['data_payment']->tipe);
		}
		$data['tipe'] = $tipe;
		
		$get_coa = $this->db->get_where('coa_master', array('no_perkiraan' => $data['data_payment']->id_bank_pembayaran))->row();

		$data['nm_bank'] = (!empty($get_coa)) ? $data['data_payment']->id_bank_pembayaran.' - '.$get_coa->nama : '';
		
		$this->load->view('print', $data);
		$html = ob_get_contents();

		require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
		$html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(0, 0, 0, 0));
		$html2pdf->pdf->SetDisplayMode('fullpage');
		$html2pdf->WriteHTML($html);
		ob_end_clean();
		$html2pdf->Output('Print Payment (' . $id . ').pdf', 'I');
	}

	public function get_data_payment()
	{
		$this->Pembayaran_material_model->get_data_payment();
	}
}
