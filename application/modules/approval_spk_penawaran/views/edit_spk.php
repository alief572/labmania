<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');

$ttl_persen_komisi = ($list_spk_penawaran->persentase_pemberi_informasi_komisi + $list_spk_penawaran->persentase_sales_komisi + $list_spk_penawaran->persentase_others_komisi);

$ttl_nominal_komisi = ($list_spk_penawaran->nominal_pemberi_informasi_komisi + $list_spk_penawaran->nominal_sales_komisi + $list_spk_penawaran->nominal_others_komisi);
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
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

    .pd-5 {
        padding: 5px;
    }
</style>

<form action="" method="post" id="frm-data">
    <div class="box">
        <div class="box-header">

        </div>

        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Number</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" name="id_spk_penawaran" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->id_spk_penawaran ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Address</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" name="address" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->address ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Customer</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" name="customer" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->nm_customer ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Waktu</td>
                    <td class="pd-5" width="390" valign="top">
                        <table style="width: 100%">
                            <tr>
                                <td>
                                    <input type="date" name="waktu_from" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_from ?>">
                                </td>
                                <td class="text-center"> - </td>
                                <td>
                                    <input type="date" name="waktu_to" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_to ?>">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">PIC</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" name="pic" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->nm_pic ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Sales</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" name="sales" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Informasi Awal</td>
                    <td class="pd-5" width="390" valign="top">
                        <table border="0" style="width: 100%">
                            <tr>
                                <td>
                                    <input type="checkbox" name="" id="" checked disabled> <?= $list_penawaran->tipe_informasi_awal ?>
                                </td>
                                <td>
                                    <input type="text" name="detail_informasi_awal" id="" class="form-control form-control-sm" value="<?= $detail_informasi_awal ?>" readonly>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Upload</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="file_upload" name="sales" id="" class="form-control form-control-sm" disabled>
                        <?php
                        if (
                            $list_spk_penawaran->upload_proposal !== '' &&
                            $list_spk_penawaran->upload_proposal !== null &&
                            file_exists('./uploads/proposal_penawaran/' . $list_spk_penawaran->upload_proposal)
                        ) {
                            echo '<a href="' . base_url('uploads/proposal_penawaran/' . $list_spk_penawaran->upload_proposal) . '" target="_blank" class="btn btn-sm btn-primary" style="margin-top: 1rem;">
                            <i class="fa fa-download"></i> Download Proposal
                        </a>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%">
                <tr>
                    <td class="pd-5 semi-bold" valign="top" rowspan="2">Project</td>
                    <td class="pd-5" width="390" valign="top" rowspan="2">
                        <textarea name="" id="" class="form-control form-control-sm" readonly><?= $list_spk_penawaran->nm_project ?></textarea>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Project Leader</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="project_leader" id="" class="form-control form-control-sm select_project_leader" required>
                            <option value="">- Select Project Leader -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_project_leader) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . $item->id . '" ' . $selected . '>' . $item->nama . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Konsultan 1</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="konsultan_1" id="" class="form-control form-control-sm select_konsultan_1">
                            <option value="">- Select Konsultan 1 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_konsultan_1) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . $item->id . '" ' . $selected . '>' . $item->nama . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Divisi</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="divisi" id="" class="form-control form-control-sm select_divisi">
                            <option value="">- Select Divisi -</option>
                            <?php
                            foreach ($list_divisi as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_divisi) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . $item->id . '" ' . $selected . '>' . strtoupper($item->nama) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Konsultan 2</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="konsultan_2" id="" class="form-control form-control-sm select_konsultan_2">
                            <option value="">- Select Konsultan 2 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_konsultan_2) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . $item->id . '" ' . $selected . '>' . $item->nama . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4 style="font-weight: 600;">Subcont</h4>
        </div>
        <div class="box-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-center">Activity Name</th>
                        <th class="text-center">Mandays</th>
                        <th class="text-center">Mandays Subcont</th>
                        <th class="text-center">Price Subcont</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_mandays = 0;
                    $total_mandays_subcont = 0;
                    $total_subcont = 0;
                    foreach ($list_spk_penawaran_subcont as $item) {
                        echo '<tr class="subcont_' . $no . '">';
                        echo '<td>';
                        echo '<select class="form-control form-control-sm chosen_select" name="dt[' . $no . '][id_aktifitas]">';
                        foreach ($list_all_aktifitas as $item_aktifitas) {
                            $selected = '';
                            if ($item_aktifitas->id_aktifitas == $item->id_aktifitas) {
                                $selected = 'selected';
                            }

                            echo '<option value="' . $item_aktifitas->id_aktifitas . '" ' . $selected . '>' . $item_aktifitas->nm_aktifitas . '</option>';
                        }
                        echo '</select>';
                        echo '</td>';
                        echo '<td class="text-center">' . $item->mandays . ' <input type="hidden" name="dt[' . $no . '][mandays]" value="' . $item->mandays . '"></td>';
                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm edit_mandays_subcont mandays_subcont_' . $item->id . '" name="dt[' . $no . '][mandays_subcont]" data-id="' . $item->id . '" value="' . $item->mandays_subcont . '">';
                        echo '</td>';
                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm text-right edit_price_subcont price_subcont_' . $item->id . ' auto_num" name="dt[' . $no . '][price_subcont]" value="' . $item->price_subcont . '" data-id="' . $item->id . '">';
                        echo '</td>';
                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm total_subcont_' . $item->id . ' auto_num text-right" name="dt[' . $no . '][total_subcont]" readonly>';
                        echo '</td>';
                        echo '<td class="text-center">';
                        echo '<button type="button" class="btn btn-sm btn-danger del_subcont" data-no="' . $no . '" ><i class="fa fa-trash"></i></button>';
                        echo '</td>';
                        echo '</tr>';

                        $total_mandays += $item->mandays;
                        $total_mandays_subcont = $item->mandays_subcont;
                        $total_subcont = $item->total_subcont;
                        $no++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <th>Total</th>
                    <th class="text-center ttl_mandays"><?= $total_mandays ?></th>
                    <th class="text-center ttl_mandays_subcont"><?= $total_mandays_subcont ?></th>
                    <th class="text-center"></th>
                    <th class="text-center ttl_total_subcont"><?= number_format($total_subcont, 2) ?></th>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Nilai Kontrak</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm auto_num text-right" value="<?= $list_spk_penawaran->nilai_kontrak ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->total_mandays) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Biaya Subcont</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="biaya_subcont" id="" class="form-control form-control-sm auto_num text-right biaya_subcont" value="<?= number_format($list_spk_penawaran->biaya_subcont) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Mandays Subcont</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_subcont" id="" class="form-control form-control-sm text-right mandays_subcont" value="<?= $list_spk_penawaran->mandays_subcont ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Nilai Internal</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="nilai_internal" id="" class="form-control form-control-sm auto_num text-right nilai_internal" value="<?= $list_spk_penawaran->nilai_internal; ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Mandays Internal</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_internal" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->mandays_internal ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_rate" id="" class="form-control form-control-sm auto_num text-right mandays_rate" value="<?= $list_spk_penawaran->mandays_rate ?>" readonly>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4 style="font-weight: 600;">Komisi</h4>
        </div>

        <div class="box-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-center">Komisi</th>
                        <th class="text-center">Nama</th>
                        <th class="text-center">Persentase Komisi</th>
                        <th class="text-center">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Pemberi Informasi</td>
                        <td>
                            <input type="text" name="nama_pemberi_informasi_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_pemberi_informasi_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_pemberi_informasi_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_pemberi_informasi_komisi ?>" onchange="persen_komisi('pemberi_informasi')">
                        </td>
                        <td>
                            <input type="text" name="nominal_pemberi_informasi_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_pemberi_informasi_komisi, 2) ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>Sales</td>
                        <td>
                            <input type="text" name="nama_sales_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_sales_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_sales_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_sales_komisi ?>" onchange="persen_komisi('sales')">
                        </td>
                        <td>
                            <input type="text" name="nominal_sales_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_sales_komisi, 2) ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>Others</td>
                        <td>
                            <input type="text" name="nama_others_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_others_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_others_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_others_komisi ?>" onchange="persen_komisi('others')">
                        </td>
                        <td>
                            <input type="text" name="nominal_others_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_others_komisi, 2) ?>" readonly>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th class="text-right ttl_persen_komisi"><?= number_format($ttl_persen_komisi, 2) ?></th>
                        <th class="text-right ttl_nilai_komisi"><?= number_format($ttl_nominal_komisi, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <table style="width: 100%;" border="0">
                <tr>
                    <th valign="top">
                        <h4 style="font-weight: 600;">Term of Payment</h4> <br>
                        <table style="width: 100%" border="0">
                            <tr>
                                <td width="100">Nilai Project</td>
                                <td>
                                    <input type="text" name="" id="" class="form-control form-control-sm text-right auto_num nilai_project" value="<?= $nilai_project ?>" style="max-width: 200px;" readonly>
                                </td>
                            </tr>
                        </table>
                    </th>
                    <th class="text-right" valign="top">
                        <button type="button" class="btn btn-sm btn-success add_payment_term">
                            <i class="fa fa-plus"></i> Add
                        </button>
                    </th>
                </tr>
            </table>
        </div>

        <div class="box-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-center">Term of Payment</th>
                        <th class="text-center">Persentase</th>
                        <th class="text-center">Nominal</th>
                        <th class="text-center">Description</th>
                        <th class="text-center">Option</th>
                    </tr>
                </thead>
                <tbody class="list_payment_term">
                    <?php
                    $no_payment = 1;

                    $ttl_persen_payment = 0;
                    $ttl_nominal_payment = 0;
                    foreach ($list_spk_penawaran_payment as $item) {
                        echo '<tr class="payment_' . $no_payment . '">';
                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm" name="pt[' . $no_payment . '][term_payment]" value="' . $item->term_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm auto_num text-right persen_payment" name="pt[' . $no_payment . '][persen_payment]" data-no="' . $no_payment . '" value="' . $item->persen_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm auto_num text-right nominal_payment" name="pt[' . $no_payment . '][nominal_payment]" data-no="' . $no_payment . '" value="' . $item->nominal_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm" name="pt[' . $no_payment . '][desc_payment]" value="' . $item->desc_payment . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo '<button type="button" class="btn btn-sm btn-danger del_term_payment" data-no_payment="' . $no_payment . '"><i class="fa fa-trash"></i></button>';
                        echo '</td>';

                        echo '</tr>';

                        $ttl_persen_payment += $item->persen_payment;
                        $ttl_nominal_payment += $item->nominal_payment;

                        $no_payment++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th class="text-right ttl_persentase_payment"><?= number_format($ttl_persen_payment, 2) ?></th>
                        <th class="text-right ttl_nominal_payment"><?= number_format($ttl_nominal_payment, 2) ?></th>
                        <th class="text-right"></th>
                        <th class="text-right"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <a href="<?= base_url('spk_penawaran'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa fa-save"></i> Save
    </button>
</form>

<input type="hidden" name="no_payment" value="<?= $no_payment ?>">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').chosen({
            width: "100%"
        });

        $('.select_divisi').chosen();
        $('.select_project_leader').chosen();
        $('.select_konsultan_1').chosen();
        $('.select_konsultan_2').chosen();

        $('.auto_num').autoNumeric();
    });

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

    function hitung_mandays_subcont() {
        var no = "<?= $no ?>";

        var mandays = 0;
        for (i = 1; i <= no; i++) {
            var mandayss = get_num($('input[name="dt[' + i + '][mandays]"]').val());

            mandays += mandayss;
        }

        $('input[name="total_mandays"]').val(mandays);
        $('.ttl_mandays').html(mandays);

        var total_mandays = get_num($('input[name="total_mandays"]').val());

        var ttl_mandays_subcont = 0;
        for (i = 1; i <= no; i++) {
            var mandays_subcont = get_num($('input[name="dt[' + i + '][mandays_subcont]"]').val());

            ttl_mandays_subcont += mandays_subcont;
        }

        $('.ttl_mandays_subcont').html(ttl_mandays_subcont);
        $('input[name="mandays_subcont"]').val(ttl_mandays_subcont);

        var mandays_internal = parseFloat(total_mandays - ttl_mandays_subcont);

        $('input[name="mandays_internal"]').val(mandays_internal);
    }

    function hitung_total_subcont() {
        var no = "<?= $no ?>";

        var nilai_kontrak = "<?= $nilai_kontrak ?>";
        var total_mandays = "<?= $total_mandays ?>";

        var ttl_subcont = 0;
        for (i = 1; i <= no; i++) {
            var total_subcont = get_num($('input[name="dt[' + i + '][total_subcont]"]').val());

            ttl_subcont += parseFloat(total_subcont);
        }

        $('.biaya_subcont').val(number_format(ttl_subcont, 2));
        $('.ttl_total_subcont').html(number_format(ttl_subcont, 2));

        $('.nilai_internal').val(number_format((nilai_kontrak - ttl_subcont), 2));

        var mandays_rate = parseFloat((nilai_kontrak - ttl_subcont) / total_mandays);

        $('.mandays_rate').val(number_format(mandays_rate, 2));
    }

    function persen_komisi(tipe) {
        var persentase = get_num($('input[name="persentase_' + tipe + '_komisi"]').val());
        var nilai_internal = get_num($('.nilai_internal').val());

        var nilai_komisi = parseFloat(nilai_internal * persentase / 100);

        $('input[name="nominal_' + tipe + '_komisi"]').val(number_format(nilai_komisi, 2));

        var persen_pemberi_informasi = get_num($('input[name="persentase_pemberi_informasi_komisi"]').val());
        var persen_sales = get_num($('input[name="persentase_sales_komisi"]').val());
        var persen_others = get_num($('input[name="persentase_others_komisi"]').val());

        var nominal_pemberi_informasi = get_num($('input[name="nominal_pemberi_informasi_komisi"]').val());
        var nominal_sales = get_num($('input[name="nominal_sales_komisi"]').val());
        var nominal_others = get_num($('input[name="nominal_others_komisi"]').val());

        var ttl_persen = parseFloat(persen_pemberi_informasi + persen_sales + persen_others);
        var ttl_nominal = parseFloat(nominal_pemberi_informasi + nominal_sales + nominal_others);

        $('.ttl_persen_komisi').html(number_format(ttl_persen, 2));
        $('.ttl_nilai_komisi').html(number_format(ttl_nominal, 2));
    }

    function hitung_ttl_payment() {
        var no_payment = get_num($('input[name="no_payment"]').val());

        var ttl_persen_payment = 0;
        for (i = 1; i <= no_payment; i++) {
            var persen_payment = get_num($('input[name="pt[' + i + '][persen_payment]"]').val());

            ttl_persen_payment += persen_payment;
        }

        var ttl_nominal_payment = 0;
        for (i = 1; i <= no_payment; i++) {
            var nominal_payment = get_num($('input[name="pt[' + i + '][nominal_payment]"]').val());

            ttl_nominal_payment += nominal_payment;
        }

        $('.ttl_persentase_payment').html(number_format(ttl_persen_payment, 2));
        $('.ttl_nominal_payment').html(number_format(ttl_nominal_payment, 2));
    }

    $(document).on('change', '.edit_mandays_subcont', function() {
        var id = $(this).data('id');
        var mandays_subcont = parseFloat($(this).val());
        var price_subcont = get_num($('.price_subcont_' + id).val());

        var total = parseFloat(mandays_subcont * price_subcont);

        $('.total_subcont_' + id).val(number_format(total, 2));
        hitung_total_subcont();
        hitung_mandays_subcont();
    });

    $(document).on('change', '.edit_price_subcont', function() {
        var id = $(this).data('id');
        var price_subcont = get_num($(this).val());
        var mandays_subcont = get_num($('.mandays_subcont_' + id).val());

        var total = parseFloat(mandays_subcont * price_subcont);

        $('.total_subcont_' + id).val(number_format(total, 2));
        hitung_total_subcont();
        hitung_mandays_subcont();
    });

    $(document).on('click', '.add_payment_term', function() {
        var hasil = '<tr class="payment_' + no_payment + '">';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="pt[' + no_payment + '][term_payment]">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm auto_num text-right persen_payment" name="pt[' + no_payment + '][persen_payment]" data-no="' + no_payment + '">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm auto_num text-right nominal_payment" name="pt[' + no_payment + '][nominal_payment]" data-no="' + no_payment + '">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="pt[' + no_payment + '][desc_payment]">';
        hasil += '</td>';

        hasil += '<td class="text-center">';
        hasil += '<button type="button" class="btn btn-sm btn-danger del_term_payment" data-no_payment="' + no_payment + '"><i class="fa fa-trash"></i></button>';
        hasil += '</td>';

        hasil += '</tr>';

        $('.list_payment_term').append(hasil);

        $('.auto_num').autoNumeric();

        no_payment += 1;

        $('input[name="no_payment"]').val(no_payment);
    });

    $(document).on('click', '.del_term_payment', function() {
        var no_payment = $(this).data('no_payment');

        $('.payment_' + no_payment).remove();
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        swal({
            type: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be saved !',
            showCancelButton: true
        }, function(next) {
            if (next) {
                var formData = $('#frm-data').serialize();

                $.ajax({
                    type: "POST",
                    url: siteurl + active_controller + 'update_spk_penawaran',
                    data: formData,
                    cache: false,
                    dataType: "JSON",
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

    $(document).on('click', '.del_subcont', function() {
        var no = $(this).data('no');

        $('.subcont_' + no).remove();

        hitung_total_subcont();
        hitung_mandays_subcont();
    });

    $(document).on('change', '.persen_payment', function() {
        var no = $(this).data('no');
        var nilai_project = "<?= $nilai_project ?>";
        var persen = get_num($(this).val());

        var nilai_payment = parseFloat(nilai_project * persen / 100);

        $('input[name="pt[' + no + '][nominal_payment]"]').val(number_format(nilai_payment, 2));

        hitung_ttl_payment();
    });

    $(document).on('change', '.nominal_payment', function() {
        var no = $(this).data('no');
        var nilai_project = "<?= $nilai_project ?>";
        var nilai_payment = get_num($(this).val());

        var persen_payment = parseFloat(nilai_payment / nilai_project * 100);

        $('input[name="pt[' + no + '][persen_payment]"]').val(number_format(persen_payment, 2));

        hitung_ttl_payment();
    });
</script>