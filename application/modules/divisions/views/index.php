<link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
<div class="box box-primary">
	<div class="box-header">
		<h3 class="box-title"><?= $title; ?></h3>
		<div class="box-tool pull-right">
			
		</div>
	</div>
	<!-- /.box-header -->
	<div class="box-body">
		<table id="example1" class="table table-bordered table-striped">
			<thead>
				<tr class='bg-blue'>
					<th class="text-center">Id</th>
					<th class="text-center">Name</th>
					<th class="text-center">Company</th>
					<th class="text-center">Option</th>
				</tr>
			</thead>
			<tbody>
				
			</tbody>
		</table>
	</div>
</div>
<!-- /.box -->
<script src="https://cdn.datatables.net/2.2.1/js/dataTables.min.js"></script>
<script>
	$(document).ready(function() {
		DataTables();
	});

	function DataTables() {
		var DataTables = $('#example1').dataTable({
			ajax: {
				url: siteurl + active_controller + 'get_data_divisions',
				type: "POST",
				dataType: "JSON",
				data: function(d) {

				}
			},
			columns: [{
					data: 'id'
				},
				{
					data: 'name'
				},
				{
					data: 'company_name'
				},
				{
					data: 'option'
				}
			],
			responsive: true,
			processing: true,
			serverSide: true,
			stateSave: true,
			destroy: true,
			paging: true
		});
	}

	function deleteData(id) {
		swal({
				title: "Are you sure?",
				text: "You will not be able to process again this data!",
				type: "warning",
				showCancelButton: true,
				confirmButtonClass: "btn-danger",
				confirmButtonText: "Yes, Process it!",
				cancelButtonText: "No, cancel process!",
				closeOnConfirm: true,
				closeOnCancel: false
			},
			function(isConfirm) {
				if (isConfirm) {
					loading_spinner();
					window.location.href = base_url + 'index.php/' + active_controller + '/delete/' + id;

				} else {
					swal("Cancelled", "Data can be process again :)", "error");
					return false;
				}
			});

	}
</script>