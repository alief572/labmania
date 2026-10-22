<?php
$ENABLE_ADD     = has_permission('Penawaran.Add');
$ENABLE_MANAGE  = has_permission('Penawaran.Manage');
$ENABLE_VIEW    = has_permission('Penawaran.View');
$ENABLE_DELETE  = has_permission('Penawaran.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    .penawaran-index { padding: 4px; background: #f2f5f7; border-radius: 14px; box-shadow: 0 6px 24px rgba(30, 50, 70, .04); }
    .penawaran-index > .box-header, .penawaran-index > .box-body { background: #fff; }
    .penawaran-index > .box-header { border-radius: 10px 10px 0 0; padding: 14px 16px 4px; }
    .penawaran-index > .box-body { border-radius: 0 0 10px 10px; padding: 12px 16px; }
    .penawaran-index .btn { border-radius: 8px; font-weight: 600; }
    .penawaran-index .box-header .btn { padding: 8px 12px; font-size: 12px; }
    .penawaran-index .box-header label { font-size: 11px; color: #617286; margin-bottom: 6px; }
    .penawaran-index .form-control, .penawaran-index .dt-input { border-radius: 8px; box-shadow: inset 0 0 0 1px rgba(30, 50, 70, .04); font-size: 12px; }
    .penawaran-index .table-responsive { overflow: visible; }
    .penawaran-index table { font-size: 12px; color: #25364a; border: 0; }
    .penawaran-index #table_penawaran, .penawaran-index #table_penawaran_non_konsultasi { table-layout: fixed; width: 100% !important; }
    .penawaran-index table thead th { padding: 10px 8px; font-size: 10px; letter-spacing: .035em; color: #607086; background: #f5f7fa; border: 0; border-bottom: 1px solid rgba(30, 50, 70, .08); }
    .penawaran-index table tbody td { padding: 7px 8px; line-height: 1.35; vertical-align: middle; border: 0; border-bottom: 1px solid rgba(30, 50, 70, .055); overflow-wrap: anywhere; }
    .penawaran-index table tbody tr:nth-child(even) { background: #fafbfd; }
    .penawaran-index table tbody tr:hover { background: #f1f7f8; }
    .penawaran-index table strong { font-size: 12px; font-weight: 600; }
    .penawaran-index table small { display: inline-block; margin-top: 2px; font-size: 10px; color: #79879a; }
    .penawaran-index .penawaran-action-toggle { display: inline-flex; gap: 5px; align-items: center; justify-content: center; min-height: 30px; padding: 5px 8px; background: #f1f5f8; color: #40566f; border: 0; border-radius: 8px; font-size: 11px; font-weight: 600; box-shadow: inset 0 0 0 1px rgba(40, 70, 100, .06); transition: transform 180ms cubic-bezier(.32,.72,0,1); }
    .penawaran-index .penawaran-action-toggle svg { width: 15px; height: 15px; }
    .penawaran-index .penawaran-action-toggle:hover, .penawaran-index .open > .penawaran-action-toggle { background: #e5eef3; color: #176c76; }
    .penawaran-index .penawaran-action-toggle:active { transform: scale(.97); }
    .penawaran-index .penawaran-action-toggle:focus, .penawaran-index .penawaran-action-menu a:focus { outline: 2px solid #33838b; outline-offset: 2px; }
    .penawaran-index .penawaran-action-menu { padding: 5px; min-width: 184px; border: 0; border-radius: 12px; background: #fff; box-shadow: 0 10px 35px rgba(30, 50, 70, .12), 0 0 0 1px rgba(30, 50, 70, .05); z-index: 1000; }
    .penawaran-index .penawaran-action-menu > li > a { display: flex; gap: 10px; align-items: center; padding: 10px; color: #35485f; border-radius: 7px; font-size: 12px; font-weight: 500; }
    .penawaran-index .penawaran-action-menu svg { width: 17px; height: 17px; flex: 0 0 17px; color: #7b8da3; }
    .penawaran-index .penawaran-action-menu > li > a:hover, .penawaran-index .penawaran-action-menu > li > a:focus { background: #eef5f6; color: #176c76; }
    .penawaran-index .penawaran-action-menu > li > a.penawaran-action-danger { color: #ad4b4b; }
    .penawaran-index .penawaran-action-menu > li > a.penawaran-action-danger:hover { background: #fff0ef; }
    .penawaran-index .penawaran-action-danger svg { color: inherit; }
    .penawaran-index .penawaran-action-divider { height: 1px; margin: 4px 8px; background: rgba(30, 50, 70, .07); }
    .penawaran-index .dt-container { font-size: 12px; }
    .penawaran-index .dt-paging-button { border-radius: 6px !important; }
    @media (max-width: 767px) {
        .penawaran-index > .box-header, .penawaran-index > .box-body { padding: 10px; }
        .penawaran-index .box-header .text-right { text-align: left; margin-top: 10px; }
        .penawaran-index .table-responsive { overflow-x: auto; }
        .penawaran-index #table_penawaran, .penawaran-index #table_penawaran_non_konsultasi { min-width: 800px; }
    }
    @media (prefers-reduced-motion: reduce) { .penawaran-index .penawaran-action-toggle { transition: none; transform: none; } }
    .penawaran-index table .badge, .penawaran-index table .label { display: inline-block; width: auto !important; max-width: 100%; white-space: normal; padding: 4px 7px; border-radius: 5px; font-size: 10px !important; font-weight: 600; line-height: 1.3; margin-top: 3px; }
    .penawaran-index table .bg-yellow, .penawaran-index table .label-warning { background: #fff2d9 !important; color: #94600b !important; }
    .penawaran-index table .bg-green { background: #e6f5ee !important; color: #247051 !important; }
    .penawaran-index table .bg-blue, .penawaran-index table .label-info { background: #eaf1fb !important; color: #365d92 !important; }
    .penawaran-index table .bg-red { background: #fcebea !important; color: #a74545 !important; }
    .penawaran-index table .bg-purple { background: #f0edf9 !important; color: #695799 !important; }
    .penawaran-index table .text-muted { font-size: 10px !important; color: #79879a; }
    .penawaran-index table span[style*="font-weight"] { font-weight: 600 !important; }
    .penawaran-index .nav-tabs { border: 0; margin-bottom: 14px; display: flex; gap: 6px; }
    .penawaran-index .nav-tabs > li > a { border: 0; border-radius: 8px; padding: 8px 12px; font-size: 12px; color: #617286; }
    .penawaran-index .nav-tabs > li.active > a { border: 0; background: #eef5f6; color: #176c76; font-weight: 600; }
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box penawaran-index">
    <div class="box-header">
        <?php if ($ENABLE_ADD) : ?>
            <div class="dropdown text-right">
                <a class="btn btn-sm btn-success" style="font-weight: bold;" href="<?= base_url('penawaran/add_penawaran') ?>">
                    <i class="fa fa-plus"></i> Penawaran Konsultasi
                </a>
                <a class="btn btn-sm btn-primary" style="font-weight: bold;" href="<?= base_url('penawaran/add_penawaran_non') ?>">
                    <i class="fa fa-plus"></i> Penawaran Non Konsultasi
                </a>
            </div>
        <?php endif; ?>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="konsultasi active"><a href="javascript:void();" onclick="tab_konsultasi();">Konsultasi</a></li>
            <li role="presentation" class="non_konsultasi"><a href="javascript:void();" onclick="tab_non_konsultasi();">Non Konsultasi</a></li>
        </ul>
        <div id="konsultasi">
            <div class="table-responsive">
                <table id="table_penawaran" class="table table-bordered table-striped table-hover" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="text-center" width="4%">No</th>
                            <th class="text-center" width="26%">Quotation & Paket</th>
                            <th class="text-center" width="20%">Customer</th>
                            <th class="text-center" width="18%">Marketing & Creator</th>
                            <th class="text-center" width="13%">Grand Total</th>
                            <th class="text-center" width="11%">Status</th>
                            <th class="text-center" width="8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="non_konsultasi" style="display: none;">
            <div class="row" style="margin-bottom: 10px; margin-top: 10px;">
                <div class="col-md-3">
                    <label for="filter_status_non_kons">Filter Status Quotation</label>
                    <select id="filter_status_non_kons" class="form-control">
                        <option value="">-- All Status --</option>
                        <option value="waiting">Waiting Approval</option>
                        <option value="approved">Approved</option>
                        <option value="deal">Deal</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="table_penawaran_non_konsultasi" class="table table-bordered table-striped table-hover" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="text-center" width="4%">No</th>
                            <th class="text-center" width="26%">Quotation & Penawaran</th>
                            <th class="text-center" width="20%">Customer</th>
                            <th class="text-center" width="18%">Admin Sales & Creator</th>
                            <th class="text-center" width="13%">Grand Total</th>
                            <th class="text-center" width="11%">Status</th>
                            <th class="text-center" width="8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>


<div class="modal modal-default fade" id="modal_deal_penawaran" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="frm-deal" enctype="multipart/form-data">
                <input type="hidden" name="id_penawaran" id="id_penawaran_deal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="myModalLabel"><span class="fa fa-users"></span>&nbsp;Upload Dokumen Pendukung</h4>
                </div>
                <div class="modal-body" id="ModalView">
                    <div class="form-group">
                        <label for="">Dokumen Pendukung</label>
                        <input type="file" name="dokumen_pendukung" id="" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-arrow-left"></i> Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        DataTables();

        $('#filter_status_non_kons').on('change', function() {
            $('#table_penawaran_non_konsultasi').DataTable().ajax.reload();
        });
    });

    function tab_konsultasi() {
        $('#konsultasi').show();
        $('#non_konsultasi').hide();

        $('.konsultasi').addClass('active');
        $('.non_konsultasi').removeClass('active');

        DataTables();
    }

    function tab_non_konsultasi() {
        $('#non_konsultasi').show();
        $('#konsultasi').hide();

        $('.non_konsultasi').addClass('active');
        $('.konsultasi').removeClass('active');

        DataTablesNon();
    }

    $(document).on('click', '.deal_penawaran', function() {
        var id_penawaran = $(this).data('id_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: "You can't reverse it after you deal this data !",
            showConfirmButton: true,
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'deal_penawaran',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg
                            }).then(() => {
                                DataTables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.msg
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                })
            }
        });
    });

    $(document).on('click', '.btn_deal_penawaran', function() {
        var id_penawaran = $(this).data('id_penawaran');
        $('#id_penawaran_deal').val(id_penawaran);
    });

    $(document).on('click', '.del_penawaran', function() {
        var id_penawaran = $(this).data('id_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            text: 'This data will be deleted !',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_penawaran',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg
                            }).then(() => {
                                DataTables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.msg
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                })
            }
        });
    });

    $(document).on('submit', '#frm-deal', function(e) {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: "You can't reverse it after you deal this data !",
            showConfirmButton: true,
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                var formData = new FormData($('#frm-deal')[0]);
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'deal_penawaran_non_kons',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: 'Penawaran has been deal !',
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowClickOutside: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            $('#modal_deal_penawaran').modal('hide');
                            DataTablesNon();
                        });
                    },
                    error: function(xhr, status, error) {
                        // 1. Ambil response text dan parse ke JSON
                        let response = {};
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            response = {
                                msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                            };
                        }

                        // 2. Tampilkan pesan 'msg' dari JSON
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: response.msg, // <--- Ini yang bakal nampilin isi pesan lu
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.del_penawaran_non_kons', function() {
        var id_penawaran = $(this).data('id_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be deleted !',
            showConfirmButton: true,
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_penawaran_non_kons',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: 'Data has been deleted !',
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowClickOutside: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            Swal.close();
                            DataTablesNon();
                        });
                    },
                    error: function(xhr, status, error) {
                        // 1. Ambil response text dan parse ke JSON
                        let response = {};
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            response = {
                                msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                            };
                        }

                        // 2. Tampilkan pesan 'msg' dari JSON
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: response.msg, // <--- Ini yang bakal nampilin isi pesan lu
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    })

    $(document).on('click', '.deal_penawaran_non_kons', function() {
        var id_penawaran = $(this).data('id_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: "You can't reverse it after you deal this data !",
            showConfirmButton: true,
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'deal_penawaran_non_kons',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: 'Data has been deal !',
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowClickOutside: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            Swal.close();
                            DataTablesNon();
                        });
                    },
                    error: function(xhr, status, error) {
                        // 1. Ambil response text dan parse ke JSON
                        let response = {};
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            response = {
                                msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                            };
                        }

                        // 2. Tampilkan pesan 'msg' dari JSON
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: response.msg, // <--- Ini yang bakal nampilin isi pesan lu
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    });

    function DataTables() {
        var dataTables = $('#table_penawaran').dataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            destroy: true,
            paging: true,
            autoWidth: false,
            ordering: false,
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran',
                type: "POST",
                dataType: "JSON",
                data: function(d) {}
            },
            language: {
                loadingRecords: 'Please wait - Loading ...'
            },
            columns: [{
                    data: 'no',
                    className: 'text-center'
                },
                {
                    data: 'quotation_paket'
                },
                {
                    data: 'customer_info'
                },
                {
                    data: 'marketing_creator'
                },
                {
                    data: 'grand_total',
                    className: 'text-right'
                },
                {
                    data: 'status_quot',
                    className: 'text-center'
                },
                {
                    data: 'option',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ]
        });
    }

    function DataTablesNon() {
        var dataTables = $('#table_penawaran_non_konsultasi').dataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            destroy: true,
            paging: true,
            autoWidth: false,
            ordering: false,
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran_non',
                type: "GET",
                dataType: "JSON",
                data: function(d) {
                    d.filter_status = $('#filter_status_non_kons').val();
                },
                error: function(xhr, status, error) {
                    let response = {};
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response = {
                            msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                        };
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error !',
                        text: response.msg,
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            },
            language: {
                loadingRecords: 'Please wait - Loading ...'
            },
            columns: [{
                    data: 'no',
                    className: 'text-center'
                },
                {
                    data: 'quotation_penawaran'
                },
                {
                    data: 'customer_info'
                },
                {
                    data: 'sales_creator'
                },
                {
                    data: 'grand_total',
                    className: 'text-right'
                },
                {
                    data: 'status_quot',
                    className: 'text-center'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ]
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
<script type="text/javascript">
    (function () {
        function closePenawaranMenus() {
            $('.penawaran-index .penawaran-actions.open > .penawaran-action-toggle').dropdown('toggle');
        }
        $(document).on('shown.bs.dropdown', '.penawaran-index .penawaran-actions', function () {
            var button = $(this).find('.penawaran-action-toggle')[0];
            var menu = $(this).find('.penawaran-action-menu')[0];
            var rect = button.getBoundingClientRect();
            var viewportWidth = document.documentElement.clientWidth;
            var viewportHeight = window.innerHeight;
            var gap = 6;
            var padding = 8;
            menu.style.position = 'fixed';
            menu.style.maxHeight = Math.max(60, viewportHeight - padding * 2) + 'px';
            menu.style.maxWidth = (viewportWidth - padding * 2) + 'px';
            menu.style.overflowY = 'auto';
            var width = menu.getBoundingClientRect().width;
            var height = menu.getBoundingClientRect().height;
            var top = rect.bottom + gap;
            if (top + height > viewportHeight - padding) {
                top = rect.top - gap - height;
            }
            menu.style.top = Math.max(padding, Math.min(top, viewportHeight - height - padding)) + 'px';
            menu.style.left = Math.max(padding, Math.min(rect.right - width, viewportWidth - width - padding)) + 'px';
            menu.style.right = 'auto';
            menu.style.bottom = 'auto';
        });
        $(document).on('hidden.bs.dropdown', '.penawaran-index .penawaran-actions', function () {
            $(this).find('.penawaran-action-menu').removeAttr('style');
        });
        $(window).on('resize.penawaranIndex', closePenawaranMenus);
        document.addEventListener('scroll', function (event) {
            if (!$(event.target).closest('.penawaran-action-menu').length) closePenawaranMenus();
        }, true);
        $('#table_penawaran, #table_penawaran_non_konsultasi').on('preDraw.dt', closePenawaranMenus);
    })();
</script>
