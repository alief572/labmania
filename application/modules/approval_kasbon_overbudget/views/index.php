<?php
$ENABLE_ADD     = has_permission('Approval_Kasbon_Overbudget.Add');
$ENABLE_MANAGE  = has_permission('Approval_Kasbon_Overbudget.Manage');
$ENABLE_VIEW    = has_permission('Approval_Kasbon_Overbudget.View');
$ENABLE_DELETE  = has_permission('Approval_Kasbon_Overbudget.Delete');
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
        <div class="table-responsive">
            <table id="table_penawaran" class="table table-bordered table-striped nowrap">
                <thead>
                    <tr>
                        <th align="center">No.</th>
                        <th align="center">ID Request</th>
                        <th align="center">ID Budgeting</th>
                        <th align="center">ID SPK Penawaran</th>
                        <th align="center">ID Penawaran</th>
                        <th align="center">Customer</th>
                        <th align="center">Nominal</th>
                        <th align="center">Status</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>

            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>
<div class="modal" id="dialog-rekap" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">

            </div>
            <div class="modal-body" id="MyModalBody">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Close
                </button>
                <button type="button" class="btn btn-sm btn-danger btn_reject"><i class="fa fa-close"></i> Reject</button>
                <button type="button" class="btn btn-sm btn-success btn_save"><i class="fa fa-check"></i> Approve</button>
            </div>
        </div>
    </div>
</div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        DataTables();
    });

    $(document).on('click', '.detail', function() {
        var id = $(this).data('id');
        var tipe = $(this).data('tipe');

        $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'detail',
            data: {
                'id': id,
                'tipe': tipe
            },
            cache: false,
            success: function(result) {
                $('.modal-header').html('<h4>View Detail Overbudget</h4>');
                $('.btn_save').hide();
                $('.btn_reject').hide();
                $('.modal-body').html(result);

                $('#dialog-rekap').modal('show');
            },
            error: function(result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: 'Please try again later !'
                });
            }
        });
    });

    $(document).on('click', '.approval', function() {
        var id = $(this).data('id');
        var tipe = $(this).data('tipe');

        $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'approval',
            data: {
                'id': id,
                'tipe': tipe
            },
            cache: false,
            success: function(result) {
                $('.modal-header').html('<h4>Approval Overbudget</h4>');
                $('.btn_save').show();
                $('.btn_reject').show();
                $('.modal-body').html(result);

                $('#dialog-rekap').modal('show');
            },
            error: function(result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: 'Please try again later !'
                });
            }
        });
    });

    $(document).on('click', '.btn_save', function() {
        var id = $('input[name="id"]').val();

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be approved !',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'approve',
                    data: {
                        'id': id
                    },
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                timer: 3000,
                                showConfirmButton: false
                            }).then((lanjut) => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.msg,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later !',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn_reject', function() {
        var id = $('input[name="id"]').val();
        var reject_reason = $('textarea[name="reject_reason"]').val();

        if (reject_reason == '' || reject_reason == undefined) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Reject reason must be filled first !',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                timer: 3000,
                showConfirmButton: false
            });

            return false;
        } else {
            if (id !== undefined && id !== '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Are you sure ?',
                    text: 'This data will be rejected !',
                    showCancelButton: true
                }).then((next) => {
                    if (next.isConfirmed) {
                        $.ajax({
                            type: 'post',
                            url: siteurl + active_controller + 'reject',
                            data: {
                                'id': id,
                                'reject_reason': reject_reason
                            },
                            cache: false,
                            dataType: 'json',
                            success: function(result) {
                                if (result.status == 1) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success !',
                                        text: result.msg,
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        allowEnterKey: false,
                                        timer: 3000,
                                        showConfirmButton: false
                                    }).then((lanjut) => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Failed !',
                                        text: result.msg,
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        allowEnterKey: false,
                                        timer: 3000,
                                        showConfirmButton: false
                                    });
                                }
                            },
                            error: function(result) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error !',
                                    text: 'Please try again later !',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    allowEnterKey: false,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }
                        });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: 'Please try again later !',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        }

    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_overbudget',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no'
                },
                {
                    data: 'id_request'
                },
                {
                    data: 'id_spk_budgeting'
                },
                {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'id_penawaran'
                },
                {
                    data: 'nama_customer'
                },
                {
                    data: 'nominal'
                },
                {
                    data: 'status'
                },
                {
                    data: 'option'
                }
            ],
            responsive: true,
            processing: false,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true,
            scrollX: true
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