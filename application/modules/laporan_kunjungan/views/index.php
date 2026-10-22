<?php
$ENABLE_VIEW   = has_permission('Laporan_Kunjungan.View');
$ENABLE_ADD    = has_permission('Laporan_Kunjungan.Add');
$ENABLE_MANAGE = has_permission('Laporan_Kunjungan.Manage');
$ENABLE_DELETE = has_permission('Laporan_Kunjungan.Delete');
?>

<style>
    .btn {
        border-radius: 10px;
    }
</style>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Daftar SPK Project</h3>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table id="table_spk" class="table table-bordered table-striped nowrap" width="100%">
                <thead class="bg-primary">
                    <tr>
                        <th align="center">No</th>
                        <th align="center">Perusahaan</th>
                        <th align="center">Project Name</th>
                        <th align="center">Project Leader</th>
                        <th align="center">Konsultan</th>
                        <th align="center">Target Selesai</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#table_spk').dataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: siteurl + 'laporan_kunjungan/get_data_spk',
            type: 'POST',
            dataType: 'JSON'
        },
        columns: [
            { data: 'no', orderable: false, searchable: false },
            { data: 'perusahaan' },
            { data: 'project' },
            { data: 'project_leader' },
            { data: 'konsultan' },
            { data: 'target_selesai' },
            { data: 'action', orderable: false, searchable: false }
        ],
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100, 150], [10, 20, 50, 100, 150]],
        responsive: true,
        stateSave: true,
        destroy: true,
        paging: true,
        order: [[1, 'desc']],
        language: {
            emptyTable: "Tidak ada data SPK yang tersedia",
            zeroRecords: "Tidak ada data yang cocok dengan pencarian",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            search: "Cari:",
            processing: "Memproses...",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });
});
</script>
