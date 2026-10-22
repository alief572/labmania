<?php
// fungsi header dengan mengirimkan raw data excel
$nama_tingkat = 'Finance';
if ($tingkat !== '1') {
    $nama_tingkat = 'Direktur';
}
header("Content-type: application/vnd-ms-excel");

// membuat nama file ekspor "export-to-excel.xls"
header("Content-Disposition: attachment; filename=Approval Request Payment Expense (" . $nama_tingkat . ").xls");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Excel Expense Finance</title>
</head>

<body>
    <table class="table table-bordered" border="1">
        <thead>
            <tr>
                <th align="center">No. Expense</th>
                <th align="center">Request By</th>
                <th align="center">Tanggal Pengajuan</th>
                <th align="center">Deskripsi Pengajuan</th>
                <th align="center">Kategori</th>
                <th align="center">Nilai Pengajuan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $ttl_expense = 0;
            foreach ($data as $item_expense) :
                $tipe = 'Expense';
                $deskripsi = !empty($item_expense->deskripsi) ? $item_expense->deskripsi : '-';

                echo '<tr>';
                echo '<td>' . $item_expense->id . '</td>';
                echo '<td>' . $item_expense->nama . '</td>';
                echo '<td>' . date('Y-m-d', strtotime($item_expense->created_date)) . '</td>';
                echo '<td>' . $deskripsi . '</td>';
                echo '<td>' . $tipe . '</td>';
                echo '<td align="right">' . number_format($item_expense->total_expense_report) . '</td>';
                echo '</tr>';

                $ttl_expense += $item_expense->total_expense_report;
            endforeach;
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" align="right">Grand Total</th>
                <th align="right"><?= number_format($ttl_expense) ?></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>