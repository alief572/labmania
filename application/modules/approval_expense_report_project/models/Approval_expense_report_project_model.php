<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Approval_expense_report_project_model extends BF_Model
{

	protected $ENABLE_ADD;
	protected $ENABLE_MANAGE;
	protected $ENABLE_DELETE;
	protected $ENABLE_VIEW;

	protected $otherdb;
	protected $gl;

	public function __construct()
	{
		parent::__construct();

		$this->ENABLE_ADD     = has_permission('Approval_expense_Report_Project.Add');
		$this->ENABLE_MANAGE  = has_permission('Approval_expense_Report_Project.Manage');
		$this->ENABLE_VIEW    = has_permission('Approval_expense_Report_Project.View');
		$this->ENABLE_DELETE  = has_permission('Approval_expense_Report_Project.Delete');

		$this->otherdb = $this->load->database('sendigs_finance', TRUE);
		$this->gl = $this->load->database('gl_sendigs', TRUE);
	}

	public function generate_id_invoice_jurnal($nomor)
	{
		$Ym             = date('ym');
		$srcMtr            = "SELECT MAX(id) as maxP FROM tr_jurnal WHERE no_jurnal LIKE '%" . int_to_roman(date('m')) . "-" . date('-y') . "%' ";
		$resultMtr        = $this->otherdb->query($srcMtr)->result_array();
		$angkaUrut2        = $resultMtr[0]['maxP'];
		$urutan2        = (int)substr($angkaUrut2, 0, 5);
		$urutan2 = $urutan2 + $nomor;
		$urut2            = sprintf('%05s', $urutan2);
		$kode_trans        = $urut2 . '-AJV-' . int_to_roman(date('m')) . '-' . date('y');

		return $kode_trans;
	}

	public function generate_id_invoice_jurnal_pph21($nomor)
	{
		$Ym             = date('ym');
		$srcMtr            = "SELECT MAX(id) as maxP FROM tr_tabungan_pph21 WHERE no_tabungan LIKE '%" . int_to_roman(date('m')) . "-" . date('-y') . "%' ";
		$resultMtr        = $this->otherdb->query($srcMtr)->result_array();
		$angkaUrut2        = $resultMtr[0]['maxP'];
		$urutan2        = (int)substr($angkaUrut2, 0, 5);
		$urutan2 = $urutan2 + $nomor;
		$urut2            = sprintf('%05s', $urutan2);
		$kode_trans        = $urut2 . '-AJV-PPH21-' . int_to_roman(date('m')) . '-' . date('y');

		return $kode_trans;
	}

	function generate_id_expense_report_header()
	{
		$Ym             = date('ym');
		$srcMtr            = "SELECT MAX(id) as maxP FROM kons_tr_expense_report_project_header WHERE id LIKE '%/EXP/H/" . date('Y') . "%' ";
		$resultMtr        = $this->db->query($srcMtr)->result_array();
		$angkaUrut2        = $resultMtr[0]['maxP'];
		$urutan2        = (int)substr($angkaUrut2, 0, 4);
		$urutan2++;
		$urut2            = sprintf('%04s', $urutan2);
		$kode_trans        = $urut2 . '/EXP/H/' . date('Y');

		return $kode_trans;
	}

	function GetAutoGenerate($tipe)
	{
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
			return $newcode;
		} else {
			return false;
		}
	}

	public function list_jurnal_pph21($id_header)
	{
		$get_expense = $this->db->get_where('kons_tr_expense_report_project_header', ['id_header' => $id_header])->row();
		$get_expense_detail = $this->db->get_where('kons_tr_expense_report_project_detail', ['id_header_expense' => $get_expense->id])->result();

		$get_kasbon = $this->db->get_where('kons_tr_kasbon_project_header', ['id' => $id_header])->row();

		$get_penawaran = $this->db->get_where('kons_tr_penawaran', ['id_quotation' => $get_kasbon->id_penawaran])->row();
		$get_company = $this->db->get_where('kons_tr_company', ['id' => $get_penawaran->company])->row();

		$id_company = (!empty($get_company)) ? $get_company->id : '';
		$nm_company = (!empty($get_company)) ? $get_company->nm_company : '';

		$hasil = '';
		$nominal_pph = 0;

		if (!empty($get_kasbon) && $get_kasbon->tipe == '2') {
			$nominal = 0;

			$this->db->select('(a.qty_expense * a.nominal_expense) as ttl_expense');
			$this->db->from('kons_tr_expense_report_project_detail a');
			$this->db->join('kons_tr_spk_budgeting_akomodasi b', 'b.id = a.id_detail_kasbon');
			$this->db->where('a.id_header_kasbon', $id_header);
			$this->db->where('b.id_item', '15');
			$get_poin_pph = $this->db->get()->result();

			foreach ($get_poin_pph as $item_pph) :
				$nominal += $item_pph->ttl_expense;
			endforeach;

			$nominal_w_pph = (100 / (100 - 10) * $nominal);
			$nominal_pph = ($nominal_w_pph - $nominal);

			$coa_pph = '1106-01-01';
			$get_coa_pph = $this->gl->get_where('coa_master', ['no_perkiraan' => $coa_pph])->row();

			$hasil .= '<tr>';

			$hasil .= '<td class="text-center">';
			$hasil .= date('d F Y');
			$hasil .= '<input type="hidden" name="jurnal_pph[1][tanggal_jurnal]" value="' . date('Y-m-d') . '">';
			$hasil .= '</td>';

			$hasil .= '<td>';
			$hasil .= $get_coa_pph->no_perkiraan;
			$hasil .= '<input type="hidden" name="jurnal_pph[1][coa]" value="' . $get_coa_pph->no_perkiraan . '">';
			$hasil .= '</td>';

			$hasil .= '<td>';
			$hasil .= $nm_company;
			$hasil .= '<input type="hidden" name="jurnal_pph[1][id_company]" value="' . $id_company . '">';
			$hasil .= '<input type="hidden" name="jurnal_pph[1][nm_company]" value="' . $nm_company . '">';
			$hasil .= '</td>';

			$hasil .= '<td>';
			$hasil .= $get_coa_pph->nama;
			$hasil .= '<input type="hidden" name="jurnal_pph[1][nm_coa]" value="' . $get_coa_pph->nama . '">';
			$hasil .= '</td>';

			$hasil .= '<td>';
			$hasil .= 'PPh 21';
			$hasil .= '<input type="hidden" name="jurnal_pph[1][keterangan]" value="PPh 21">';
			$hasil .= '</td>';

			$hasil .= '<td class="text-right">';
			$hasil .= number_format(0);
			$hasil .= '<input type="hidden" name="jurnal_pph[1][debit]" value="0">';
			$hasil .= '</td>';

			$hasil .= '<td class="text-right">';
			$hasil .= number_format($nominal_pph);
			$hasil .= '<input type="hidden" name="jurnal_pph[1][kredit]" value="' . $nominal_pph . '">';
			$hasil .= '</td>';

			$hasil .= '</tr>';
		}


		$response = [
			'hasil' => $hasil,
			'nominal_pph' => $nominal_pph
		];

		return $response;
	}
}
