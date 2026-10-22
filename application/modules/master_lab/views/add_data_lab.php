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
    .input-group-addon-modern {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 8px 0 0 8px !important;
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 12px;
    }
    .form-control-addon {
        border-radius: 0 8px 8px 0 !important;
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

<form action="" id="form-data">
    <div class="row">
        <div class="col-md-12">
            <div class="form-section-title">
                <i class="fa fa-info-circle"></i> Informasi Pengujian Lab
            </div>
        </div>

        <div class="col-md-8">
            <div class="form-group-modern">
                <label for="isu_lingkungan">Isu Lingkungan <span style="color: #ef4444;">*</span></label>
                <input type="text" name="isu_lingkungan" class="form-control" id="isu_lingkungan" placeholder="Contoh: Udara Ambien, Emisi Sumber Bergerak">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group-modern">
                <label for="waktu">Durasi Waktu <span style="color: #ef4444;">*</span></label>
                <select name="waktu" id="waktu" class="form-control">
                    <option value="">- Pilih Waktu -</option>
                    <option value="1">1 Jam</option>
                    <option value="8">8 Jam</option>
                    <option value="24">24 Jam</option>
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group-modern">
                <label for="peraturan">Pengaturan Perundang-undangan <span style="color: #ef4444;">*</span></label>
                <textarea name="peraturan" id="peraturan" class="form-control" rows="3" placeholder="Masukkan dasar hukum / peraturan terkait..."></textarea>
            </div>
        </div>

        <div class="col-md-12" style="margin-top: 10px;">
            <div class="form-section-title">
                <i class="fa fa-money"></i> Tarif & Pemetaan Akun
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group-modern">
                <label for="harga_ssc">Harga SSC / Titik <span style="color: #ef4444;">*</span></label>
                <div class="input-group">
                    <span class="input-group-addon input-group-addon-modern">Rp</span>
                    <input type="text" name="harga_ssc" id="harga_ssc" class="form-control form-control-addon text-right auto_num" placeholder="0">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group-modern">
                <label for="harga_lab">Harga Lab / Titik <span style="color: #ef4444;">*</span></label>
                <div class="input-group">
                    <span class="input-group-addon input-group-addon-modern">Rp</span>
                    <input type="text" name="harga_lab" id="harga_lab" class="form-control form-control-addon text-right auto_num" placeholder="0">
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group-modern">
                <label for="coa">COA (Chart of Account) <span style="color: #ef4444;">*</span></label>
                <select name="coa" id="coa" class="form-control select2" style="width: 100%;">
                    <option value="">- Pilih Akun COA -</option>
                    <?php if (!empty($list_coa)) : ?>
                        <?php foreach ($list_coa as $item_coa) : ?>
                            <option value="<?= htmlspecialchars($item_coa->no_perkiraan) ?>">
                                (<?= htmlspecialchars($item_coa->no_perkiraan) ?>) - <?= htmlspecialchars($item_coa->nm_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#dialog-rekap .select2').select2({
            dropdownParent: $('#dialog-rekap'),
            width: '100%'
        });
    });
</script>