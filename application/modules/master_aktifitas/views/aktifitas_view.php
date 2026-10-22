<?php
$ENABLE_ADD     = has_permission('Master_Aktifitas.Add');
$ENABLE_MANAGE  = has_permission('Master_Aktifitas.Manage');
$ENABLE_VIEW    = has_permission('Master_Aktifitas.View');
$ENABLE_DELETE  = has_permission('Master_Aktifitas.Delete');
?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<style>
    .card-modern {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
        margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header-modern {
        padding: 18px 24px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .card-title-modern {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .card-title-icon {
        width: 36px;
        height: 36px;
        background: #e0f2fe;
        color: #0284c7;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .card-body-modern {
        padding: 24px;
    }

    /* Tab Segmented Styling */
    .tab-segmented {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        margin-bottom: 22px;
        border: none;
        list-style: none;
    }
    .tab-segmented > li {
        margin: 0;
    }
    .tab-segmented > li > a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border: none;
        transition: all 0.2s ease;
        background: transparent;
    }
    .tab-segmented > li.active > a,
    .tab-segmented > li > a:hover {
        background: #ffffff;
        color: #0284c7;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    /* Primary Add Button */
    .btn-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff !important;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 13px;
        border: none;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
        cursor: pointer;
    }
    .btn-action-primary:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
        color: #ffffff !important;
    }
    .btn-action-primary:active {
        transform: translateY(0);
    }

    /* Table Action Buttons */
    .btn-table-action-edit {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(217, 119, 6, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
        margin-right: 4px;
    }
    .btn-table-action-edit:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(217, 119, 6, 0.35);
        color: #ffffff !important;
    }
    .btn-table-action-edit:active {
        transform: translateY(0);
    }

    .btn-table-action-delete {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
        cursor: pointer;
    }
    .btn-table-action-delete:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
        color: #ffffff !important;
    }
    .btn-table-action-delete:active {
        transform: translateY(0);
    }

    /* Modern Table */
    .table-modern {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #edf2f7;
    }
    .table-modern thead th {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        padding: 13px 14px !important;
        border: none !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle !important;
    }
    .table-modern tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        font-size: 13px;
    }
    .table-modern tbody tr:hover {
        background-color: #f8fafc !important;
    }

    /* DataTable inputs & pagination */
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        outline: none;
        transition: all 0.2s ease;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 5px 8px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        margin: 0 2px !important;
        padding: 5px 10px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
    }
</style>

<div class="card-modern">
    <div class="card-header-modern">
        <div class="card-title-modern">
            <span class="card-title-icon"><i class="fa fa-cubes"></i></span>
            <span>Master Data Aktifitas</span>
        </div>
    </div>
    <div class="card-body-modern">
        <ul class="tab-segmented">
            <li class="active">
                <a href="<?php echo site_url('master_aktifitas'); ?>">
                    <i class="fa fa-table"></i> Data Aktifitas
                </a>
            </li>
            <?php if ($ENABLE_ADD) : ?>
                <li>
                    <a href="<?php echo site_url('master_aktifitas/aktifitas_new'); ?>">
                        <i class="fa fa-plus"></i> Tambah Aktifitas
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="table-responsive">
            <table id="my-grid" class="table table-bordered table-modern" width="100%">
                <thead>
                    <tr>
                        <th class="column-hide" width="40" style="text-align: center;">No.</th>
                        <th class="no-sort" width="130" style="text-align: center;">ID Aktifitas</th>
                        <th width="120" style="text-align: center;">Tgl Input</th>
                        <th>Nama Aktifitas</th>
                        <th class="no-sort" width="140" style="text-align: center;">Harga</th>
                        <th width="110" style="text-align: center;">Mandays</th>
                        <th>Keterangan</th>
                        <th class="no-sort" width="160" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        datatable();

        function datatable() {
            var dataTable = $('#my-grid').DataTable();
            dataTable.destroy();

            var dataTable = $('#my-grid').DataTable({
                "serverSide": true,
                "stateSave": false,
                "bAutoWidth": false,
                "oLanguage": {
                    "sSearch": "<i class='fa fa-search'></i> Cari : ",
                    "sLengthMenu": "Tampilkan _MENU_ baris",
                    "sInfo": "Menampilkan _START_ sampai _END_ dari total _TOTAL_ data",
                    "sInfoFiltered": "(difilter dari _MAX_ total data)",
                    "sZeroRecords": "<div class='text-center text-muted' style='padding: 20px;'><i class='fa fa-info-circle fa-2x'></i><br><br>Data tidak ditemukan</div>",
                    "sEmptyTable": "<div class='text-center text-muted' style='padding: 20px;'><i class='fa fa-folder-open-o fa-2x'></i><br><br>Belum ada data aktifitas</div>",
                    "sLoadingRecords": "Memuat data...",
                    "oPaginate": {
                        "sPrevious": "<i class='fa fa-chevron-left'></i>",
                        "sNext": "<i class='fa fa-chevron-right'></i>"
                    }
                },
                "aaSorting": [
                    [2, "desc"]
                ],
                "columnDefs": [{
                        "aTargets": [0],
                        "sClass": "column-hide text-center"
                    },
                    {
                        "aTargets": [1, 2, 5, 7],
                        "sClass": "text-center"
                    },
                    {
                        "aTargets": 'no-sort',
                        "orderable": false
                    }
                ],
                "sPaginationType": "simple_numbers",
                "iDisplayLength": 10,
                "aLengthMenu": [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                "ajax": {
                    url: siteurl + active_controller + 'display_aktifitas_json',
                    type: "post",
                    error: function() {
                        $(".my-grid-error").html("");
                        $("#my-grid").append('<tbody class="my-grid-error"><tr><th colspan="8"><center>Terjadi kesalahan saat memuat data server</center></th></tr></tbody>');
                        $("#my-grid_processing").css("display", "none");
                    }
                }
            });
        }

        $(document).on('click', '.delete_aktifitas', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data aktifitas ini akan dihapus permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fa fa-trash"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((submit) => {
                if (submit.isConfirmed) {
                    $.ajax({
                        url: siteurl + active_controller + 'delete_aktifitas',
                        type: 'POST',
                        data: {
                            'id': id
                        },
                        cache: false,
                        dataType: 'JSON',
                        success: function(result) {
                            if (result.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: result.msg,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    datatable();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Gagal!',
                                    text: result.msg
                                });
                            }
                        },
                        error: function(result) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Gagal terhubung ke server, silakan coba lagi!'
                            });
                        }
                    });
                }
            });
        });
    });
</script>