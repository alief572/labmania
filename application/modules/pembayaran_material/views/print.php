<div style="display: flex; justify-content: center !important; align-items: center !important; height: 100vh;">
    <table>
        <tr>
            <th colspan="2" align="center">
                <h4>Payment</h4>
            </th>
        </tr>
        <tr>
            <th align="center" width="300">No. Payment</th>
            <td width="400" style="border:1px solid black;"><?= $data_payment->id ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Tipe</th>
            <td width="400" style="border:1px solid black;"><?= $tipe ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Nilai Bayar</th>
            <td width="400" style="border:1px solid black;"><?= number_format($data_payment->nilai_bayar, 2) ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Tanggal Pembayaran</th>
            <td width="400" style="border:1px solid black;"><?= date('d F Y', strtotime($data_payment->tanggal_pembayaran)) ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Keterangan</th>
            <td width="400" height="100" style="border:1px solid black;"><?= $data_payment->keterangan_pembayaran ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Bank</th>
            <td width="400" style="border:1px solid black;"><?= $nm_bank ?></td>
        </tr>
        <tr>
            <th align="center" width="300">Bukti Transfer</th>
            <td width="400" height="500" style="border:1px solid black;">
                <img src="uploads/bukti_pembayaran/<?= $data_payment->bukti_transfer ?>" width="400" height="500">
            </td>
        </tr>
    </table>
</div>

<?php
if ($data_payment->upload_document !== '' && $data_payment->upload_document !== null && file_exists('uploads/upload_document_payment/' . $data_payment->upload_document)) {
?>

    <!-- <div style="page-break-before: always"> -->
        <img src="uploads/upload_document_payment/<?= $data_payment->upload_document ?>" style="width: 100%; height: 100%">
    <!-- </div> -->

<?php
}
?>