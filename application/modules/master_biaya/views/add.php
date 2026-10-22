<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<input type="hidden" name="id" value="">
<div class="form-group">
    <label for="">Nama Biaya <span class="text-red">*</span></label>
    <input type="text" name="nm_biaya" id="" class="form-control form-control-sm" required>
</div>
<div class="form-group">
    <label for="">Tipe Biaya <span class="text-red">*</span></label>
    <select name="tipe_biaya" id="" class="form-control form-control-sm" required>
        <option value="">- Select Tipe Biaya -</option>
        <option value="1">Akomodasi</option>
        <option value="2">Others</option>
    </select>
</div>
<div class="form-group">
    <label for="">COA <span class="text-red">*</span></label>
    <select name="coa" id="" class="form-control form-control-sm select2" required>
        <option value="">- Select COA -</option>
        <?php
        foreach ($list_coa as $item_coa) :
            echo '<option value="' . $item_coa->no_perkiraan . '">(' . $item_coa->no_perkiraan . ') - ' . $item_coa->nm_coa . '</option>';
        endforeach;
        ?>
    </select>
</div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $('.select2').select2({
        width: '100%'
    });
</script>