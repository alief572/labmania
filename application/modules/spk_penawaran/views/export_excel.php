<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=exce_spk_penawaran_consultant.xls");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Excel SPK Penawaran</title>
</head>
<body>
    <table border="1" width="100%">
        <thead>
            <tr>
                <th align="center">No</th>
                <th align="center">Nomor SPK</th>
                <th align="center">Marketing</th>
                <th align="center">Package</th>
                <th align="center">Customer</th>
                <th align="center">Grand Total</th>
                <th align="center">Created By</th>
                <th align="center">Created Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row) : ?>
                <tr>
                    <td align="center"><?= $row['no'] ?></td>
                    <td><?= $row['id_spk_penawaran'] ?></td>
                    <td><?= $row['nm_marketing'] ?></td>
                    <td><?= $row['nm_paket'] ?></td>
                    <td><?= $row['nm_customer'] ?></td>
                    <td align="right"><?= $row['grand_total'] ?></td>
                    <td><?= $row['created_by'] ?></td>
                    <td align="center"><?= $row['created_date'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
