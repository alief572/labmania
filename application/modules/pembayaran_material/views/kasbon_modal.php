<form action="" method="post" id="form-post" enctype="multipart/form-data">
    <div class="col-md-12">
        <div class="form-group">
            <label for="">No. Payment</label>
            <input type="text" name="no_payment" id="" class="form-control form-control-sm" value="<?= $data_payment->id ?>" readonly>
        </div>
        <div class="form-group">
            <label for="">Tipe</label>
            <input type="text" name="tipe" id="" class="form-control form-control-sm" value="<?= ucfirst($data_payment->tipe) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="">Nilai Bayar</label>
            <input type="text" name="nilai_bayar" id="" class="form-control form-control-sm text-right" value="<?= number_format($data_payment->jumlah, 2) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="">Tanggal Pembayaran <span style="color: red;">*</span></label>
            <input type="date" name="tanggal_pembayaran" id="" class="form-control form-control-sm" value="" required>
        </div>
        <div class="form-group">
            <label for="">Keterangan</label>
            <textarea name="keterangan" id="" class="form-control form-control-sm"></textarea>
        </div>
        <div class="form-group">
            <label for="">Bank <span style="color: red;">*</span></label>
            <select name="bank" id="" class="form-control form-control-sm select_2" required>
                <option value="">- Select Bank -</option>
                <?php
                foreach ($list_coa_bank as $item_bank) {
                    echo '<option value="' . $item_bank->no_perkiraan . '">' . $item_bank->nama . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="">Bukti Transfer <span style="color: red;">*</span></label>
            <input type="file" name="bukti_transfer" id="" class="form-control form-control-sm" required>
        </div>
        <div class="form-group">
            <label for="">Upload Document <span style="color: red;">*</span></label>
            <input type="file" name="upload_document" id="" class="form-control form-control-sm" required>
        </div>
    </div>
</form>