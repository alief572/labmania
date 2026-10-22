<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    td {
        padding: 0.50rem 0 0.50rem 0;
    }
</style>

<form action="" method="post" id="frm-data">
    <div class="box">
        <div class="box-header">
        </div>

        <div class="box-body">

            <table border="0" style="width: 100%;">
                <tr>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">No. SPK</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= $header->id_spk_penawaran ?>
                    </td>
                    <!-- <td valign="top" width="100"></td> -->
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Project Leader</td>
                    <td valign="top" class="pd-5" width="500" valign="top">
                        <?= ucfirst($header->nm_project_leader) ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Customer</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= $header->nm_customer ?>
                    </td>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Sales</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= ucfirst($header->nm_sales) ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Address</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= $header->address ?>
                    </td>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Waktu</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= date('d F Y', strtotime($header->waktu_from)) . ' - ' . date('d F Y', strtotime($header->waktu_to)) ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110">Project</td>
                    <td valign="top" class="pd-5" width="400" valign="top">
                        <?= $header->nm_project ?>
                    </td>
                    <td valign="top" class="pd-5 semi-bold" valign="top" width="110"></td>
                    <td valign="top" class="pd-5" width="400" valign="top">

                    </td>
                </tr>
            </table>
        </div>
    </div>

    <?php
    for ($i = 1; $i <= round($detail->mandays + $detail->mandays_tandem + $detail->mandays_subcont); $i++) {
    ?>
        <div class="box">
            <div class="box-header">
                <h4 style="font-weight: bold;">Activity Detail <?= $i ?></h4>
            </div>

            <div class="box-body">
                <div class="col-md-6">
                    <table width="100%" border="0">
                        <tr>
                            <td valign="top">Activity Name</td>
                            <td valign="top" colspan="3" width="400">
                                <input type="hidden" name="activity_detail[<?= $i ?>][id]" value="<?= $detail->id ?>">
                                <input type="hidden" name="activity_detail[<?= $i ?>][no_mandays]" value="<?= $i ?>">
                                <input type="hidden" name="activity_detail[<?= $i ?>][id_spk_penawaran]" value="<?= $header->id_spk_penawaran ?>">
                                <input type="hidden" name="activity_detail[<?= $i ?>][id_aktifitas]" value="<?= $detail->id_aktifitas ?>">
                                <input type="hidden" name="activity_detail[<?= $i ?>][nm_aktifitas]" value="<?= $detail->nm_aktifitas ?>">
                                <?= $detail->nm_aktifitas ?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">Date</td>
                            <td valign="top">
                                <input type="date" name="activity_detail[<?= $i ?>][tanggal]" id="" class="form-control form-control-sm">
                            </td>
                            <td valign="top"></td>
                            <td valign="top"></td>
                        </tr>
                        <tr>
                            <td valign="top">Time</td>
                            <td valign="top" width="180">
                                <input type="time" name="activity_detail[<?= $i ?>][time_from]" id="" class="form-control form-control-sm">
                            </td>
                            <td valign="top" class="text-center">
                                To
                            </td>
                            <td valign="top" width="180">
                                <input type="time" name="activity_detail[<?= $i ?>][time_to]" id="" class="form-control form-control-sm">
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">PIC</td>
                            <td valign="top">
                                <input type="text" name="activity_detail[<?= $i ?>][pic]" id="" class="form-control form-control-sm" placeholder="- PIC -">
                            </td>
                            <td valign="top"></td>
                            <td valign="top"></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-12"></div>

                <div class="col-md-6" style="margin-top: 2rem;">
                    <h4 style="font-weight: bold;">Report and Action Plan</h4>
                </div>
                <div class="col-md-6 text-right" style="margin-top: 2rem;">
                    <button type="button" class="btn btn-sm btn-success add_detail" data-no="<?= $i ?>">
                        <i class="fa fa-plus"></i> Add
                    </button>
                </div>

                <div class="col-md-12">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th class="text-center">No.</th>
                                <th class="text-center">Report/Isu</th>
                                <th class="text-center">PIC</th>
                                <th class="text-center">Action Plan</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list_report_action_detail_<?= $i ?>"></tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php
    }
    ?>

    <a href="<?= base_url('consultation_report/add/' . urlencode(str_replace('/', '|', $header->id_spk_penawaran))) ?>" class="btn btn-sm btn-danger">
        <i class="fa fa-arrow-left"></i> Back
    </a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa fa-save"></i> Save
    </button>
</form>

<input type="hidden" name="no_payment" value="1">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_detail_detail = 1;

    function get_num(nilai = null) {
        if (nilai !== '' && nilai !== null) {
            nilai = nilai.split(',').join('');
            nilai = parseFloat(nilai);
        } else {
            nilai = 0;
        }

        return nilai;
    }

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    $(document).on('click', '.add_detail', function() {
        var no = $(this).data('no');

        var max = 0;
        $('.td_no_' + no).each(function() {
            var value = parseFloat($(this).text());
            // if (!isNaN(value)) {
            max = max === null ? 0 : Math.max(max, value);
            // }
        });

        max++;

        var hasil = '<tr class="tr_detail_detail_' + no_detail_detail + '">';

        hasil += '<td class="text-center td_no_' + no + '">';
        hasil += '<input type="hidden" name="detail_detail_report[' + no_detail_detail + '][no_mandays]" value="' + no + '">';
        hasil += '<input type="hidden" name="detail_detail_report[' + no_detail_detail + '][id]" value="<?= $detail->id ?>">';
        hasil += '<input type="hidden" name="detail_detail_report[' + no_detail_detail + '][id_spk_penawaran]" value="<?= $header->id_spk_penawaran ?>">';
        hasil += max;
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<textarea name="detail_detail_report[' + no_detail_detail + '][report_isu]" class="form-control form-control-sm" placeholder="- Report / Isu -"></textarea>';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="detail_detail_report[' + no_detail_detail + '][pic]" placeholder="- PIC -">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="detail_detail_report[' + no_detail_detail + '][action_plan]" placeholder="- Action Plan -">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="date" class="form-control form-control-sm" name="detail_detail_report[' + no_detail_detail + '][due_date]" placeholder="- Due Date -">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<button type="button" class="btn btn-sm btn-danger del_detail_detail" data-no_detail_detail="' + no_detail_detail + '"><i class="fa fa-trash"></i></button>';
        hasil += '</td>';

        hasil += '</tr>';

        $('.list_report_action_detail_' + no).append(hasil);
        no_detail_detail++;
    });

    $(document).on('click', '.del_detail_detail', function() {
        var no = $(this).data('no_detail_detail');

        $('.tr_detail_detail_' + no).remove();
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        swal({
            type: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be saved !',
            showCancelButton: true
        }, function(next) {
            if(next) {
                var data = new FormData($('#frm-data')[0]);

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'save_cons',
                    data: data,
                    dataType: 'json',
                    cache: false,
                    success: function(result) {

                    },
                    error: function(result) {
                        swal({
                            type: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                });
            }
        });
    });
</script>