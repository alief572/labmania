<table style="width: 100%">
    <tr>
        <th align="left">
            <img src="<?= base_url('assets/images/logo_ssc.jpg'); ?>" alt="" width="150px" height="60px">
        </th>
        <td align="center">
            <span style="font-size: 16px; font-weight: bold;">SENTRAL SISTEM CONSULTING</span> <br>
            <span style="font-size: 11px">Jalan Letnan Jendral M.T. Haryono KAV.10 MTH Square Lt.3A No.2, <br>Cawang, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13330</span> <br>
            <span style="font-size: 11px">Telp (021 2906 7201-3) Fax (021 2906 7204)</span> <br>
            <span style="font-size: 11px">info@sentralsistem.com</span>
        </td>
        <th align="right">
            <img src="<?= base_url('assets/images/logo_kemnaker.jpg') ?>" alt="" width="150px" height="60px">
        </th>
    </tr>
</table>
<hr style="border: 1px solid black;">
<table style="width: 100%">
    <tr>
        <th align="center">
            <span style="font-size: 16px; font-weight: bold;">SURAT PERINTAH KERJA (SPK)</span>
        </th>
    </tr>
    <tr>
        <td align="center">
            <span style="font-size: 13px; ">
                Nomor: <?= $list_spk_penawaran->id_spk_penawaran ?>
            </span>
        </td>
    </tr>
</table>
<br><br>
<h3>Data Client</h3>
<hr>
<table style="width: 100%" border="0">
    <tr>
        <th align="left" valign="top" width="50">Customer</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= $list_spk_penawaran->nm_customer ?></td>
        <th align="left" valign="top" width="50">No. SPK</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= $list_spk_penawaran->id_spk_penawaran ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Alamat</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= $list_spk_penawaran->address ?></td>
        <th align="left" valign="top" width="50">No. NPWP</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= $list_spk_penawaran->npwp_cust ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">PIC</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= $list_customer->nm_pic ?></td>
        <th align="left" valign="top" width="50">Jabatan</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= $list_customer->jabatan_pic ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Kontak PIC</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= $list_customer->no_hp_pic ?></td>
        <th colspan="3"></th>
    </tr>
</table>

<br><br>

<h3>Marketing</h3>
<hr>
<table style="width: 100%;" border="0">
    <tr>
        <th align="left" valign="top" width="50">Sales</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= ucfirst($list_marketing->nm_karyawan) ?></td>
        <th align="left" valign="top" width="50">Informasi Awal Eksternal (Badan Sertifikasi)</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= (!empty($list_spk_penawaran->detail_info_awal_eks) && $list_spk_penawaran->tipe_info_awal_eks == 'bs') ? $list_spk_penawaran->detail_info_awal_eks . ' (' . $list_spk_penawaran->cp_info_awal_eks . ')' : '' ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Informasi Awal Internal</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= ucfirst($detail_informasi_awal) ?></td>
        <th align="left" valign="top" width="50">Informasi Awal Eksternal (Lain - lain)</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= (!empty($list_spk_penawaran->detail_info_awal_eks) && $list_spk_penawaran->tipe_info_awal_eks == 'lain') ? $list_spk_penawaran->detail_info_awal_eks . ' (' . $list_spk_penawaran->cp_info_awal_eks . ')' : '' ?></td>
    </tr>
</table>
<br><br>

<h3>Project</h3>
<hr>
<table style="width: 100%;" border="0">
    <tr>
        <th align="left" valign="top" width="50" rowspan="3">Project</th>
        <th align="center" valign="top" width="2" rowspan="3">:</th>
        <td width="100" valign="top" rowspan="3"><?= $nm_paket ?></td>
        <th align="left" valign="top" width="50">Project Leader</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= ucfirst($list_spk_penawaran->nm_project_leader) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Konsultan 1</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= ucfirst($list_spk_penawaran->nm_konsultan_1) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Konsultan 2</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top"><?= ucfirst($list_spk_penawaran->nm_konsultan_2) ?></td>
    </tr>
</table>
<br><br>

<h3>Detail Subcont</h3>
<hr>
<table width="100%" class="table table-bordered" border="1">
    <thead>
        <tr>
            <th align="center" width="20">No.</th>
            <th align="center" width="200">Activity Name</th>
            <th align="center" width="150">Mandays Subcont</th>
            <th align="center" width="150">Mandays Rate Subcont</th>
            <th align="center" width="200">Price</th>
            <th align="center" width="200">Description</th>
        </tr>
    </thead>
    <tbody>
        <?php

        $no_subcont = 1;
        foreach ($list_spk_penawaran_subcont as $item) {
            echo '<tr class="tr_list_subcont tr_list_subcont_' . $no_subcont . '">';
            echo '<td align="center">' . $no_subcont . '</td>';
            echo '<td>' . $item->nm_aktifitas . '</td>';
            echo '<td align="center">' . number_format($item->mandays_subcont) . '</td>';
            echo '<td align="right">' . number_format($item->price_subcont, 2) . '</td>';
            echo '<td align="right">' . number_format($item->total_subcont, 2) . '</td>';
            echo '<td>' . $item->keterangan . '</td>';
            echo '</tr>';

            $no_subcont++;
        }
        ?>
    </tbody>
</table>
<br><br>

<h3>Detail Akomodasi</h3>
<hr>
<table width="100%" class="table table-bordered" border="1">
    <thead>
        <tr>
            <th align="center">No.</th>
            <th align="center">Item</th>
            <th align="center">Qty</th>
            <th align="center">Price/Unit</th>
            <th align="center">Total</th>
            <th align="center">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $biaya_akomodasi = 0;

        $no_akomodasi = 1;
        foreach ($list_akomodasi as $item_akomodasi) {
            echo '<tr>';
            echo '<td align="center">' . $no_akomodasi . '</td>';
            echo '<td align="left">' . $item_akomodasi->nm_biaya . '</td>';
            echo '<td align="center">' . number_format($item_akomodasi->qty) . '</td>';
            echo '<td align="center">' . number_format($item_akomodasi->price_unit, 2) . '</td>';
            echo '<td align="center">' . number_format($item_akomodasi->total, 2) . '</td>';
            echo '<td align="left">' . $item_akomodasi->keterangan . '</td>';
            echo '</tr>';

            $biaya_akomodasi += $item_akomodasi->total;

            $no_akomodasi++;
        }
        ?>
    </tbody>
</table>
<br><br>

<h3>Detail Others</h3>
<hr>
<table width="100%" class="table table-bordered" border="1">
    <thead>
        <tr>
            <th align="center">No.</th>
            <th align="center">Item</th>
            <th align="center">Qty</th>
            <th align="center">Price/Unit Budget</th>
            <th align="center">Total Budget</th>
            <th align="center">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $biaya_others = 0;

        $no_others = 1;
        foreach ($list_others as $item_others) {
            echo '<tr>';
            echo '<td align="center">' . $no_others . '</td>';
            echo '<td align="left">' . $item_others->nm_biaya . '</td>';
            echo '<td align="center">' . number_format($item_others->qty) . '</td>';
            echo '<td align="center">' . number_format($item_others->price_unit_budget, 2) . '</td>';
            echo '<td align="center">' . number_format($item_others->total_budget, 2) . '</td>';
            echo '<td align="left">' . $item_others->keterangan . '</td>';
            echo '</tr>';

            $biaya_others += $item_others->total_budget;

            $no_others++;
        }
        ?>
    </tbody>
</table>
<br><br>

<h3>Detail Lab</h3>
<hr>
<table width="100%" class="table table-bordered" border="1">
    <thead>
        <tr>
            <th align="center">No.</th>
            <th align="center">Item</th>
            <th align="center">Qty</th>
            <th align="center">Price/Unit Budget</th>
            <th align="center">Total Budget</th>
            <th align="center">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $biaya_lab = 0;

        $no_lab = 1;
        foreach ($list_lab as $item_lab) {
            echo '<tr>';
            echo '<td align="center">' . $no_lab . '</td>';
            echo '<td align="left">' . $item_lab->nm_biaya . '</td>';
            echo '<td align="center">' . number_format($item_lab->qty) . '</td>';
            echo '<td align="center">' . number_format($item_lab->price_unit_budget, 2) . '</td>';
            echo '<td align="center">' . number_format($item_lab->total_budget, 2) . '</td>';
            echo '<td align="left">' . $item_lab->keterangan . '</td>';
            echo '</tr>';

            $biaya_lab += $item_lab->total_budget;

            $no_lab++;
        }
        ?>
    </tbody>
</table>
<br><br>

<h3>Summary</h3>
<hr>
<table width="100%">
    <tr>
        <th align="left" valign="top" width="50">Waktu</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= date('d-M-Y', strtotime($list_spk_penawaran->waktu_from)) . ' - ' . date('d-M-Y', strtotime($list_spk_penawaran->waktu_to)) ?></td>
        <th align="left" valign="top" width="50">Nilai Kontrak</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($list_spk_penawaran->nilai_kontrak, 2) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Divisi</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= $list_spk_penawaran->nm_divisi ?></td>
        <th align="left" valign="top" width="50">Biaya Akomodasi</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($biaya_akomodasi, 2) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Total Mandays</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= number_format($list_spk_penawaran->total_mandays) ?></td>
        <th align="left" valign="top" width="50">Biaya Subcont</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($list_spk_penawaran->biaya_subcont, 2) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Mandays Subcont</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= number_format($ttl_mandays_subcont) ?></td>
        <th align="left" valign="top" width="50">Biaya Others</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($biaya_others, 2) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Mandays Internal</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= number_format($list_spk_penawaran->mandays_internal) ?></td>
        <th align="left" valign="top" width="50">Biaya Lab</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($biaya_lab, 2) ?></td>
    </tr>
    <tr>
        <th align="left" valign="top" width="50">Mandays Rate</th>
        <th align="center" valign="top" width="2">:</th>
        <td width="100" valign="top"><?= number_format($list_spk_penawaran->mandays_rate, 2) ?></td>
        <th align="left" valign="top" width="50">Biaya Tandem</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($ttl_tandem, 2) ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <th align="left" valign="top" width="50">Biaya Subcont Tenaga Ahli</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($list_spk_penawaran->biaya_subcont_tenaga_ahli, 2) ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <th align="left" valign="top" width="50">Biaya Subcont Perusahaan</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($list_spk_penawaran->biaya_subcont_perusahaan, 2) ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <th align="left" valign="top" width="50">Nilai Kontrak Bersih</th>
        <th align="center" width="2" valign="top">:</th>
        <td width="100" valign="top">Rp. <?= number_format($list_spk_penawaran->nilai_kontrak_bersih, 2) ?></td>
    </tr>
</table>
<br><br>

<h3>Term of Payment</h3>
<hr>
<table width="100%" border="1">
    <thead>
        <tr>
            <th align="center">No.</th>
            <th align="center">Term of Payment</th>
            <th align="center">Persentase (%)</th>
            <th align="center">Nominal (Rp.)</th>
            <th align="center">Description</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no_top = 1;
        foreach ($list_spk_penawaran_payment as $item) {
            echo '<tr>';
            echo '<td align="center">' . $no_top . '</td>';
            echo '<td align="left">' . $item->term_payment . '</td>';
            echo '<td align="center">' . number_format($item->persen_payment, 2) . '</td>';
            echo '<td align="center">' . number_format($item->nominal_payment, 2) . '</td>';
            echo '<td align="left">' . $item->desc_payment . '</td>';
            echo '</tr>';

            $no_top++;
        }
        ?>
    </tbody>
</table>
<br><br>

<h3>Komisi</h3>
<hr>
<table width="100%" border="1">
    <thead>
        <tr>
            <th align="center">Komisi</th>
            <th align="center">Nama</th>
            <th align="center">Persentase Komisi (%)</th>
            <th align="center">Nominal (Rp.)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td align="center">Pemberi Informasi 1</td>
            <td align="center">
                <?= $list_spk_penawaran->nm_pemberi_informasi_1_komisi ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->persen_pemberi_informasi_1_komisi, 2) ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->nominal_pemberi_informasi_1_komisi, 2) ?>
            </td>
        </tr>
        <tr>
            <td align="center">Pemberi Informasi 2</td>
            <td align="center">
                <?= $list_spk_penawaran->nm_pemberi_informasi_2_komisi ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->persen_pemberi_informasi_2_komisi, 2) ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->nominal_pemberi_informasi_2_komisi, 2) ?>
            </td>
        </tr>
        <tr>
            <td align="center">Sales 1</td>
            <td align="center">
                <?= $list_spk_penawaran->nm_sales_1_komisi ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->persen_sales_1_komisi, 2) ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->nominal_sales_1_komisi, 2) ?>
            </td>
        </tr>
        <tr>
            <td align="center">Sales 2</td>
            <td align="center">
                <?= $list_spk_penawaran->nm_sales_2_komisi ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->persen_sales_2_komisi, 2) ?>
            </td>
            <td align="center">
                <?= number_format($list_spk_penawaran->nominal_sales_2_komisi) ?>
            </td>
        </tr>
    </tbody>
</table>
<br><br>

<h3>Isu Khusus dan Komitmen</h3>
<hr>
<table width="100%">
    <tr>
        <th colspan="3" align="left">Isu Khusus / Permintaan khusus dari customer / Tujuan Program / 3 objective utama (khusus konsultasi)</th>
    </tr>
    <tr>
        <th colspan="3" align="left">
            <textarea name="isu_khusus" id="" style="width: 100%;" rows="10" readonly><?= $list_spk_penawaran->isu_khusus ?></textarea>
        </th>
    </tr>
</table>

<script>
    window.print();
</script>