<?php
$ENABLE_ADD     = has_permission('History_SPK_Penawaran.Add');
$ENABLE_MANAGE  = has_permission('History_SPK_Penawaran.Manage');
$ENABLE_VIEW    = has_permission('History_SPK_Penawaran.View');
$ENABLE_DELETE  = has_permission('History_SPK_Penawaran.Delete');
?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    .btn {
        border-radius: 10px;
    }

    .dropdown-menu {
        top: 100%;
        position: absolute;
        overflow: auto;
    }
</style>

<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>

<div class="box">
    <div class="box-header">
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="table_spk" class="table table-bordered table-striped nowrap">
                <thead class="bg-primary">
                    <tr>
                        <th align="center">No</th>
                        <th align="center">ID History</th>
                        <th align="center">ID SPK</th>
                        <th align="center">ID Penawaran</th>
                        <th align="center">Date</th>
                        <th align="center">Sales</th>
                        <th align="center">Customer</th>
                        <th align="center">Project</th>
                        <th align="center">Nilai Kontrak</th>
                        <th align="center">Revisi</th>
                        <th align="center">Status</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        DataTables();
    });

    function DataTables() {
        var dataTables = $('#table_spk').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data',
                type: "POST",
                dataType: "JSON",
                data: function(d) {}
            },
            language: {
                loadingRecords: 'Please wait - Loading ...'
            },
            stateSave: false,
            autoWidth: true,
            columns: [{
                    data: 'no'
                },
                {
                    data: 'id_history'
                },
                {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'id_penawaran'
                },
                {
                    data: 'tgl_spk'
                },
                {
                    data: 'nm_sales'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'nm_project'
                },
                {
                    data: 'nilai_kontrak',
                    render: function(data, type, row) {
                        if (data) {
                            return formatCurrency(data);
                        }
                        return '0';
                    }
                },
                {
                    data: 'revisi'
                },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        if (data == '1') {
                            return '<span class="badge bg-green">Approved</span>';
                        } else if (data == '0') {
                            return '<span class="badge bg-red">Rejected</span>';
                        } else {
                            return '<span class="badge bg-blue">Draft</span>';
                        }
                    }
                },
                {
                    data: 'option'
                }
            ],
            responsive: true,
            processing: false,
            serverSide: true,
            destroy: true,
            paging: true,
            scrollX: true
        });
    }

    function formatCurrency(amount) {
        if (!amount) return '0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
</script>

<script src="<?= base_url('assets/js/basic.js') ?>"></script>