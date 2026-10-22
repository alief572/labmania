<div class="col-12">
    <?php
    $form_id = 'FormCheckPoint';
    echo form_open(site_url('master_aktifitas/aktifitas_check_point/?' . $variables), array('id' => $form_id));
    ?>
    <div class="table-responsive">
        <table id="my-grid" class="table table-bordered TableCheckPoint" width="100%" style="border-radius: 8px; overflow: hidden; border: 1px solid #edf2f7;">
            <thead>
                <tr style="background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%); color: #fff;">
                    <th width="50px" style="text-align: center; font-size: 12px; text-transform: uppercase;">#</th>
                    <th style="font-size: 12px; text-transform: uppercase;">Detail Check Point</th>
                    <th width="80px" style="text-align: center; font-size: 12px; text-transform: uppercase;">Hapus</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($cek_point->num_rows() > 0) {
                    $no = 1;
                    foreach ($cek_point->result() as $d) {
                        echo "
                            <tr>
                                <td style='vertical-align: middle; text-align: center; font-weight: 600; color: #64748b;'>
                                    " . $no . ".
                                </td>
                                <td>
                                    <input type='text' class='form-control' style='border-radius: 6px; border: 1px solid #cbd5e1;' name='check_point[]' id='check_point' placeholder='Masukkan deskripsi check point...' value='" . htmlspecialchars($d->nm_chk_point) . "' required>
                                    <input type='hidden' name='id_chk_point[]' value='" . $d->id_chk_point . "'>
                                    <input type='hidden' name='unik_id[]' value='" . $d->unique_id . "'>
                                </td>
                                <td align='center' style='vertical-align: middle;'>
                                ";
                        if ($no > 1) {
                ?>
                            <button type="button" id="RemoveCheck" title="Hapus Baris" class="btn btn-sm btn-danger" style="border-radius: 4px;">
                                <i class="fa fa-trash"></i>
                            </button>
                <?php
                        } else {
                            echo "<span class='text-muted' style='font-size: 11px;'>Baris 1</span>";
                        }
                        echo "
                                </td>
                            </tr>
                            ";
                        $no++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 10px;">
        <button type="button" class="btn btn-success btn-sm" id="NewLine" style="border-radius: 6px; font-weight: 600;">
            <i class="fa fa-plus"></i> Tambah Check Point
        </button>
    </div>
    <div id="Notification_<?php echo $form_id; ?>" style="margin-top: 10px;"></div>
    <?php echo form_close(); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript">
    $(document).ready(function() {
        var btnSave = "<button type='button' class='btn btn-primary' id='SaveFormPoint' data-form-id='<?php echo $form_id; ?>' style='border-radius: 6px; font-weight: 600;'><i class='fa fa-save'></i> Simpan Point</button>";
        var btnClose = "<button type='button' class='btn btn-default' data-dismiss='modal' style='border-radius: 6px; font-weight: 600;'><i class='fa fa-times'></i> Tutup</button>";
        $('#modal-footer').html(btnClose + btnSave);

        var JmlData = $('.TableCheckPoint tbody tr').length;
        if (JmlData < 1) {
            AppendCheckPoint();
        }
    });
</script>