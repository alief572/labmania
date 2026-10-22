<?php
$ENABLE_ADD     = has_permission('Approval_expense_Report_Project.Add');
$ENABLE_MANAGE  = has_permission('Approval_expense_Report_Project.Manage');
$ENABLE_VIEW    = has_permission('Approval_expense_Report_Project.View');
$ENABLE_DELETE  = has_permission('Approval_expense_Report_Project.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">

<style>
    .expense-approval .btn {
        border-radius: 10px;
    }

    .expense-approval .box-header { padding: 18px 20px; border-bottom: 1px solid #e5e7eb; }
    .expense-approval .box-title { font-size: 17px; font-weight: 600; }
    .expense-approval .box-body { padding: 16px 20px; }
    .expense-approval .table-responsive { overflow-x: auto; }
    .expense-approval table { width: 100%; table-layout: fixed; }
    .expense-approval table th { background: #f5f6f8; font-size: 12px; color: #475569; }
    .expense-approval table td { vertical-align: middle; padding: 14px 10px; overflow-wrap: anywhere; white-space: normal; }
    .expense-approval .cell-primary { font-weight: 600; color: #253649; }
    .expense-approval .cell-secondary { margin-top: 5px; color: #5f6b7a; font-size: 12px; line-height: 1.6; }
    .expense-approval .cell-label { font-size: 11px; color: #5f6b7a; }
    .expense-approval .cell-amount { font-weight: 700; white-space: nowrap; }
    .expense-approval .badge { white-space: normal; line-height: 1.4; }
    .expense-approval .dt-search input { border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px; }
    .expense-approval .btn:focus-visible { outline: 2px solid #253649; outline-offset: 3px; }
    @media (max-width: 1100px) {
        .expense-approval table { min-width: 950px; }
        .expense-approval .box-body { padding: 12px; }
    }

    .expense-approval .dropdown-menu {
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

    .expense-approval .dropdown-menu li a {
        display: block;
        padding: 5px 15px;
        color: #333;
        text-decoration: none;
        white-space: nowrap;
    }

    .expense-approval .dropdown-menu li a:hover {
        background-color: #f5f5f5;
    }

</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box expense-approval">
    <div class="box-header">
        <h3 class="box-title">Daftar Expense Report</h3>
        <p class="cell-secondary" style="margin-bottom: 0;">Expense report yang menunggu persetujuan.</p>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table id="table_penawaran" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 4%;">No</th>
                        <th scope="col" style="width: 21%;">Nomor SPK &amp; Paket</th>
                        <th scope="col" style="width: 14%;">Customer</th>
                        <th scope="col" style="width: 15%;">Team / PIC</th>
                        <th scope="col" style="width: 24%;">Expense Info</th>
                        <th scope="col" class="text-right" style="width: 13%;">Nominal</th>
                        <th scope="col" class="text-center" style="width: 9%;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        DataTables();
    });

    $(document).on('click', '.del_spk_budget', function() {
        var id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be deleted !',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_spk_budgeting',
                    data: {
                        'id': id
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.pesan
                            }).then((lanjut) => {
                                DataTables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.pesan
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later!'
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.req_approval', function(e) {
        e.preventDefault();

        var id_spk_budgeting = $(this).data('id_spk_budgeting');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This action cannot be undo !',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'req_approval_kasbon',
                    data: {
                        'id_spk_budgeting': id_spk_budgeting
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.pesan
                            }).then((lanjut) => {
                                DataTables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.pesan
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later!'
                        });
                    }
                });
            }
        });
    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        function escapeText(value) {
            return $('<div>').text(value == null || value === '' ? '-' : value).html();
        }

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
                    className: 'text-center'
                },
                {
                    data: 'nomor_spk',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return '<div class="cell-primary">' + escapeText(data) + '</div>' +
                            '<div class="cell-secondary"><i class="fa fa-briefcase" aria-hidden="true"></i> ' + escapeText(row.package) + '</div>';
                    }
                },
                {
                    data: 'customer',
                    render: function(data, type) { return type === 'display' ? escapeText(data) : data; }
                },
                {
                    data: 'project_leader',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return '<div><span class="cell-label">PL:</span> ' + escapeText(data) + '</div>' +
                            '<div class="cell-secondary">Sales: ' + escapeText(row.sales) + '</div>';
                    }
                },
                {
                    data: 'nomor_expense',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return '<div class="cell-primary">' + escapeText(data) + '</div>' +
                            '<div class="cell-secondary">Pengajuan: ' + escapeText(row.nomor_pengajuan) + '</div>' +
                            '<div class="cell-secondary">' + (row.tipe || '') + '</div>' +
                            '<div class="cell-secondary">' + escapeText(row.keterangan) + '</div>';
                    }
                },
                {
                    data: 'nominal',
                    className: 'text-right',
                    render: function(data, type) {
                        return type === 'display' ? '<span class="cell-amount">Rp ' + escapeText(data) + '</span>' : data;
                    }
                },
                {
                    data: 'action',
                    className: 'text-center',
                    render: function(data, type) {
                        if (type !== 'display') return data;
                        var action = $('<div>').html(data);
                        action.find('a').attr('title', 'Review Expense Report').attr('aria-label', 'Review Expense Report').text('Review');
                        return action.html();
                    }
                }
            ],
            processing: true,
            serverSide: true,
            stateSave: true,
            stateLoadParams: function(settings, data) {
                return data.columns && data.columns.length === 7;
            },
            destroy: true,
            paging: true,
            autoWidth: false,
            ordering: false
        });
    }
</script>
<script type="text/javascript">
    $(document).on('click', '.expense-approval .dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.expense-approval .dropdown-menu').hide();
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
            $('.expense-approval .dropdown-menu').hide();
        }
    });
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
