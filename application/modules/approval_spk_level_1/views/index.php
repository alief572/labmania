<?php
$ENABLE_ADD     = has_permission('Approval_SPK.Add');
$ENABLE_MANAGE  = has_permission('Approval_SPK.Manage');
$ENABLE_VIEW    = has_permission('Approval_SPK.View');
$ENABLE_DELETE  = has_permission('Approval_SPK.Delete');
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
        <table id="table_penawaran" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th align="center">No</th>
                    <th align="center">Nomor SPK</th>
                    <th align="center">Marketing</th>
                    <th align="center">Package</th>
                    <th align="center">Customer</th>
                    <th align="center">Grand Total</th>
                    <th align="center">Status</th>
                    <th align="center">Status SPK</th>
                    <th align="center">Action</th>
                </tr>
            </thead>

        </table>
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
    $(document).ready(function() {
        DataTables();
    });

    $(document).on('click', '.del_spk', function() {
        var id_spk_penawaran = $(this).data('id_spk_penawaran');

        swal({
            type: 'warning',
            title: 'Are you sure?',
            text: 'This data will be deleted !',
            cancelShowButton: true
        }, function(next) {
            if (next) {
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
                            swal({
                                type: 'success',
                                title: 'Success !',
                                text: result.msg
                            }, function(after) {
                                window.location.href = siteurl + active_controller;
                            });
                        } else {
                            swal({
                                type: 'warning',
                                title: 'Failed !',
                                text: result.msg
                            });
                        }
                    },
                    error: function(result) {
                        swal({
                            type: 'error',
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
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

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
                    data: 'nm_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'status'
                },
                {
                    data: 'status_spk'
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
</script>
<script type="text/javascript">
    $(document).on('click', '.dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.dropdown-menu').hide();
        var $btn = $(this);
        var $menu = $btn.siblings('.dropdown-menu');
        if ($menu.is(':visible')) {
            $menu.hide();
            return;
        }
        var offset = $btn.offset();
        $menu.css({
            position: 'fixed',
            top: (offset.top + $btn.outerHeight() - $(window).scrollTop()) + 'px',
            left: 'auto',
            right: ($(window).width() - offset.left - $btn.outerWidth()) + 'px',
            display: 'block',
            zIndex: 99999
        });
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').hide();
        }
    });
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>