<input type="hidden" name="id" value="<?= $id ?>">
<table class="table table-striped">
    <thead>
        <tr>
            <th class="text-center">No.</th>
            <th class="text-center">Item</th>
            <th class="text-center">Qty Tambahan</th>
            <th class="text-center">Budget Tambahan</th>
            <th class="text-center">Total Pengajuan</th>
            <th class="text-center">Reason</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 0;
        $grand_total = 0;
        foreach ($list_data as $item) {
            $no++;
            $total_row = $item['qty_budget_tambahan'] * $item['budget_tambahan'];
            $grand_total += $total_row;

            echo '<tr>';
            echo '<td class="text-center">' . $no . '</td>';
            echo '<td class="text-left">' . $item['nm_item'] . '</td>';
            echo '<td class="text-center">' . number_format($item['qty_budget_tambahan']) . '</td>';
            echo '<td class="text-right">' . number_format($item['budget_tambahan']) . '</td>';
            echo '<td class="text-right">' . number_format($total_row) . '</td>';
            echo '<td class="text-left">' . $item['reason'] . '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong><?= number_format($grand_total) ?></strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<br><br>

<div class="form-group">
    <label for="">Reject Reason</label>
    <textarea name="reject_reason" id="" class="form-control form-control-sm"></textarea>
</div>