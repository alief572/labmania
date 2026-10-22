<?php
$ENABLE_ADD     = has_permission('Approval_Request_Payment_Checker.Add');
$ENABLE_MANAGE  = has_permission('Approval_Request_Payment_Checker.Manage');
$ENABLE_VIEW    = has_permission('Approval_Request_Payment_Checker.View');
$ENABLE_DELETE  = has_permission('Approval_Request_Payment_Checker.Delete');

$box_kasbon_subcont = 'd-none';
$box_kasbon_akomodasi = 'd-none';
$box_kasbon_others = 'd-none';

if ($tipe == 'Kasbon Subcont') {
	$box_kasbon_subcont = '';
}
if ($tipe == 'Kasbon Akomodasi') {
	$box_kasbon_akomodasi = '';
}
if ($tipe == 'Kasbon Others') {
	$box_kasbon_others = '';
}

?>
<!-- <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script> -->
<style>
	.btn {
		border-radius: 10px;
	}

	.dropdown-menu {
		top: 100%;
		position: absolute;
		overflow: auto;
	}

	.pd-5 {
		padding: 5px;
	}

	.form-inline .form-control {
		width: auto;
		/* Let elements adjust automatically */
		max-width: 100%;
		/* Prevent overflow */
	}

	.form-inline {
		display: flex;
		/* Use flexbox for better alignment */
		justify-content: flex-start;
		/* Align items to the left */
		flex-wrap: nowrap;
		/* Prevent wrapping to the next line */
	}

	.top-total-project {
		width: 280px;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 15px;
	}

	.pd-5 {
		padding: 5px;
	}

	.valign-top {
		vertical-align: top;
	}

	.mt-5 {
		margin-top: 5px;
	}

	.dropdown-menu {

		position: absolute;
		top: 100%;
		/* Position below the button */
		right: 0;
		/* Align with left edge */
	}
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>

<input type="hidden" name="id" value="<?= $id ?>">

<div class="box">
	<div class="box-header">

	</div>

	<div class="box-body" style="z-index: 1 !important;">
		<table border="0" style="width: 100%; z-index: 1 !important;">
			<tr>
				<th class="pd-5 valign-top" width="150">No. SPK</th>
				<td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->id_spk_penawaran ?></td>
				<th class="pd-5 valign-top" width="150">Project Leader</th>
				<td class="pd-5 valign-top" width="400"><?= ucfirst($data_spk_penawaran->nm_project_leader) ?></td>
			</tr>
			<tr>
				<th class="pd-5 valign-top" width="150">Customer</th>
				<td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->nm_customer ?></td>
				<th class="pd-5 valign-top" width="150">Sales</th>
				<td class="pd-5 valign-top" width="400"><?= ucfirst($data_spk_penawaran->nm_sales) ?></td>
			</tr>
			<tr>
				<th class="pd-5 valign-top" width="150">Address</th>
				<td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->alamat ?></td>
				<th class="pd-5 valign-top" width="150">Waktu</th>
				<td class="pd-5 valign-top" width="400">
					<div class="form-inline">
						<div class="form-group">
							<input type="date" name="" id="" class="form-control form-control-sm" value="<?= $data_spk_penawaran->waktu_from ?>" readonly>
						</div>
						<div class="form-group text-center" style="width: 50px; padding-top: 8px;">
							<span>-</span>
						</div>
						<div class="form-group">
							<input type="date" name="" id="" class="form-control form-control-sm" value="<?= $data_spk_penawaran->waktu_to ?>" readonly>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<th class="pd-5 valign-top" width="150">Project</th>
				<td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->nm_paket ?></td>
				<th class="pd-5 valign-top" width="150">Keperluan</th>
				<td class="pd-5 valign-top" width="400">
					<textarea name="" id="" class="form-control form-control-sm" readonly><?= $data_spk_penawaran->nm_customer . ', ' . $data_spk_penawaran->id_spk_penawaran . ', ' . $tipe ?></textarea>
				</td>
			</tr>
		</table>
	</div>
</div>

<div class="box <?= $box_kasbon_subcont ?>">
	<div class="box-header">
		<h4 style="font-weight: 800;">List Item Subcont</h4>
	</div>

	<div class="box-body">
		<table class="table custom-table">
			<thead>
				<tr>
					<th rowspan="2" class="text-center" valign="middle">No.</th>
					<th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
					<th colspan="2" class="text-center">Estimasi</th>
					<th rowspan="2" class="text-center" valign="middle">Total Budget</th>
					<th colspan="3" class="text-center">Pengajuan</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Qty</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Budget</th>
				</tr>
				<tr>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Total Pengajuan</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$no_subcont = 0;

				$ttl_qty_pengajuan = 0;
				$ttl_pengajuan = 0;
				foreach ($data_kasbon_subcont as $item) {
					$no_subcont++;

					$sisa_qty = ($item->aktual_terpakai - $item->qty_pengajuan);
					$sisa_budget = ($item->sisa_budget - $item->total_pengajuan);

					echo '<tr>';
					echo '<td class="text-center">' . $no_subcont . '</td>';
					echo '<td class="text-left">' . $item->nm_aktifitas . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
					echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
					echo '<td class="text-center">' . number_format($sisa_qty, 2) . '</td>';
					echo '<td class="text-right">' . number_format($sisa_budget, 2) . '</td>';
					echo '</tr>';

					$ttl_qty_pengajuan += $item->qty_pengajuan;
					$ttl_pengajuan += $item->total_pengajuan;
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="text-right">Total</td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center ttl_qty_pengajuan"><?= number_format($ttl_qty_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-right ttl_pengajuan"><?= number_format($ttl_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="box <?= $box_kasbon_akomodasi ?>">
	<div class="box-header">
		<h4 style="font-weight: 800;">List Item Others</h4>
	</div>

	<div class="box-body">
		<table class="table custom-table">
			<thead>
				<tr>
					<th rowspan="2" class="text-center" valign="middle">No.</th>
					<th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
					<th colspan="2" class="text-center">Estimasi</th>
					<th rowspan="2" class="text-center" valign="middle">Total Budget</th>
					<th colspan="3" class="text-center">Pengajuan</th>
					<th rowspan="2" class="text-center" valign="middle">Qty Budget Tambahan</th>
					<th rowspan="2" class="text-center" valign="middle">Budget Tambahan</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Qty</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Budget</th>
				</tr>
				<tr>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Total Pengajuan</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$no_subcont = 0;

				$ttl_qty_pengajuan = 0;
				$ttl_pengajuan = 0;
				foreach ($data_kasbon_akomodasi as $item) {
					$no_subcont++;

					$sisa_qty = ($item->aktual_terpakai + $item->qty_budget_tambahan - $item->qty_pengajuan);
					$sisa_budget = ($item->sisa_budget + $item->budget_tambahan - $item->total_pengajuan);

					echo '<tr>';
					echo '<td class="text-center">' . $no_subcont . '</td>';
					echo '<td class="text-left">' . $item->nm_item . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
					echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_budget_tambahan) . '</td>';
					echo '<td class="text-right">' . number_format($item->budget_tambahan, 2) . '</td>';
					echo '<td class="text-center">' . number_format($sisa_qty, 2) . '</td>';
					echo '<td class="text-right">' . number_format($sisa_budget, 2) . '</td>';
					echo '</tr>';

					$ttl_qty_pengajuan += $item->qty_pengajuan;
					$ttl_pengajuan += $item->total_pengajuan;
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="text-right">Total</td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center ttl_qty_pengajuan"><?= number_format($ttl_qty_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-right ttl_pengajuan"><?= number_format($ttl_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="box <?= $box_kasbon_others ?>">
	<div class="box-header">
		<h4 style="font-weight: 800;">List Item Others</h4>
	</div>

	<div class="box-body">
		<table class="table custom-table">
			<thead>
				<tr>
					<th rowspan="2" class="text-center" valign="middle">No.</th>
					<th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
					<th colspan="2" class="text-center">Estimasi</th>
					<th rowspan="2" class="text-center" valign="middle">Total Budget</th>
					<th colspan="3" class="text-center">Pengajuan</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Qty</th>
					<th rowspan="2" class="text-center" valign="middle">Sisa Budget</th>
				</tr>
				<tr>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Qty</th>
					<th class="text-center">Price / Unit</th>
					<th class="text-center">Total Pengajuan</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$no_subcont = 0;

				$ttl_qty_pengajuan = 0;
				$ttl_pengajuan = 0;
				foreach ($data_kasbon_others as $item) {
					$no_subcont++;

					$sisa_qty = ($item->aktual_terpakai - $item->qty_pengajuan);
					$sisa_budget = ($item->sisa_budget - $item->total_pengajuan);

					echo '<tr>';
					echo '<td class="text-center">' . $no_subcont . '</td>';
					echo '<td class="text-left">' . $item->nm_item . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
					echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
					echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
					echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
					echo '<td class="text-center">' . number_format($sisa_qty, 2) . '</td>';
					echo '<td class="text-right">' . number_format($sisa_budget, 2) . '</td>';
					echo '</tr>';

					$ttl_qty_pengajuan += $item->qty_pengajuan;
					$ttl_pengajuan += $item->total_pengajuan;
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="text-right">Total</td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
					<td class="text-center ttl_qty_pengajuan"><?= number_format($ttl_qty_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-right ttl_pengajuan"><?= number_format($ttl_pengajuan, 2) ?></td>
					<td class="text-center"></td>
					<td class="text-center"></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class="box">
	<div class="box-body">
		<div class="col-md-6">
			<div class="form-group">
				<label for="">Reject Reason</label>
				<textarea class="form-control form-control-sm reject_reason" name="reject_reason"></textarea>
			</div>
		</div>
	</div>
</div>

<a href="<?= base_url('request_payment/list_approve_checker') ?>" class="btn btn-sm btn-danger">
	<i class="fa fa-arrow-left"></i> Back
</a>
<button type="button" class="btn btn-sm btn-danger" id="reject">
	<i class="fa fa-close"></i> Reject
</button>
<button type="button" class="btn btn-sm btn-success" id="approve">
	<i class="fa fa-check"></i> Approve
</button>

<script src="<?= base_url('assets/js/number-divider.min.js') ?>"></script>
<script type="text/javascript">
	var url_save = siteurl + 'request_payment/save_approval_checker';
	var url_reject = siteurl + 'request_payment/reject_approval';
	$('.divide').divide();

	$(document).on('click', '.master_check', function() {
		const checked = $(this).is(':checked');
		$('.check_item').prop('checked', false)
		if (checked) {
			$('.check_item').prop('checked', true)
		}
	})

	//Save
	$(document).on('click', '#approve', function(e) {
		var errors = "";
		if ($("#bank_coa").val() == "0") errors = "Bank tidak boleh kosong";

		swal({
				title: "Anda Yakin?",
				text: "Item Akan Di Approve!",
				type: "info",
				showCancelButton: true,
				confirmButtonText: "Ya, Approve!",
				cancelButtonText: "Tidak!",
				closeOnConfirm: false,
				closeOnCancel: true
			},
			function(isConfirm) {
				if (isConfirm) {
					var id = $('input[name="id"]').val();
					$.ajax({
						url: url_save,
						dataType: "json",
						type: 'POST',
						data: {
							'id': id
						},
						success: function(msg) {
							if (msg['save'] == '1') {
								swal({
									title: "Sukses!",
									text: "Data Berhasil Di Approve",
									type: "success",
									timer: 1500,
									showConfirmButton: false
								});
								location.href = siteurl + active_controller + 'list_approve_checker';
							} else {
								swal({
									title: "Gagal!",
									text: "Data Gagal Di Approve",
									type: "error",
									timer: 1500,
									showConfirmButton: false
								});
							};
							console.log(msg);
						},
						error: function(msg) {
							swal({
								title: "Gagal!",
								text: "Ajax Data Gagal Di Proses",
								type: "error",
								timer: 1500,
								showConfirmButton: false
							});
							console.log(msg);
						}
					});
				}
			});
	});

	$(document).on('click', '#reject', function(e) {
		var id = $('input[name="id"]').val();
		var reject_reason = $('.reject_reason').val();

		if (reject_reason !== '') {
			swal({
					title: "Anda Yakin?",
					text: "Item Akan Di Reject!",
					type: "info",
					showCancelButton: true,
					confirmButtonText: "Ya, Reject!",
					cancelButtonText: "Tidak!",
					closeOnConfirm: false,
					closeOnCancel: true
				},
				function(isConfirm) {
					if (isConfirm) {
						var formdata = new FormData($('#frm_data')[0]);
						$.ajax({
							url: url_reject,
							dataType: "json",
							type: 'POST',
							data: {
								'id': id,
								'reject_reason': reject_reason,
								'tingkat_approval': 1
							},
							success: function(msg) {
								if (msg['save'] == '1') {
									swal({
										title: "Sukses!",
										text: "Data Berhasil Di Reject",
										type: "success",
										timer: 1500,
										showConfirmButton: false
									});
									location.href = siteurl + active_controller + 'list_approve_checker';
								} else {
									swal({
										title: "Gagal!",
										text: "Data Gagal Di Reject",
										type: "error",
										timer: 1500,
										showConfirmButton: false
									});
								};
								console.log(msg);
							},
							error: function(msg) {
								swal({
									title: "Gagal!",
									text: "Ajax Data Gagal Di Proses",
									type: "error",
									timer: 1500,
									showConfirmButton: false
								});
								console.log(msg);
							}
						});
					}
				});
		} else {
			swal("Warning!", "Pastikan Reject Reason terisi!", "warning", 3000);
			return false;
		}
	});

	$("#btnxls").click(function() {
		$("#mytabledata").table2excel({
			exclude: ".exclass",
			name: "Weekly Budget",
			filename: "WeeklyBudget.xls", // do include extension
			preserveColors: false // set to true if you want background colors and font colors preserved
		});
	});
</script>