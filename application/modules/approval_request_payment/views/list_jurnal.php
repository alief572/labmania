<?php
    $ENABLE_ADD     = has_permission('Payment_Jurnal.Add');
    $ENABLE_MANAGE  = has_permission('Payment_Jurnal.Manage');
    $ENABLE_VIEW    = has_permission('Payment_Jurnal.View');
    $ENABLE_DELETE  = has_permission('Payment_Jurnal.Delete');
?>
<div class="box box-primary">
	<div class="box-body">
		<table class="table table-bordered table-striped" id="my-grid" width='100%'>
			<thead>
				<tr class='bg-blue'>
					<th class="text-center">#</th>
					<th class="text-center">Nomor</th>
					<th class="text-center">No Transaksi</th>
					<th class="text-center">Tgl Jurnal</th>
					<th class="text-center">Jumlah</th>
					<th class="text-center">Status</th>
					<th class="text-center">Option</th>
				</tr>
			</thead>
			<tbody>
			<?php
			if(!empty($results)){
				$numb=0; foreach($results AS $record){ $numb++; ?>
			<tr>
				<td><?= $numb; ?></td>
				<td><?= $record->no_reff ?></td>
				<td><?= $record->nomor ?></td>
				<td><?= $record->tanggal ?></td>
				<td><?= number_format($record->total,2) ?></td>
				<td><?= $record->stspos ?></td>
				<td>
				<?php
					echo "
					  <a class='btn btn-sm btn-default viewed' href='javascript:void(0)' title='View Jurnal' data-id='" . $record->nomor . "'><i class='fa fa-search'></i>
					  </a> ";
				if($record->stspos!=1){
					echo "<a class='btn btn-warning btn-sm edited' href='javascript:void(0)' title='Edit Jurnal' data-id='" . $record->nomor . "'><i class='fa fa-check'></i>
					  </a>
					  ";
				}
				?>
				</td>
			</tr>
			<?php
				}
			}  ?>			
			</tbody>
		</table>
	</div>
	<!-- /.box-body -->
 </div>
  <!-- /.box -->
  <script>


$(document).on('click', '.viewed', function(e) {
    window.location.href = base_url + active_controller + '/view_jurnal/' + $(this).data('id');
});

$(document).on('click', '.edited', function(e) {
    window.location.href = base_url + active_controller + '/edit_jurnal/' + $(this).data('id');
});

$(document).on('click', '.updated', function() {
    var id = $(this).data('id');

    Swal.fire({
            title: "Are you sure?",
            text: "Update this data ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd6b55",
            confirmButtonText: "Yes, Process it!",
            cancelButtonText: "No, cancel process!"
        }).then((result) => {
            if (result.isConfirmed) {
                loading_spinner();
                $.ajax({
                    url: base_url + active_controller + '/update_jurnal/' + id,
                    type: "POST",
                    cache: false,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.status == 1) {
                            Swal.fire({
                                title: "Update Success!",
                                text: data.pesan,
                                icon: "success",
                                timer: 5000
                            });
                            window.location.href = base_url + 'ros/index_jurnal_incoming';
                        } else if (data.status == 0) {
                            Swal.fire({
                                title: "Update Failed!",
                                text: data.pesan,
                                icon: "warning",
                                timer: 5000
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: "Error Message !",
                            text: 'An Error Occured During Process. Please try again..',
                            icon: "warning",
                            timer: 5000
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Data can be process again :)",
                    icon: "error"
                });
                return false;
            }
        });
});
</script>
