<table class="table table-striped">
    <thead>
        <tr>
            <th class="text-center">No.</th>
            <th class="text-center">Item</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Price / Unit</th>
            <th class="text-center">Total</th>
            <th class="text-center">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 0;
        $ttl = 0;
        foreach ($list_lab as $item) {
            $no++;

            echo '<tr>';
            echo '<td class="text-center">' . $no . '</td>';
            echo '<td>' . $item->nm_biaya . '</td>';
            echo '<td class="text-center">' . number_format($item->qty) . '</td>';
            echo '<td class="text-right">' . number_format($item->price_unit_budget, 2) . '</td>';
            echo '<td class="text-right">' . number_format($item->total_budget, 2) . '</td>';
            echo '<td>' . $item->keterangan . '</td>';
            echo '</tr>';

            $ttl += $item->total_budget;
        }
        ?>
    </tbody>
    <tbody>
        <tr>
            <td colspan="4" class="text-right">
                <span style="font-weight: bold;">Grand Total</span>
            </td>
            <td class="text-right">
                <span style="font-weight: bold;">
                    <?= number_format($ttl, 2) ?>
                </span>
            </td>
            <td></td>
        </tr>
    </tbody>
</table>