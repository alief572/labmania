<style>
    .form-group-modern {
        margin-bottom: 18px;
    }
    .form-group-modern label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .form-group-modern .form-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
        font-size: 13px;
        transition: all 0.2s ease;
        box-shadow: none;
    }
    .form-group-modern .form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .form-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #0369a1;
        background: #f0f9ff;
        border-left: 4px solid #0284c7;
        padding: 9px 14px;
        border-radius: 6px;
        margin-bottom: 18px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<input type="hidden" name="id" value="<?= htmlspecialchars($data_biaya->id) ?>">

<div class="row">
    <div class="col-md-12">
        <div class="form-section-title">
            <i class="fa fa-info-circle"></i> Informasi Subcont Perusahaan
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group-modern">
            <label for="nm_biaya">Nama Subcont Perusahaan <span style="color: #ef4444;">*</span></label>
            <input type="text" name="nm_biaya" id="nm_biaya" class="form-control" placeholder="Contoh: PT. Sumber Daya Alam, CV. Mitra Teknik" value="<?= htmlspecialchars($data_biaya->nm_biaya) ?>" required>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group-modern">
            <label for="coa">COA (Chart of Account) <span style="color: #ef4444;">*</span></label>
            <select name="coa" id="coa" class="form-control select2" style="width: 100%;" required>
                <option value="">- Pilih Akun COA -</option>
                <?php if (!empty($list_coa)) : ?>
                    <?php foreach ($list_coa as $item_coa) : ?>
                        <option value="<?= htmlspecialchars($item_coa->no_perkiraan) ?>" <?= ($item_coa->no_perkiraan == $data_biaya->no_coa) ? 'selected' : '' ?>>
                            (<?= htmlspecialchars($item_coa->no_perkiraan) ?>) - <?= htmlspecialchars($item_coa->nm_coa) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dialog-popup .select2').select2({
            dropdownParent: $('#dialog-popup'),
            width: '100%'
        });
    });
</script>