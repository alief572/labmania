<?php
$ENABLE_ADD     = has_permission('SPK_Penawaran.Add');
$ENABLE_MANAGE  = has_permission('SPK_Penawaran.Manage');
$ENABLE_VIEW    = has_permission('SPK_Penawaran.View');
$ENABLE_DELETE  = has_permission('SPK_Penawaran.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">

<style>
    .spk-index { padding: 4px; background: #f2f5f7; border-radius: 14px; box-shadow: 0 6px 24px rgba(30, 50, 70, .04); }
    .spk-index > .box-header, .spk-index > .box-body { background: #fff; }
    .spk-index > .box-header { border-radius: 10px 10px 0 0; padding: 14px 16px 4px; }
    .spk-index > .box-body { border-radius: 0 0 10px 10px; padding: 12px 16px; }
    .spk-index .btn { border-radius: 8px; font-weight: 600; }
    .spk-index .box-header .btn { padding: 8px 12px; font-size: 12px; }
    .spk-index .box-header label { font-size: 11px; color: #617286; margin-bottom: 6px; }
    .spk-index .form-control, .spk-index .dt-input { border-radius: 8px; box-shadow: inset 0 0 0 1px rgba(30, 50, 70, .04); font-size: 12px; }
    .spk-index .table-responsive { overflow: visible; }
    .spk-index table { font-size: 12px; color: #25364a; border: 0; }
    .spk-index #table_penawaran { table-layout: fixed; width: 100% !important; }
    .spk-index table thead th { padding: 10px 8px; font-size: 10px; letter-spacing: .035em; color: #607086; background: #f5f7fa; border: 0; border-bottom: 1px solid rgba(30, 50, 70, .08); }
    .spk-index table tbody td { padding: 7px 8px; line-height: 1.35; vertical-align: middle; border: 0; border-bottom: 1px solid rgba(30, 50, 70, .055); overflow-wrap: anywhere; }
    .spk-index table tbody tr:nth-child(even) { background: #fafbfd; }
    .spk-index table tbody tr:hover { background: #f1f7f8; }
    .spk-index table strong { font-size: 12px; font-weight: 600; }
    .spk-index table small { display: inline-block; margin-top: 2px; font-size: 10px; color: #79879a; }
    .spk-index .spk-status-stack { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
    .spk-index .spk-status-stack .badge { width: auto !important; max-width: 100%; white-space: normal; text-align: left; padding: 4px 7px; border-radius: 5px; font-size: 10px; font-weight: 600 !important; line-height: 1.3; }
    .spk-index .spk-status-stack .bg-yellow { background: #fff2d9 !important; color: #94600b !important; }
    .spk-index .spk-status-stack .bg-green { background: #e6f5ee !important; color: #247051 !important; }
    .spk-index .spk-status-stack .bg-blue, .spk-index .spk-status-stack .bg-light-blue { background: #eaf1fb !important; color: #365d92 !important; }
    .spk-index .spk-status-stack .bg-red { background: #fcebea !important; color: #a74545 !important; }
    .spk-index .spk-action-toggle { display: inline-flex; gap: 5px; align-items: center; justify-content: center; min-height: 30px; padding: 5px 8px; background: #f1f5f8; color: #40566f; border: 0; border-radius: 8px; font-size: 11px; font-weight: 600; box-shadow: inset 0 0 0 1px rgba(40, 70, 100, .06); transition: transform 180ms cubic-bezier(.32,.72,0,1); }
    .spk-index .spk-action-toggle svg { width: 15px; height: 15px; }
    .spk-index .spk-action-toggle:hover, .spk-index .open > .spk-action-toggle { background: #e5eef3; color: #176c76; }
    .spk-index .spk-action-toggle:active { transform: scale(.97); }
    .spk-index .spk-action-toggle:focus, .spk-index .spk-action-menu a:focus { outline: 2px solid #33838b; outline-offset: 2px; }
    .spk-index .spk-action-menu { padding: 5px; min-width: 184px; border: 0; border-radius: 12px; background: #fff; box-shadow: 0 10px 35px rgba(30, 50, 70, .12), 0 0 0 1px rgba(30, 50, 70, .05); z-index: 1000; }
    .spk-index .spk-action-menu > li > a { display: flex; gap: 10px; align-items: center; padding: 10px; color: #35485f; border-radius: 7px; font-size: 12px; font-weight: 500; }
    .spk-index .spk-action-menu svg { width: 17px; height: 17px; flex: 0 0 17px; color: #7b8da3; }
    .spk-index .spk-action-menu > li > a:hover, .spk-index .spk-action-menu > li > a:focus { background: #eef5f6; color: #176c76; }
    .spk-index .spk-action-menu > li > a.spk-action-danger { color: #ad4b4b; }
    .spk-index .spk-action-menu > li > a.spk-action-danger:hover { background: #fff0ef; }
    .spk-index .spk-action-danger svg { color: inherit; }
    .spk-index .spk-action-divider { height: 1px; margin: 4px 8px; background: rgba(30, 50, 70, .07); }
    .spk-index .dt-container { font-size: 12px; }
    .spk-index .dt-paging-button { border-radius: 6px !important; }
    @media (max-width: 767px) {
        .spk-index > .box-header, .spk-index > .box-body { padding: 10px; }
        .spk-index .box-header .text-right { text-align: left; margin-top: 10px; }
        .spk-index .table-responsive { overflow-x: auto; }
        .spk-index #table_penawaran { min-width: 720px; }
    }
    @media (prefers-reduced-motion: reduce) { .spk-index .spk-action-toggle { transition: none; transform: none; } }
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box spk-index">
    <div class="box-header">
        <div class="row" style="margin-bottom: 10px; margin-top: 10px;">
            <div class="col-md-3">
                <label for="filter_status_spk">Filter Status SPK</label>
                <select id="filter_status_spk" class="form-control">
                    <option value="">-- All Status --</option>
                    <option value="waiting">Waiting Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <?php if ($ENABLE_ADD) : ?>
                <div class="col-md-9 text-right">
                    <a class="btn btn-sm btn-success" href="<?= base_url('spk_penawaran/create_spk') ?>">
                        <i class="fa fa-plus"></i> Create SPK
                    </a>
                    <a class="btn btn-sm btn-info" href="<?= base_url('spk_penawaran/export_excel') ?>" target="_blank">
                        <i class="fa fa-file-excel-o"></i> Export Excel
                    </a>
                    <!-- <a class="btn btn-sm btn-primary" href="<?= base_url('spk_penawaran/create_spk_non_konsultasi') ?>">
                    <i class="fa fa-plus"></i> Create SPK Non Konsultasi
                </a> -->
                    <!-- <button type="button" class="btn btn-sm btn-danger" id="one_time">Update!</button> -->
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <!-- <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="konsultasi active"><a href="javascript:void();" onclick="tab_konsultasi();">Konsultasi</a></li>
            <li role="presentation" class="non_konsultasi"><a href="javascript:void();" onclick="tab_non_konsultasi();">Non Konsultasi</a></li>
        </ul> -->
        <div id="konsultasi">
            <div class="table-responsive">
                <table id="table_penawaran" class="table table-bordered table-striped table-hover" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center" width="4%">No</th>
                            <th class="text-center" width="28%">Nomor SPK & Paket</th>
                            <th class="text-center" width="22%">Customer</th>
                            <th class="text-center" width="20%">Marketing / Created</th>
                            <th class="text-center" width="18%">Status</th>
                            <th class="text-center" width="8%">Action</th>
                        </tr>
                    </thead>

                </table>
            </div>
        </div>
        <div id="non_konsultasi" style="display: none;">
            <div class="table-responsive">
                <table id="table_spk_non_konsultasi" class="table table-bordered table-striped table-hover" style="width:100%;">
                    <thead class="bg-primary">
                        <tr>
                            <th align="center">No</th>
                            <th align="center">Nomor SPK</th>
                            <th align="center">Marketing</th>
                            <th align="center">Package</th>
                            <th align="center">Customer</th>
                            <th align="center">Grand Total</th>
                            <th align="center">Status SPK</th>
                            <th align="center">Action</th>
                        </tr>
                    </thead>

                </table>
            </div>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- page script -->
<script type="text/javascript">
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

        DataTablesNonKons();
    }

    $(document).ready(function() {
        DataTables();

        $('#filter_status_spk').on('change', function() {
            $('#table_penawaran').DataTable().ajax.reload();
        });
    });

    $(document).on('click', '.del_spk', function() {
        var id_spk_penawaran = $(this).data('id_spk_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            text: 'This data will be deleted !',
            cancelShowButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_spk',
                    data: {
                        'id_spk_penawaran': id_spk_penawaran
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
                            type: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                })
            }
        });
    });

    $(document).on('click', '#one_time', function() {
        Swal.fire({
            icon: 'warning',
            title: 'Warning !',
            text: 'Are you sure ?',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'one_time',
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg
                            }, function(after) {
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
                });
            }
        });
    });

    $(document).on('click', '.del_spk_non_kons', function() {
        var id_spk_penawaran = $(this).data('id_spk_penawaran');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be deleted !',
            showConfirmButton: true,
            showCancelButton: true,
            allowOutsideClick: false
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_spk_non_kons',
                    data: {
                        'id_spk_penawaran': id_spk_penawaran
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
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            Swal.close();
                            DataTablesNonKons();
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: "There's an error occured, Please try again later !",
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowEscapeKey: false,
                            allowOutsideClick: false,
                            timer: 3000
                        }).then(() => {
                            Swal.close();
                        });
                    }
                });
            }
        });
    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {
                    d.filter_status = $('#filter_status_spk').val();
                }
            },
            columns: [{
                    data: 'no',
                    className: 'text-center'
                }, {
                    data: 'spk_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'nm_marketing'
                },
                {
                    data: 'status',
                    className: 'text-center'
                },
                {
                    data: 'option',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: false,
            ordering: false,
            destroy: true,
            paging: true,
            autoWidth: false
        });
    }

    function DataTablesNonKons() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_spk_non_konsultasi').dataTable({
            ajax: {
                url: siteurl + active_controller + 'table_spk_non_konsultasi',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error !',
                        text: "There's an error occured, Please try again later !",
                        showConfirmButton: true,
                        showCancelButton: false,
                        allowOutsideClick: false
                    });
                }
            },
            columns: [{
                    data: 'no',
                }, {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'nm_marketing'
                },
                {
                    data: 'nm_paket',
                    render: function(data, type, row) {
                        if (type === 'display' && data && data.length > 40) {
                            return '<span title="' + data + '" style="cursor:help;">' + data.substring(0, 40) + '…</span>';
                        }
                        return data;
                    }
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'status_spk'
                },
                {
                    data: 'action'
                }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true,
            scrollX: true
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
<script type="text/javascript">
    (function () {
        function closeSpkMenus() {
            $('.spk-index .spk-actions.open > .spk-action-toggle').dropdown('toggle');
        }
        $(document).on('shown.bs.dropdown', '.spk-index .spk-actions', function () {
            var button = $(this).find('.spk-action-toggle')[0];
            var menu = $(this).find('.spk-action-menu')[0];
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
        $(document).on('hidden.bs.dropdown', '.spk-index .spk-actions', function () {
            $(this).find('.spk-action-menu').removeAttr('style');
        });
        $(window).on('resize.spkIndex', closeSpkMenus);
        document.addEventListener('scroll', function (event) {
            if (!$(event.target).closest('.spk-action-menu').length) closeSpkMenus();
        }, true);
        $('#table_penawaran').on('preDraw.dt', closeSpkMenus);
    })();
</script>
