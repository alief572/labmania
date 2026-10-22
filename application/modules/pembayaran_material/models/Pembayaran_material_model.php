<?php
class Pembayaran_material_model extends BF_Model
{

	protected $viewPermission   = 'Payment.View';
	protected $addPermission    = 'Payment.Add';
	protected $managePermission = 'Payment.Manage';
	protected $deletePermission = 'Payment.Delete';

	public function __construct()
	{
		parent::__construct();
	}
	public function get_data_json_request_payment_header($sqlwhere = '')
	{
		$sql = "SELECT a.*, b.nm_supplier FROM purchase_order_request_payment_header a left join supplier b on a.id_supplier =b.id_supplier WHERE 1=1 " . ($sqlwhere == '' ? '' : " and " . $sqlwhere) . " order by a.id desc ";
		$query = $this->db->query($sql);
		return $query->result();
	}
	public function get_data_json_request_payment($sqlwhere = '')
	{

		$sql = "SELECT a.*, b.nm_supplier FROM purchase_order_request_payment a left join supplier b on a.id_supplier =b.id_supplier WHERE 1=1 " . ($sqlwhere == '' ? '' : " and " . $sqlwhere) . " order by a.id desc ";
		$query = $this->db->query($sql);
		return $query->result();
	}
	public function get_data_json_request_payment_nm($sqlwhere = '')
	{

		$sql = "SELECT a.*, b.nm_supplier FROM purchase_order_request_payment_nm a left join supplier b on a.id_supplier =b.id_supplier WHERE 1=1 " . ($sqlwhere == '' ? '' : " and " . $sqlwhere) . " order by a.no_po desc ";
		$query = $this->db->query($sql);
		return $query->result();
	}
	public function get_data_json_jurnal($sqlwhere = '')
	{

		$sql = "SELECT nomor,tanggal,no_reff,stspos FROM jurnaltras a WHERE 1=1 " . ($sqlwhere == '' ? '' : " and " . $sqlwhere) . " group by nomor,tanggal,no_reff,stspos order by no_reff desc ";
		$query = $this->db->query($sql);
		return $query->result();
	}

	public function generate_id_payment_paid($kode_bank = null, $tanggal)
	{
		$generate_id = $this->db->query("SELECT MAX(id) AS max_id FROM tr_payment_paid WHERE id LIKE '%BK-" . $kode_bank . "-" . date('my-', strtotime($tanggal)) . "%'")->row();
		$kodeBarang = $generate_id->max_id;
		$urutan = (int) substr($kodeBarang, 11, 4);
		$urutan++;
		$tahun = date('my-', strtotime($tanggal));
		$huruf = "BK-" . $kode_bank . "-";
		$kodecollect = $huruf . $tahun . sprintf("%04s", $urutan);

		return $kodecollect;
	}

	public function get_data_payment()
	{
		$data = $this->input->post();

		$draw = $data['draw'];
		$start = $data['start'];
		$length = $data['length'];
		$search = $data['search'];

		$where_1 = '';
		$where_2 = '';

		if (!empty($search['value'])) {
			// $where_1 = ' AND (
			// 	a.id LIKE "%' . $search['value'] . '%" OR
			// 	DATE_FORMAT(a.created_on, "%Y-%m-%d") LIKE "%' . $search['value'] . '%" OR
			// 	c.nm_customer LIKE "%' . $search['value'] . '%" OR
			// 	c.id_spk_penawaran LIKE "%' . $search['value'] . '%" OR
			// 	IF(b.tipe = "1", "Kasbon Subcont", IF(b.tipe = "2", "Kasbon Akomodasi", IF(b.tipe = "3", "Kasbon Others", "")) LIKE "%' . $search['value'] . '%" OR
			// 	a.tipe LIKE "%' . $search['value'] . '%" OR
			// 	b.bank LIKE "%' . $search['value'] . '%" OR
			// 	b.bank_number LIKE "%' . $search['value'] . '%" OR
			// 	b.bank_account LIKE "%' . $search['value'] . '%" OR
			// 	a.nilai_bayar LIKE "%' . $search['value'] . '%" OR
			// 	DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") LIKE "%' . $search['value'] . '%" OR
			// 	a.id_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.nm_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.keterangan_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.bank LIKE "%' . $search['value'] . '%"
			// )';

			// $where_2 = ' AND (
			// 	a.id LIKE "%' . $search['value'] . '%" OR
			// 	DATE_FORMAT(a.created_on, "%Y-%m-%d") LIKE "%' . $search['value'] . '%" OR
			// 	d.nm_customer LIKE "%' . $search['value'] . '%" OR
			// 	d.id_spk_penawaran LIKE "%' . $search['value'] . '%" OR
			// 	IF(c.tipe = "1", "Kasbon Subcont", IF(c.tipe = "2", "Kasbon Akomodasi", IF(c.tipe = "3", "Kasbon Others", "")) LIKE "%' . $search['value'] . '%" OR
			// 	a.tipe LIKE "%' . $search['value'] . '%" OR
			// 	b.bank LIKE "%' . $search['value'] . '%" OR
			// 	b.bank_number LIKE "%' . $search['value'] . '%" OR
			// 	b.bank_account LIKE "%' . $search['value'] . '%" OR
			// 	a.nilai_bayar LIKE "%' . $search['value'] . '%" OR
			// 	DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") LIKE "%' . $search['value'] . '%" OR
			// 	a.id_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.nm_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.keterangan_pembayaran LIKE "%' . $search['value'] . '%" OR
			// 	a.bank LIKE "%' . $search['value'] . '%"
			// )';

			$where_1 = '
				AND (
					z.id LIKE "%' . $search['value'] . '%" OR
					z.no_doc LIKE "%' . $search['value'] . '%" OR
					z.tanggal_pengajuan LIKE "%' . $search['value'] . '%" OR
					z.keperluan LIKE "%' . $search['value'] . '%" OR
					z.tipe LIKE "%' . $search['value'] . '%" OR
					z.jumlah LIKE "%' . $search['value'] . '%" OR
					z.status LIKE "%' . $search['value'] . '%" OR
					z.keterangan_pembayaran LIKE "%' . $search['value'] . '%" OR
					z.id_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
					z.nm_bank_pembayaran LIKE "%' . $search['value'] . '%" OR
					z.bukti_transfer LIKE "%' . $search['value'] . '%" OR
					z.bank LIKE "%' . $search['value'] . '%" OR
					z.bank_number LIKE "%' . $search['value'] . '%" OR
					z.bank_account LIKE "%' . $search['value'] . '%" OR
					z.tipe_data LIKE "%' . $search['value'] . '%" OR
					z.nm_customer LIKE "%' . $search['value'] . '%" OR
					z.id_spk_penawaran LIKE "%' . $search['value'] . '%" OR
					z.tanggal_pembayaran LIKE "%' . $search['value'] . '%"
				)
			';
		}

		$sql_query = '
			SELECT
				z.id,
				z.no_doc,
				z.tanggal_pengajuan,
				z.keperluan,
				z.tipe,
				z.jumlah,
				z.status,
				z.keterangan_pembayaran,
				z.id_bank_pembayaran,
				z.nm_bank_pembayaran,
				z.bukti_transfer,
				z.bank,
				z.bank_number,
				z.bank_account,
				z.tipe_data,
				z.nm_customer,
				z.id_spk_penawaran,
				z.tanggal_pembayaran,
				z.tipe2
			FROM
				(
					SELECT
						a.id as id,
						a.no_doc as no_doc,
						DATE_FORMAT(a.created_on, "%Y-%m-%d") as tanggal_pengajuan,
						a.keperluan as keperluan,
						a.tipe as tipe,
						a.jumlah as jumlah,
						a.status as status,
						a.keterangan_pembayaran as keterangan_pembayaran,
						a.id_bank_pembayaran as id_bank_pembayaran,
						d.nama as nm_bank_pembayaran,
						a.bukti_transfer as bukti_transfer,
						b.bank as bank,
						b.bank_number as bank_number,
						b.bank_account as bank_account,
						b.tipe as tipe_data,
						c.nm_customer as nm_customer,
						c.id_spk_penawaran as id_spk_penawaran,
						DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") as tanggal_pembayaran,
						"" as tipe2
					FROM
						payment_approve a
						JOIN kons_tr_kasbon_project_header b ON b.id = a.no_doc
						JOIN kons_tr_spk_penawaran c ON c.id_spk_penawaran = b.id_spk_penawaran
						LEFT JOIN coa_master d ON d.no_perkiraan = a.id_bank_pembayaran
					UNION ALL

					SELECT
						a.id as id,
						a.no_doc as no_doc,
						DATE_FORMAT(a.created_on, "%Y-%m-%d") as tanggal_pengajuan,
						a.keperluan as keperluan,
						a.tipe as tipe,
						a.jumlah as jumlah,
						a.status as status,
						a.keterangan_pembayaran as keterangan_pembayaran,
						a.id_bank_pembayaran as id_bank_pembayaran,
						e.nama as nm_bank_pembayaran,
						a.bukti_transfer as bukti_transfer,
						b.bank as bank,
						b.bank_number as bank_number,
						b.bank_account as bank_account,
						c.tipe as tipe_data,
						d.nm_customer as nm_customer,
						d.id_spk_penawaran as id_spk_penawaran,
						DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") as tanggal_pembayaran,
						"Expense" as tipe2
					FROM
						payment_approve a
						JOIN kons_tr_expense_report_project_header b ON b.id = a.no_doc
						JOIN kons_tr_kasbon_project_header c ON c.id = b.id_header
						JOIN kons_tr_spk_penawaran d ON d.id_spk_penawaran = c.id_spk_penawaran
						LEFT JOIN coa_master e ON e.no_perkiraan = a.id_bank_pembayaran
				) z
			WHERE
				1 = 1 ' . $where_1 . '
			ORDER BY z.id DESC
			LIMIT ' . $length . ' OFFSET ' . $start . '
		';

		$get_data = $this->db->query($sql_query);

		$sql_query_all = '
			SELECT
				z.id,
				z.no_doc,
				z.tanggal_pengajuan,
				z.keperluan,
				z.tipe,
				z.jumlah,
				z.status,
				z.keterangan_pembayaran,
				z.id_bank_pembayaran,
				z.nm_bank_pembayaran,
				z.bukti_transfer,
				z.bank,
				z.bank_number,
				z.bank_account,
				z.tipe_data,
				z.nm_customer,
				z.id_spk_penawaran,
				z.tanggal_pembayaran,
				z.tipe2
			FROM
				(
					SELECT
						a.id as id,
						a.no_doc as no_doc,
						DATE_FORMAT(a.created_on, "%Y-%m-%d") as tanggal_pengajuan,
						a.keperluan as keperluan,
						a.tipe as tipe,
						a.jumlah as jumlah,
						a.status as status,
						a.keterangan_pembayaran as keterangan_pembayaran,
						a.id_bank_pembayaran as id_bank_pembayaran,
						d.nama as nm_bank_pembayaran,
						a.bukti_transfer as bukti_transfer,
						b.bank as bank,
						b.bank_number as bank_number,
						b.bank_account as bank_account,
						b.tipe as tipe_data,
						c.nm_customer as nm_customer,
						c.id_spk_penawaran as id_spk_penawaran,
						DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") as tanggal_pembayaran,
						"" as tipe2
					FROM
						payment_approve a
						JOIN kons_tr_kasbon_project_header b ON b.id = a.no_doc
						JOIN kons_tr_spk_penawaran c ON c.id_spk_penawaran = b.id_spk_penawaran
						LEFT JOIN coa_master d ON d.no_perkiraan = a.id_bank_pembayaran
					UNION ALL

					SELECT
						a.id as id,
						a.no_doc as no_doc,
						DATE_FORMAT(a.created_on, "%Y-%m-%d") as tanggal_pengajuan,
						a.keperluan as keperluan,
						a.tipe as tipe,
						a.jumlah as jumlah,
						a.status as status,
						a.keterangan_pembayaran as keterangan_pembayaran,
						a.id_bank_pembayaran as id_bank_pembayaran,
						e.nama as nm_bank_pembayaran,
						a.bukti_transfer as bukti_transfer,
						b.bank as bank,
						b.bank_number as bank_number,
						b.bank_account as bank_account,
						c.tipe as tipe_data,
						d.nm_customer as nm_customer,
						d.id_spk_penawaran as id_spk_penawaran,
						DATE_FORMAT(a.tanggal_pembayaran, "%d %M %Y") as tanggal_pembayaran,
						"Expense" as tipe2
					FROM
						payment_approve a
						JOIN kons_tr_expense_report_project_header b ON b.id = a.no_doc
						JOIN kons_tr_kasbon_project_header c ON c.id = b.id_header
						JOIN kons_tr_spk_penawaran d ON d.id_spk_penawaran = c.id_spk_penawaran
						LEFT JOIN coa_master e ON e.no_perkiraan = a.id_bank_pembayaran
				) z
			WHERE
				1 = 1 ' . $where_1 . '
			ORDER BY z.id DESC
		';

		$get_data_all = $this->db->query($sql_query_all);

		$hasil = [];

		$no = (0 + $start);
		foreach ($get_data->result() as $item) {
			$no++;

			if ($item->tipe_data == '1') {
				$tipe_data = 'Kasbon Subcont';
			}
			if ($item->tipe_data == '2') {
				$tipe_data = 'Kasbon Akomodasi';
			}
			if ($item->tipe_data == '3') {
				$tipe_data = 'Kasbon Others';
			}

			if($item->tipe2 !== '') {
				$tipe_data = $item->tipe2;
			}

			$status = '<button type="button" class="btn btn-sm btn-warning">Draft</button>';
			if ($item->status == '2') {
				$status = '<button type="button" class="btn btn-sm btn-success">Success</button>';
			}

			$info_transfer = '<span style="font-weight: bold;">Bank</span> : ' . $item->bank . '<br>';
			$info_transfer .= '<span style="font-weight: bold;">Bank Number</span> : ' . $item->bank_number . '<br>';
			$info_transfer .= '<span style="font-weight: bold;">Bank Account</span> : ' . $item->bank_account . '<br>';

			$get_coa_bank = $this->db->select('a.no_perkiraan, a.nama')->from('coa_master a')->like('a.no_perkiraan', '1101-02-', 'both')->not_like('a.no_perkiraan', '00', 'both')->get()->result();

			$input_select_bank = '<select name="dt[' . $no . '][bank]" class="form-control form-control-sm">';
			$input_select_bank .= '<option value="">- Select Bank -</option>';
			foreach ($get_coa_bank as $item_bank) {
				$input_select_bank .= '<option value="' . $item_bank->no_perkiraan . '">' . $item_bank->nama . '</option>';
			}
			$input_select_bank .= '</select>';

			$action = '';
			if(has_permission($this->managePermission)) {
				$action = '<button type="button" class="btn btn-sm btn-success payment" data-id="' . $item->id . '" data-no="' . $no . '" data-tipe="' . $item->tipe . '"><i class="fa fa-money"></i> Pay</button>';
				if ($item->status == '2') {
					$action = '<button type="button" class="btn btn-sm btn-danger revisi" data-id="' . $item->id . '" data-no="' . $no . '"><i class="fa fa-arrow-left"></i> Revision</button>';

					$action .= ' <a href="'.base_url('pembayaran_material/print2/'.$item->id).'" class="btn btn-sm btn-info" title="Print Payment" target="_blank"><i class="fa fa-print"></i> Print</a>';
				}
			}

			$bukti_transfer = '';
			if ($item->bukti_transfer !== '' && $item->bukti_transfer !== null && file_exists('./uploads/bukti_pembayaran/' . $item->bukti_transfer)) {
				$bukti_transfer = '<a href="' . base_url('uploads/bukti_pembayaran/' . $item->bukti_transfer) . '" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i> View</a>';
			}

			$hasil[] = [
				'no' => $no,
				'no_payment' => $item->id,
				'tanggal_pengajuan' => date('d F Y', strtotime($item->tanggal_pengajuan)),
				'keperluan' => implode(', ', [$item->nm_customer, $item->id_spk_penawaran, $tipe_data]),
				'kategori' => $tipe_data,
				'info_transfer' => $info_transfer,
				'nilai_bayar' => number_format($item->jumlah, 2),
				'tanggal_pembayaran' => $item->tanggal_pembayaran,
				'keterangan' => $item->keterangan_pembayaran,
				'bank' => $item->nm_bank_pembayaran,
				'bukti_transfer' => $bukti_transfer,
				'status' => $status,
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
