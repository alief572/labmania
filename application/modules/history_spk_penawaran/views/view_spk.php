<?php
$header = $header;
$list_aktifitas = $list_aktifitas;
$list_subcont = $list_subcont;
$list_payment = $list_payment;

$ttl_persen_komisi = ($header->persen_pemberi_informasi_1_komisi + $header->persen_pemberi_informasi_2_komisi + $header->persen_sales_1_komisi + $header->persen_sales_2_komisi);
$ttl_nominal_komisi = ($header->nominal_pemberi_informasi_1_komisi + $header->nominal_pemberi_informasi_2_komisi + $header->nominal_sales_1_komisi + $header->nominal_sales_2_komisi);

$reject_by_label = '';
if ($header->reject_sales_sts !== null) { $reject_by_label = 'Sales'; }
if ($header->reject_konsultan_1_sts !== null) { $reject_by_label = 'Konsultan 1'; }
if ($header->reject_konsultan_2_sts !== null) { $reject_by_label = 'Konsultan 2'; }
if ($header->reject_project_leader_sts !== null) { $reject_by_label = 'Project Leader'; }
if ($header->reject_manager_sales_sts !== null) { $reject_by_label = 'Manager Sales'; }
if ($header->reject_level2_by !== null) { $reject_by_label = 'Direktur'; }
?>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .btn { border-radius: 10px; }
    .pd-5 { padding: 5px; }
    .top-total-project {
        width: 280px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 15px;
    }
    .bg-green { background-color: #28a745 !important; }
    .bg-red { background-color: #dc3545 !important; }
    .bg-blue { background-color: #007bff !important; }
</style>

<form action="" method="post" id="frm-data">
    <div class="box">
        <div class="box-header bg-gray">
            <h4>ID History: <?= $header->id_history ?></h4>
            <h4>ID SPK: <?= $header->id_spk_penawaran ?></h4>
            <h4>Revisi: <?= $header->revisi ?></h4>
            <span class="badge <?= ($header->sts_spk == '1') ? 'bg-green' : (($header->sts_spk == '0') ? 'bg-red' : 'bg-blue') ?>">
                <?= ($header->sts_spk == '1') ? 'Approved' : (($header->sts_spk == '0') ? 'Rejected' : 'Draft') ?>
            </span>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4>Data Client</h4>
        </div>
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="110">Customer</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="customer" class="form-control form-control-sm text-center" value="<?= $header->nm_customer ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="110">No. SPK</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="id_spk_penawaran" class="form-control form-control-sm text-center" value="<?= $header->id_spk_penawaran ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" rowspan="2">Alamat</td>
                    <td class="pd-5" width="400" valign="top" rowspan="2">
                        <textarea name="address" class="form-control form-control-sm" rows="4" readonly><?= $header->address ?></textarea>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">No. NPWP</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="no_npwp" class="form-control form-control-sm text-center" value="<?= $header->npwp_cust ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">PIC</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="pic" class="form-control form-control-sm" value="<?= $header->nm_pic ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td colspan="2"><h4>Marketing</h4></td>
                    <td colspan="2"><h4>Informasi Awal Eksternal</h4></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Sales</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="sales" class="form-control form-control-sm text-center" value="<?= $header->nm_sales ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Tipe</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->tipe_info_awal_eks ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Project Leader</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="project_leader" class="form-control form-control-sm text-center" value="<?= $header->nm_project_leader ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Detail</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->detail_info_awal_eks ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Konsultan 1</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="konsultan_1" class="form-control form-control-sm text-center" value="<?= $header->nm_konsultan_1 ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Divisi</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_divisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Konsultan 2</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="konsultan_2" class="form-control form-control-sm text-center" value="<?= $header->nm_konsultan_2 ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Project</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_project ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td colspan="4"><h4>Informasi Waktu</h4></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Waktu Mulai</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" name="waktu_from" class="form-control form-control-sm" value="<?= $header->waktu_from ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Waktu Selesai</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="waktu_to" class="form-control form-control-sm" value="<?= $header->waktu_to ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4>Detail Aktivitas</h4>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped" id="table_aktifitas">
                <thead class="bg-primary">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Aktivitas</th>
                        <th class="text-center">Mandays</th>
                        <th class="text-center">Rate</th>
                        <th class="text-center">Tandem</th>
                        <th class="text-center">Rate Tandem</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_aktifitas = 0;
                    foreach ($list_aktifitas as $item) {
                        $total_aktifitas += $item->total_aktifitas;
                        echo '<tr>';
                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>' . $item->nama_aktifitas . '</td>';
                        echo '<td class="text-center">' . number_format($item->mandays) . '</td>';
                        echo '<td class="text-right">' . number_format($item->mandays_rate) . '</td>';
                        echo '<td class="text-center">' . number_format($item->mandays_tandem) . '</td>';
                        echo '<td class="text-right">' . number_format($item->mandays_rate_tandem) . '</td>';
                        echo '<td class="text-right">' . number_format($item->total_aktifitas) . '</td>';
                        echo '</tr>';
                        $no++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right semi-bold">Total Aktivitas</td>
                        <td class="text-right semi-bold"><?= number_format($total_aktifitas) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if (!empty($list_subcont)) { ?>
    <div class="box">
        <div class="box-header">
            <h4>Detail Subcont</h4>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped" id="table_subcont">
                <thead class="bg-primary">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Aktivitas</th>
                        <th class="text-center">Mandays</th>
                        <th class="text-center">Rate</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_subcont = 0;
                    foreach ($list_subcont as $item) {
                        $total_subcont += $item->total_subcont;
                        echo '<tr>';
                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>' . $item->nm_aktifitas . '</td>';
                        echo '<td class="text-center">' . number_format($item->mandays_subcont) . '</td>';
                        echo '<td class="text-right">' . number_format($item->price_subcont) . '</td>';
                        echo '<td class="text-right">' . number_format($item->total_subcont) . '</td>';
                        echo '<td>' . $item->keterangan . '</td>';
                        echo '</tr>';
                        $no++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right semi-bold">Total Subcont</td>
                        <td class="text-right semi-bold"><?= number_format($total_subcont) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php } ?>

    <?php if (!empty($list_payment)) { ?>
    <div class="box">
        <div class="box-header">
            <h4>Detail Payment</h4>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped" id="table_payment">
                <thead class="bg-primary">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Termin</th>
                        <th class="text-center">Persentase</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_payment = 0;
                    foreach ($list_payment as $item) {
                        $total_payment += $item->nominal_payment;
                        echo '<tr>';
                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>' . $item->term_payment . '</td>';
                        echo '<td class="text-center">' . number_format($item->persen_payment) . '%</td>';
                        echo '<td class="text-right">' . number_format($item->nominal_payment) . '</td>';
                        echo '<td>' . (!empty($item->desc_payment) ? $item->desc_payment : '-') . '</td>';
                        echo '</tr>';
                        $no++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right semi-bold">Total Payment</td>
                        <td class="text-right semi-bold"><?= number_format($total_payment) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php } ?>

    <div class="box">
        <div class="box-header">
            <h4>Ringkasan Nilai</h4>
        </div>
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Nilai Kontrak</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" name="nilai_kontrak" class="form-control form-control-sm text-right" value="<?= number_format($header->nilai_kontrak) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Subcont</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="biaya_subcont" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_subcont) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Akomodasi</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" name="biaya_akomodasi" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_akomodasi) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Others</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="biaya_others" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_others) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Tandem</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" name="biaya_tandem" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_tandem) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Lab</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="biaya_lab" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_lab) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Subcont Tenaga Ahli</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" name="biaya_subcont_tenaga_ahli" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_subcont_tenaga_ahli) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Biaya Subcont Perusahaan</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="biaya_subcont_perusahaan" class="form-control form-control-sm text-right" value="<?= number_format($header->biaya_subcont_perusahaan) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Nilai Kontrak Bersih</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="nilai_kontrak_bersih" class="form-control form-control-sm text-right" value="<?= number_format($header->nilai_kontrak_bersih) ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <?php if ($ttl_persen_komisi > 0 || $ttl_nominal_komisi > 0) { ?>
    <div class="box">
        <div class="box-header">
            <h4>Komisi</h4>
        </div>
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td colspan="4"><h5>Pemberi Informasi</h5></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Nama 1</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_pemberi_informasi_1_komisi ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Persentase</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm text-right" value="<?= $header->persen_pemberi_informasi_1_komisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Nama 2</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_pemberi_informasi_2_komisi ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Persentase</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm text-right" value="<?= $header->persen_pemberi_informasi_2_komisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td colspan="4"><h5>Sales</h5></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Sales 1</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_sales_1_komisi ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Persentase</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm text-right" value="<?= $header->persen_sales_1_komisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Sales 2</td>
                    <td class="pd-5" width="300" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->nm_sales_2_komisi ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Persentase</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm text-right" value="<?= $header->persen_sales_2_komisi ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php } ?>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Input By</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->input_by ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="210">Input Date</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $header->input_date ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</form>