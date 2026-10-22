<?php
$ENABLE_ADD     = has_permission('Master_Konsultasi.Add');
$ENABLE_MANAGE  = has_permission('Master_Konsultasi.Manage');
$ENABLE_VIEW    = has_permission('Master_Konsultasi.View');
$ENABLE_DELETE  = has_permission('Master_Konsultasi.Delete');
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

    /* Table Action Buttons */
    .btn-table-action-view {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
        margin-right: 3px;
    }
    .btn-table-action-view:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35);
        color: #ffffff !important;
    }

    .btn-table-action-edit {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(217, 119, 6, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
        margin-right: 3px;
    }
    .btn-table-action-edit:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(217, 119, 6, 0.35);
        color: #ffffff !important;
    }

    .btn-table-action-delete {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
        margin-right: 3px;
        cursor: pointer;
    }
    .btn-table-action-delete:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
        color: #ffffff !important;
    }

    .btn-table-action-print {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        transition: all 0.2s ease;
        text-decoration: none !important;
    }
    .btn-table-action-print:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35);
        color: #ffffff !important;
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
            <span class="card-title-icon"><i class="fa fa-handshake-o"></i></span>
            <span>Master Data Konsultasi</span>
        </div>
    </div>
    <div class="card-body-modern">
        <ul class="tab-segmented">
            <li class="active">
                <a href="<?php echo site_url('master_konsultasi'); ?>">
                    <i class="fa fa-table"></i> Data Konsultasi
                </a>
            </li>
            <?php if ($ENABLE_ADD) : ?>
                <li>
                    <a href="<?php echo site_url('master_konsultasi/konsultasi_new'); ?>">
                        <i class="fa fa-plus"></i> Tambah Konsultasi
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="table-responsive">
            <table id="my-grid" class="table table-bordered table-modern" width="100%">
                <thead>
                    <tr>
                        <th class="column-hide" width="40" style="text-align: center;">No.</th>
                        <th width="140" style="text-align: center;">ID Konsultasi</th>
                        <th>Nama Konsultasi</th>
                        <th width="150" style="text-align: center;">Tgl Input</th>
                        <th class="no-sort" width="220" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Container -->
<div class="modal fade" id="ModalView" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%); color: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 0.9;"><i class="fa fa-times-circle"></i></button>
                <h4 class="modal-title" id="ModalHeader" style="font-weight: 600;">Detail Konsultasi</h4>
            </div>
            <div class="modal-body" id="ModalContent" style="padding: 20px;"></div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        datatables();

        function datatables() {
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
                    "sEmptyTable": "<div class='text-center text-muted' style='padding: 20px;'><i class='fa fa-folder-open-o fa-2x'></i><br><br>Belum ada data konsultasi</div>",
                    "sLoadingRecords": "Memuat data...",
                    "oPaginate": {
                        "sPrevious": "<i class='fa fa-chevron-left'></i>",
                        "sNext": "<i class='fa fa-chevron-right'></i>"
                    }
                },
                "aaSorting": [
                    [3, "desc"]
                ],
                "columnDefs": [{
                        "aTargets": [0],
                        "sClass": "column-hide text-center"
                    },
                    {
                        "aTargets": [1, 3, 4],
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
                    url: siteurl + active_controller + 'display_konsultasi_json',
                    type: "post",
                    error: function() {
                        $(".my-grid-error").html("");
                        $("#my-grid").append('<tbody class="my-grid-error"><tr><th colspan="5"><center>Terjadi kesalahan saat memuat data server</center></th></tr></tbody>');
                        $("#my-grid_processing").css("display", "none");
                    }
                }
            });
        }

        // Modal View handler
        $(document).on('click', '#ShowModal', function(e) {
            e.preventDefault();
            var link = $(this).attr('href');
            var header = $(this).data('header') || 'Detail Konsultasi';

            $('#ModalHeader').html(header);
            $('#ModalContent').html('<div class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><br>Memuat detail...</div>');
            $('#ModalView').modal('show');
            $('#ModalContent').load(link);
        });

        // Delete confirmation
        $(document).on('click', '.delete_konsultasi', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data paket konsultasi ini akan dihapus!',
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
                        type: 'POST',
                        url: siteurl + active_controller + 'delete_konsultasi',
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
                                    datatables();
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