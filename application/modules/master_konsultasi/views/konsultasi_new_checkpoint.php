
<div class="row">
    <div class="col-md-12">
        <?php
        $form_id = 'FormCheckPoint';
        echo form_open(site_url('konsultasi/konsultasi-check-point/?' . $variables), array('id' => $form_id));
        ?>
        <div class="table-responsive">
            <table id="my-grid" class="table table-bordered table-modern TableCheckPoint" width="100%">
                <thead>
                    <tr>
                        <th width="40" style="text-align: center;">#</th>
                        <th>Detail Check Point</th>
                        <th width="50" style="text-align: center;">Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($cek_point->num_rows() > 0) {
                        $no = 1;
                        foreach ($cek_point->result() as $d) {
                            echo "
                            <tr>
                                <td style='vertical-align:middle; text-align:center; font-weight:600; color:#64748b;'>" . $no . "</td>
                                <td>
                                    <input type='text' class='form-control form-control-modern' name='check_point[]' id='check_point' value='" . htmlspecialchars($d->nm_chk_point) . "' placeholder='Nama check point...'>
                                    <input type='hidden' name='id_chk_point[]' value='" . $d->id_chk_point . "'>
                                    <input type='hidden' name='unik_id[]' value='" . $d->unique_id . "'>
                                </td>
                                <td align='center' style='vertical-align:middle;'>
                                    <button type='button' class='btn btn-danger btn-xs' id='RemoveCheck' title='Hapus Baris' style='border-radius: 4px; padding: 4px 8px;'><i class='fa fa-trash'></i></button>
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
        <button type="button" class="btn btn-success btn-sm" id="NewLine" style="border-radius: 6px; font-weight: 600;">
            <i class="fa fa-plus"></i> Tambah Baris Point
        </button>
        <div id="Notification_<?php echo $form_id; ?>" style="margin-top: 15px;"></div>
        <?php echo form_close(); ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var btnSave = "<button type='button' class='btn btn-primary' id='SaveFormPoint' data-form-id='<?php echo $form_id; ?>' style='border-radius: 6px; font-weight: 600;'><i class='fa fa-save'></i> Simpan</button>";
        var btnClose = "<button type='button' class='btn btn-default' data-dismiss='modal' style='border-radius: 6px; font-weight: 600;'><i class='fa fa-times'></i> Tutup</button>";
        $('#modal-footer').html(btnSave + btnClose);

        var JmlData = $('.TableCheckPoint tbody tr').length;
        if (JmlData < 1) {
            AppendCheckPoint();
        }
    });
</script>
