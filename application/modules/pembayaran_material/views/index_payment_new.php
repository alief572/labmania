<link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div id='alert_edit' class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box">
	<div class="box-header">

	</div>
	<div class="box-body">
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="non_material">
				<div class="box-body">
					<table class="table table-bordered table-striped" id="mytabledatanonmaterial" width='100%'>
						<thead>
							<tr class='bg-blue'>
								<th class="text-center">#</th>
								<th class="text-center">No. Payment</th>
								<th class="text-center">Tanggal Pengajuan</th>
								<th class="text-center">Keperluan</th>
								<th class="text-center">Kategori</th>
								<th class="text-center">Info Transfer</th>
								<th class="text-center">Nilai Bayar</th>
								<th class="text-center">Tanggal Pembayaran</th>
								<th class="text-center">Keterangan</th>
								<th class="text-center">Bank</th>
								<th class="text-center">Bukti Transfer</th>
								<th class="text-center">Status</th>
								<th class="text-center">Action</th>
							</tr>
						</thead>
						<tbody>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="modal" id="dialog-popup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel"></h4>
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				</div>
				<div class="modal-body" id="MyModalBody">

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success confirm_jenis_payment"><i class="fa fa-check"></i> Proses</button>
					<button type="button" class="btn btn-danger" data-dismiss="modal">
						<span class="glyphicon glyphicon-remove"></span> Batal</button>
				</div>
			</div>
		</div>
	</div>
	<div id="form-data">
	</div>

	<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<!-- page script -->
	<script>
		$(document).ready(function() {
			DataTables();
		});

		$(document).on('click', '.payment', function() {
			var id = $(this).data('id');
			var no = $(this).data('no');
			var tipe = $(this).data('tipe');

			$.ajax({
				type: 'post',
				url: siteurl + active_controller + 'payment_modal',
				data: {
					'id': id,
					'no': no
				},
				cache: false,
				success: function(result) {
					if (tipe == 'kasbon') {
						$('.modal-title').html('Payment Kasbon');
					}
					if (tipe == 'expense') {
						$('.modal-title').html('Payment Expense');
					}

					$('#MyModalBody').html(result);
					$('#dialog-popup').modal('show');

					$('.select_2').select2({
						width: '100%'
					});
				},
				error: function(result) {

				}
			});
		});

		$(document).on('click', '.confirm_jenis_payment', function() {
			var no_payment = $('input[name="no_payment"]').val();
			var tipe = $('input[name="tipe"]').val();
			var tanggal_pembayaran = $('input[name="tanggal_pembayaran"]').val();
			var keterangan = $('input[name="keterangan"]').val();
			var bank = $('input[name="bank"]').val();
			var bukti_transfer = $('input[name="bukti_transfer"]').val();

			if (tanggal_pembayaran == null && tanggal_pembayaran == '') {
				swal({
					type: 'warning',
					title: 'Warning !',
					text: 'Tanggal Pembayaran tidak boleh kosong !'
				});

				return false;
			}

			if (bank == null && bank == '') {
				swal({
					type: 'warning',
					title: 'Warning !',
					text: 'Bank tidak boleh kosong !'
				});

				return false;
			}

			if (bukti_transfer == null && bukti_transfer == '') {
				swal({
					type: 'warning',
					title: 'Warning !',
					text: 'Tanggal Pembayaran tidak boleh kosong !'
				});

				return false;
			}

			swal({
				type: 'warning',
				title: 'Are you sure ?',
				text: 'This data will be updated as paid !',
				showCancelButton: true
			}, function(next) {
				if (next) {

					var formPost = new FormData($('#form-post')[0]);

					$.ajax({
						type: 'post',
						url: siteurl + active_controller + 'save_payment',
						data: formPost,
						cache: false,
						dataType: 'json',
						contentType: false,
						processData: false,
						success: function(result) {
							if (result.status == '1') {
								swal({
									type: 'success',
									title: 'Success !',
									text: result.msg
								}, function(lanjut) {
									$('#dialog-popup').modal('hide');
									DataTables();
								});
							} else {
								swal({
									type: 'warning',
									title: 'Warning !',
									text: result.msg
								});
							}
						},
						error: function(result) {
							swal({
								type: 'error',
								title: 'Error !',
								text: 'Please try again later !'
							});
						}
					});
				}
			});
		});

		$(document).on('click', '.revisi', function() {
			var id = $(this).data('id');
			var no = $(this).data('no');

			swal({
				type: 'warning',
				title: 'Are you sure ?',
				text: 'The data that has been input will be lost !',
				showCancelButton: true
			}, function(next) {
				if (next) {
					$.ajax({
						type: 'post',
						url: siteurl + active_controller + 'revisi_payment',
						data: {
							'id': id
						},
						cache: false,
						dataType: 'json',
						success: function(result) {
							if (result.status == '1') {
								swal({
									type: 'success',
									title: 'Success !',
									text: result.msg
								}, function(lanjut) {
									DataTables();
								});
							} else {
								swal({
									type: 'warning',
									title: 'Warning !',
									text: result.msg
								});
							}
						},
						error: function(result) {
							swal({
								type: 'error',
								title: 'Error !',
								text: 'Please try again later !'
							});
						}
					})
				}
			});
		});

		function DataTables() {
			var Datatables = $('#mytabledatanonmaterial').DataTable().destroy();
			var Datatables = $('#mytabledatanonmaterial').DataTable({
				processing: false,
				serverSide: true,
				language: {
					loadingRecords: 'Loading - Please Wait ...'
				},
				ajax: {
					type: 'post',
					url: siteurl + active_controller + 'get_data_payment'
				},
				columns: [{
						data: 'no'
					},
					{
						data: 'no_payment'
					},
					{
						data: 'tanggal_pengajuan'
					},
					{
						data: 'keperluan'
					},
					{
						data: 'kategori'
					},
					{
						data: 'info_transfer'
					},
					{
						data: 'nilai_bayar'
					},
					{
						data: 'tanggal_pembayaran'
					},
					{
						data: 'keterangan'
					},
					{
						data: 'bank'
					},
					{
						data: 'bukti_transfer'
					},
					{
						data: 'status'
					},
					{
						data: 'action'
					}
				]
			});
		}
	</script>