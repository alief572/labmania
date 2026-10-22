<?php
$ENABLE_ADD     = has_permission('Master_Customer.Add');
$ENABLE_MANAGE  = has_permission('Master_Customer.Manage');
$ENABLE_VIEW    = has_permission('Master_Customer.View');
$ENABLE_DELETE  = has_permission('Master_Customer.Delete');
?>

<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box">
	<div class="box-header">
		<span class="pull-right">
			<?php if ($ENABLE_ADD) : ?>
				<a class="btn btn-success btn-sm" href="<?= base_url('master_customer/add') ?>" title="Add"> <i class="fa fa-plus">&nbsp;</i>Add</a>
			<?php endif; ?>
			<!-- <a class="btn btn-warning btn-sm" href="<?= base_url('master_customer/excel_download') ?>" target='_blank' title="Download Excel"> <i class="fa fa-file-excel-o">&nbsp;</i>&nbsp;Download Excel</a> -->

		</span>
	</div>
	<!-- /.box-header -->
	<div class="box-body">
		<div class="table-responsive">
			<table id="example1" class="table table-bordered table-striped nowrap">
				<thead>
					<th class="text-center">#</th>
					<th class="text-center">Customer</th>
					<th class="text-center">Credibility</th>
					<th class="text-center">Product Jual</th>
					<th class="text-center">Country</th>
					<th class="text-center">Status</th>
					<th class="text-center">Option</th>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
	<!-- /.box-body -->
</div>

<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
	.box-primary {

		border: 1px solid #ddd;
	}
</style>
<script type="text/javascript">
	var dataTable;
	$(document).ready(function() {
		datatables();
	});

	function datatables() {
		if ($.fn.DataTable.isDataTable('#example1')) {
			dataTable.ajax.reload(null, false);
		} else {
			dataTable = $('#example1').DataTable({
				ajax: {
					url: siteurl + active_controller + '/get_data_customer',
					type: "POST",
					dataType: "JSON",
					data: function(d) {}
				},
				columns: [
					{
						data: 'no',
						className: 'text-center',
						orderable: false,
						searchable: false,
						width: '50px'
					},
					{
						data: 'nm_customer'
					},
					{
						data: 'kredibilitas',
						className: 'text-center'
					},
					{
						data: 'produk_jual'
					},
					{
						data: 'country_code',
						className: 'text-center'
					},
					{
						data: 'sts_aktif',
						className: 'text-center',
						orderable: false,
						searchable: false
					},
					{
						data: 'option',
						className: 'text-center',
						orderable: false,
						searchable: false,
						width: '120px'
					}
				],
				responsive: true,
				processing: true,
				serverSide: true,
				stateSave: true,
				paging: true,
				scrollX: true,
				order: []
			});
		}
	}

	$(document).on('click', '.delete', function(e) {
		e.preventDefault()
		var id = $(this).data('id');
		// alert(id);
		Swal.fire({
			title: "Anda Yakin?",
			text: "Data akan di hapus!",
			icon: "warning",
			showCancelButton: true,
			confirmButtonClass: "btn-info",
			confirmButtonText: "Yes",
			cancelButtonText: "No",
			closeOnConfirm: false
		}).then((next) => {
			if (next.isConfirmed) {
				$.ajax({
					type: 'POST',
					url: siteurl + active_controller + '/delete',
					dataType: "json",
					data: {
						'id': id
					},
					success: function(data) {
						if (data.status == '1') {
							Swal.fire({
								title: "Sukses",
								text: data.pesan,
								icon: "success",
								showCancelButton: false,
								showConfirmButton: false,
								allowOutsideClick: false,
								timer: 3000
							}).then(() => {
								datatables();
							})
						} else {
							Swal.fire({
								title: "Error",
								text: data.pesan,
								icon: "error",
								showCancelButton: false,
								showConfirmButton: false,
								allowOutsideClick: false,
								timer: 3000
							})

						}
					},
					error: function() {
						Swal.fire({
							title: "Error",
							text: "Error proccess !",
							icon: "error",
							showCancelButton: false,
							showConfirmButton: false,
							allowOutsideClick: false,
							timer: 3000
						})
					}
				})
			}
		});

	})
</script>