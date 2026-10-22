<?php
$ENABLE_ADD     = has_permission('Penawaran.Add');
$ENABLE_MANAGE  = has_permission('Penawaran.Manage');
$ENABLE_VIEW    = has_permission('Penawaran.View');
$ENABLE_DELETE  = has_permission('Penawaran.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    .btn {
        border-radius: 10px;
    }

    .dropdown-menu {
        list-style: none;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        z-index: 99999;
        padding: 5px 0;
        margin: 0;
        min-width: 150px;
        display: none;
    }

    .dropdown-menu li a {
        display: block;
        padding: 5px 15px;
        color: #333;
        text-decoration: none;
        white-space: nowrap;
    }

    .dropdown-menu li a:hover {
        background-color: #f5f5f5;
    }

    .table-responsive,
    .dataTables_scrollBody,
    .dataTables_wrapper,
    .box-body,
    .box {
        overflow: visible !important;
    }
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box">
    <div class="box-header">

    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="konsultasi active"><a href="javascript:void();" onclick="tab_konsultasi();">Konsultasi</a></li>
            <li role="presentation" class="non_konsultasi"><a href="javascript:void();" onclick="tab_non_konsultasi();">Non Konsultasi</a></li>
        </ul>
        <div id="konsultasi">
            <div class="table-responsive">
                <table id="table_penawaran" class="table table-bordered table-striped nowrap">
                    <thead class="bg-primary">
                        <tr>
                            <th align="center">No</th>
                            <th align="center">ID Quotation</th>
                            <th align="center">Date</th>
                            <th align="center">Marketing</th>
                            <th align="center">Package</th>
                            <th align="center">Customer</th>
                            <th align="center">Grand Total</th>
                            <th align="center">Status Cust.</th>
                            <th align="center">Status Quot.</th>
                            <th align="center">Action</th>
                        </tr>
                    </thead>

                </table>
            </div>
        </div>
        <div id="non_konsultasi" style="display: none;">
            <div class="table-responsive">
                <table id="table_penawaran_non_konsultasi" class="table table-bordered table-striped nowrap">
                    <thead class="bg-primary">
                        <tr>
                            <th align="center">No</th>
                            <th align="center">ID Quotation</th>
                            <th align="center">Date</th>
                            <th align="center">PIC Penawaran</th>
                            <th align="center">Penawaran</th>
                            <th align="center">Customer</th>
                            <th align="center">Grand Total</th>
                            <th align="center">Status Quotation</th>
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

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
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

        DataTablesNon();
    }

    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'non_konsultasi') {
            tab_non_konsultasi();
        } else {
            DataTables();
        }
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
                            }).then((after) => {
                                location.reload(true);
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

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no',
                }, {
                    data: 'id_quotation'
                }, {
                    data: 'tgl_quotation'
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
                    data: 'status_cust'
                },
                {
                    data: 'status_quot'
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
            paging: true,
            scrollX: true
        });
    }

    function DataTablesNon() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran_non_konsultasi').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran_non_konsultasi',
                type: "GET",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no'
                },
                {
                    data: 'id_quotation'
                },
                {
                    data: 'date'
                },
                {
                    data: 'pic_penawaran'
                },
                {
                    data: 'penawaran'
                },
                {
                    data: 'customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'status_quot'
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
<script type="text/javascript">
    // Fix dropdown terpotong di dalam DataTables scrollX
    $(document).on('click', '.dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Tutup semua dropdown yang sedang terbuka
        $('.dropdown-menu').removeClass('show-dropdown');
        $('.dropdown-menu').hide();

        var $btn = $(this);
        var $menu = $btn.siblings('.dropdown-menu');

        if ($menu.is(':visible')) {
            $menu.hide();
            return;
        }

        var offset = $btn.offset();
        var btnHeight = $btn.outerHeight();
        var btnWidth = $btn.outerWidth();

        $menu.css({
            position: 'fixed',
            top: (offset.top + btnHeight - $(window).scrollTop()) + 'px',
            left: 'auto',
            right: ($(window).width() - offset.left - btnWidth) + 'px',
            display: 'block',
            zIndex: 99999
        });
    });

    // Tutup dropdown saat klik di luar
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').hide();
        }
    });
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>