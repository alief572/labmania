<?php
// $ENABLE_VIEW is intentionally not set here — permission check is handled in the controller.
?>
<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/datatables/dataTables.bootstrap.css') ?>">

<style>
    .btn {
        border-radius: 10px;
    }

    .filter-section {
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #fafafa;
    }

    .filter-section label {
        font-weight: bold;
        text-transform: uppercase;
        font-size: 11px;
        color: #666;
    }

    .btn-view {
        background-color: #17a2b8;
        color: #fff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        padding: 0;
        line-height: 30px;
        text-align: center;
        display: inline-block;
    }

    .btn-export-excel {
        background-color: #28a745;
        color: #fff;
        border-radius: 5px;
    }
</style>

<div class="box">
    <div class="box-header">
        <h3 class="box-title"><i class="fa fa-list"></i> Daftar SPK - Cashflow Budget</h3>
        <p class="text-muted" style="margin-top:5px;">Menampilkan SPK yang memiliki cashflow budget project. Klik ikon mata untuk melihat rincian, atau ekspor per baris.</p>
    </div>
    <div class="box-body">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-2">
                    <label>Periode (Tahun)</label>
                    <select id="year_filter" class="form-control">
                        <?php if (empty($years)): ?>
                            <option value="">Tidak ada data</option>
                        <?php else: ?>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>" <?= ($year == $default_year) ? 'selected' : '' ?>><?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2" style="padding-top: 20px;">
                    <button id="btn_filter" class="btn btn-info"><i class="fa fa-filter"></i> Terapkan</button>
                </div>
                <div class="col-md-8 text-right" style="padding-top: 20px;">
                    <button id="btn_export" class="btn btn-export-excel"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <table id="table_spk" class="table table-bordered table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor SPK</th>
                    <th>Customer</th>
                    <th>Sales</th>
                    <th>Konsultan</th>
                    <th>Package</th>
                    <th>Total Budget Project</th>
                    <th>Total Actual Project</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Hidden form for Excel export -->
<form id="export_form" method="POST" action="<?= base_url('cashflow_project/export_excel') ?>" style="display:none;">
    <input type="hidden" name="year" id="export_year" value="<?= htmlspecialchars($default_year, ENT_QUOTES, 'UTF-8') ?>">
</form>

<script src="<?= base_url('assets/adminlte/plugins/datatables/dataTables.bootstrap.js') ?>"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var table = initDataTable();

        // Filter button click
        $('#btn_filter').on('click', function() {
            table.ajax.reload();
            $('#export_year').val($('#year_filter').val());
        });

        // Export button
        $('#btn_export').on('click', function() {
            $('#export_year').val($('#year_filter').val());
            $('#export_form').submit();
        });
    });

    function formatRupiah(num) {
        if (num === null || num === undefined || num === '' || num === 0) return 'Rp 0';
        return 'Rp ' + parseFloat(num).toLocaleString('id-ID');
    }

    function initDataTable() {
        var table = $('#table_spk').DataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {
                    d.year = $('#year_filter').val();
                }
            },
            columns: [{
                    data: 'no',
                    className: 'text-center'
                },
                {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'nm_sales'
                },
                {
                    data: 'nm_project_leader'
                },
                {
                    data: 'nm_project'
                },
                {
                    data: 'total_budget',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                },
                {
                    data: 'total_actual',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                },
                {
                    data: 'option',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                }
            ],
            processing: true,
            serverSide: true,
            pageLength: 10,
            ordering: false,
            language: {
                emptyTable: "Tidak ada data untuk tahun yang dipilih",
                zeroRecords: "Tidak ada data yang ditemukan",
                search: "Search:",
                searchPlaceholder: "Cari nomor SPK, customer..."
            }
        });

        return table;
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>