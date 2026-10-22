<style>
    .modal-detail-card {
        background: #f8fafc;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .modal-detail-card .pkg-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .modal-detail-card .pkg-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .table-detail-modern {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .table-detail-modern thead th {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        padding: 10px 12px !important;
        border: none !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .table-detail-modern tbody td {
        padding: 10px 12px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        font-size: 13px;
    }
    .table-detail-modern tfoot th {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        padding: 10px 12px !important;
        font-weight: 700;
        border-top: 2px solid #357ca5;
    }
    .table-detail-modern tfoot th span {
        color: #ffffff !important;
    }
    .checkpoint-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 10px 14px;
        margin: 5px 0 10px 0;
    }
    .checkpoint-header {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .checkpoint-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .checkpoint-item:last-child {
        border-bottom: none;
    }
    .checkpoint-badge {
        width: 18px;
        height: 18px;
        background: #e2e8f0;
        color: #475569;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
    }
</style>

<div>
    <div class="modal-detail-card">
        <div>
            <div class="pkg-label"><i class="fa fa-briefcase"></i> Paket Konsultasi</div>
            <div class="pkg-title"><?= $konsultasi_header->row()->nm_paket; ?></div>
        </div>
        <div>
            <span class="label label-primary" style="font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                <i class="fa fa-tag"></i> <?= $konsultasi_header->row()->id_konsultasi_h; ?>
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-detail-modern" width="100%">
            <thead>
                <tr>
                    <th width="4%" style="text-align: center;">#</th>
                    <th>Nama Aktifitas</th>
                    <th width="20%" style="text-align: right;">Harga</th>
                    <th width="12%" style="text-align: center;">Bobot</th>
                    <th width="12%" style="text-align: center;">Mandays</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ttl_harga = 0;
                $ttl_bobot = 0;
                $ttl_mandays = 0;

                if ($konsultasi_detail->num_rows() > 0) {
                    $no = 1;
                    foreach ($konsultasi_detail->result() as $dt) {
                ?>
                        <tr>
                            <td class="text-center" style="font-weight: 600; color: #64748b;"><?php echo $no; ?></td>
                            <td>
                                <div style="font-weight: 600; color: #1e293b;"><?php echo $dt->nm_aktifitas; ?></div>
                                <?php
                                $cek_point = $this->db
                                    ->select('id_chk_point, id_aktifitas, nm_chk_point')
                                    ->where('id_aktifitas', $dt->id_aktifitas)
                                    ->get('kons_master_check_point');
                                if ($cek_point->num_rows() > 0) {
                                ?>
                                    <div class="checkpoint-box">
                                        <div class="checkpoint-header">
                                            <i class="fa fa-list-check text-primary"></i>
                                            <span>Check Point (<?= $cek_point->num_rows(); ?> point):</span>
                                        </div>
                                        <?php
                                        $nomor = 1;
                                        foreach ($cek_point->result() as $d) {
                                        ?>
                                            <div class="checkpoint-item">
                                                <span class="checkpoint-badge"><?= $nomor; ?></span>
                                                <span><?= htmlspecialchars($d->nm_chk_point); ?></span>
                                            </div>
                                        <?php
                                            $nomor++;
                                        }
                                        ?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td class="text-right" style="font-weight: 600; color: #2e7d32;">
                                Rp <?= number_format($dt->harga_aktifitas); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 600;"><?php echo $dt->bobot; ?>%</span>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600;"><?php echo $dt->mandays; ?> Hari</span>
                            </td>
                        </tr>
                <?php
                        $ttl_harga += $dt->harga_aktifitas;
                        $ttl_bobot += $dt->bobot;
                        $ttl_mandays += $dt->mandays;
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center text-muted' style='padding: 20px;'>Belum ada aktifitas untuk paket ini</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right" style="color: #ffffff !important;">TOTAL:</th>
                    <th class="text-right" style="color: #ffffff !important; font-weight: 700;">Rp <?= number_format($ttl_harga) ?></th>
                    <th class="text-center" style="color: #ffffff !important; font-weight: 700;"><?= number_format($ttl_bobot) ?>%</th>
                    <th class="text-center" style="color: #ffffff !important; font-weight: 700;"><?= number_format($ttl_mandays) ?> Hari</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 6px; font-weight: 600;">
            <i class="fa fa-times"></i> Tutup
        </button>
    </div>
</div>