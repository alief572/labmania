<?php
$ENABLE_ADD     = has_permission('Master_Tenaga_Ahli.Add');
$ENABLE_MANAGE  = has_permission('Master_Tenaga_Ahli.Manage');
$ENABLE_VIEW    = has_permission('Master_Tenaga_Ahli.View');
$ENABLE_DELETE  = has_permission('Master_Tenaga_Ahli.Delete');
?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
        cursor: pointer;
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
        cursor: pointer;
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
        cursor: pointer;
    }
    .btn-table-action-delete:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
        color: #ffffff !important;
    }

    /* Header Action Button */
    .btn-modern-add {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #ffffff !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
        transition: all 0.2s ease;
        text-decoration: none !important;
        cursor: pointer;
    }
    .btn-modern-add:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4);
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

    /* Modal Styling */
    .modal-content-modern {
        border-radius: 12px;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .modal-header-modern {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        padding: 16px 22px !important;
        border-bottom: none !important;
        position: relative;
    }
    .modal-header-modern .close {
        color: #ffffff !important;
        opacity: 0.9 !important;
        font-size: 22px !important;
        text-shadow: none !important;
        float: right !important;
        margin-top: -2px !important;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        transition: all 0.2s ease;
    }
    .modal-header-modern .close:hover {
        opacity: 1 !important;
        transform: scale(1.1);
    }
    .modal-header-modern .modal-title {
        font-weight: 700 !important;
        font-size: 16px !important;
        color: #ffffff !important;
        margin: 0 !important;
        line-height: 1.4 !important;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .modal-body-modern {
        padding: 24px;
        background: #ffffff;
    }
    .modal-footer-modern {
        padding: 14px 24px;
        background: #f8fafc;
        border-top: 1px solid #edf2f7;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

<div class="card-modern">
    <div class="card-header-modern">
        <div class="card-title-modern">
            <span class="card-title-icon"><i class="fa fa-users"></i></span>
            <span>Master Tenaga Ahli</span>
        </div>
        <?php if ($ENABLE_ADD) : ?>
            <div>
                <button type="button" class="btn-modern-add add">
                    <i class="fa fa-plus-circle"></i> Tambah Tenaga Ahli
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table id="example1" class="table table-bordered table-modern" width="100%">
                <thead>
                    <tr>
                        <th width="40" style="text-align: center;">No.</th>
                        <th>Nama Tenaga Ahli</th>
                        <th width="240">COA</th>
                        <th class="no-sort" width="190" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modern Modal Dialog -->
<div class="modal fade" id="dialog-popup" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-content-modern">
            <div class="modal-header modal-header-modern">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times-circle"></i>
                </button>
                <h4 class="modal-title" id="head_title">
                    <i class="fa fa-users"></i> <span id="modal-title-text">Data Tenaga Ahli</span>
                </h4>
            </div>
            <form action="" method="post" id="frm-data">
                <div class="modal-body modal-body-modern" id="ModalView">
                    <div class="text-center" style="padding: 30px;">
                        <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                        <br><br>Memuat form...
                    </div>
                </div>
                <div class="modal-footer modal-footer-modern">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 6px; font-weight: 600;">
                        <i class="fa fa-times"></i> Tutup
                    </button>
                    <button type="submit" class="btn btn-primary btn_save" style="border-radius: 6px; font-weight: 600; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);">
                        <i class="fa fa-save"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script type="text/javascript">
    var dataTable;
    $(document).ready(function() {
        datatables();
    });

    function datatables() {
        dataTable = $('#example1').DataTable({
            "destroy": true,
            "processing": true,
            "serverSide": true,
            "stateSave": false,
            "bAutoWidth": false,
            "oLanguage": {
                "sSearch": "<i class='fa fa-search'></i> Cari : ",
                "sLengthMenu": "Tampilkan _MENU_ baris",
                "sInfo": "Menampilkan _START_ sampai _END_ dari total _TOTAL_ data",
                "sInfoFiltered": "(difilter dari _MAX_ total data)",
                "sZeroRecords": "<div class='text-center text-muted' style='padding: 20px;'><i class='fa fa-info-circle fa-2x'></i><br><br>Data tidak ditemukan</div>",
                "sEmptyTable": "<div class='text-center text-muted' style='padding: 20px;'><i class='fa fa-folder-open-o fa-2x'></i><br><br>Belum ada data tenaga ahli</div>",
                "sLoadingRecords": "Memuat data...",
                "oPaginate": {
                    "sPrevious": "<i class='fa fa-chevron-left'></i>",
                    "sNext": "<i class='fa fa-chevron-right'></i>"
                }
            },
            "columnDefs": [
                {
                    "targets": [0],
                    "className": "text-center"
                },
                {
                    "targets": [3],
                    "className": "text-center",
                    "orderable": false
                }
            ],
            "ajax": {
                url: siteurl + active_controller + 'get_data_biaya',
                type: "POST",
                dataType: "JSON",
                error: function() {
                    $("#example1").append('<tbody class="grid-error"><tr><th colspan="4" class="text-center text-danger" style="padding: 20px;">Terjadi kesalahan saat memuat data dari server</th></tr></tbody>');
                }
            },
            "columns": [
                { data: 'no' },
                { data: 'nm_biaya' },
                { data: 'coa' },
                { data: 'option' }
            ]
        });
    }

    // Add Data
    $(document).on('click', '.add', function(e) {
        e.preventDefault();
        $('#modal-title-text').html('Tambah Tenaga Ahli');
        $('#ModalView').html('<div class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><br>Memuat form tambah...</div>');
        $('.btn_save').show();
        $('#dialog-popup').modal('show');

        $.ajax({
            type: 'POST',
            url: siteurl + active_controller + 'add/',
            cache: false,
            success: function(data) {
                $('#ModalView').html(data);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memuat form tambah. Silakan coba lagi!'
                });
            }
        });
    });

    // View Data
    $(document).on('click', '.view_biaya_modal', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#modal-title-text').html('Detail Tenaga Ahli');
        $('#ModalView').html('<div class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><br>Memuat detail...</div>');
        $('.btn_save').hide();
        $('#dialog-popup').modal('show');

        $.ajax({
            type: 'POST',
            url: siteurl + active_controller + 'view/',
            data: { 'id': id },
            cache: false,
            success: function(data) {
                $('#ModalView').html(data);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memuat detail tenaga ahli. Silakan coba lagi!'
                });
            }
        });
    });

    // Edit Data
    $(document).on('click', '.edit_biaya_modal', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#modal-title-text').html('Edit Tenaga Ahli');
        $('#ModalView').html('<div class="text-center" style="padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><br>Memuat form edit...</div>');
        $('.btn_save').show();
        $('#dialog-popup').modal('show');

        $.ajax({
            type: 'POST',
            url: siteurl + active_controller + 'edit/',
            data: { 'id': id },
            cache: false,
            success: function(data) {
                $('#ModalView').html(data);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memuat form edit. Silakan coba lagi!'
                });
            }
        });
    });

    // Save Data
    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        var nm_biaya = $('input[name="nm_biaya"]').val();
        var coa = $('select[name="coa"]').val();

        if ($.trim(nm_biaya) === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                text: 'Nama Tenaga Ahli wajib diisi!'
            });
            return false;
        }

        if ($.trim(coa) === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                text: 'Silakan pilih Akun COA!'
            });
            return false;
        }

        Swal.fire({
            title: 'Konfirmasi Simpan',
            text: 'Apakah data yang Anda masukkan sudah sesuai?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-save"></i> Ya, Simpan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = $('#frm-data').serialize();

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'save_biaya',
                    data: formData,
                    cache: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.pesan,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                $('#dialog-popup').modal('hide');
                                datatables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Gagal!',
                                text: res.pesan
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Gagal memproses data ke server!'
                        });
                    }
                });
            }
        });
    });

    // Delete Confirmation
    $(document).on('click', '.del_biaya', function(e) {
        e.preventDefault();
        var id = $(this).data('id');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Data tenaga ahli ini akan dihapus!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-trash"></i> Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_biaya',
                    data: { 'id': id },
                    cache: false,
                    dataType: 'JSON',
                    success: function(res) {
                        if (res.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.pesan,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                datatables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Gagal!',
                                text: res.pesan
                            });
                        }
                    },
                    error: function() {
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
</script>