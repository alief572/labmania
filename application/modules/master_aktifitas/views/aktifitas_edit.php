<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.css" integrity="sha512-0nkKORjFgcyxv3HbE4rzFUlENUMNqic/EzDIeYCgsKa/nwqr2B91Vu/tNAu4Q0cBuG4Xe/D1f/freEci/7GDRA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    .card-modern {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
        margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header-modern {
        padding: 18px 24px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .card-title-modern {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .card-title-icon {
        width: 36px;
        height: 36px;
        background: #fef3c7;
        color: #d97706;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .card-body-modern {
        padding: 24px;
    }

    /* Tab Segmented Styling */
    .tab-segmented {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        margin-bottom: 22px;
        border: none;
        list-style: none;
    }
    .tab-segmented > li {
        margin: 0;
    }
    .tab-segmented > li > a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border: none;
        transition: all 0.2s ease;
        background: transparent;
    }
    .tab-segmented > li.active > a,
    .tab-segmented > li > a:hover {
        background: #ffffff;
        color: #0284c7;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .table-modern {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #edf2f7;
    }
    .table-modern thead th {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        padding: 12px 14px !important;
        border: none !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle !important;
    }
    .table-modern tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
    }
    .form-control-modern {
        border-radius: 6px !important;
        border: 1px solid #cbd5e1;
        box-shadow: none;
        transition: all 0.2s ease;
    }
    .form-control-modern:focus {
        border-color: #3c8dbc;
        box-shadow: 0 0 0 3px rgba(60, 141, 188, 0.15);
    }
    .action-btn-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        margin-top: 15px;
        border-top: 1px solid #edf2f7;
        flex-wrap: wrap;
        gap: 10px;
    }
</style>

<div class="card-modern">
    <div class="card-header-modern">
        <div class="card-title-modern">
            <span class="card-title-icon"><i class="fa fa-pencil-square-o"></i></span>
            <span>Edit Master Aktifitas</span>
            <span class="label label-primary" style="font-size: 12px; margin-left: 5px;"><?= $id_aktifitas; ?></span>
        </div>
    </div>
    <div class="card-body-modern">
        <ul class="tab-segmented">
            <li>
                <a href="<?php echo site_url('master_aktifitas'); ?>">
                    <i class="fa fa-table"></i> Data Aktifitas
                </a>
            </li>
            <li class="active">
                <a href="<?php echo site_url('master_aktifitas/aktifitas_edit/' . $id_aktifitas); ?>">
                    <i class="fa fa-pencil"></i> Edit Aktifitas
                </a>
            </li>
        </ul>

        <?php
        $form_id  = 'FormAktifitas';
        echo form_open(site_url('master_aktifitas/aktifitas_edit/' . $id_aktifitas), array('id' => $form_id));
        ?>
        <div class="table-responsive">
            <table id="my-grid" class="table table-bordered table-modern Tableaktifitas" width="100%">
                <thead>
                    <tr>
                        <th width="35%">Nama Aktifitas <span class="text-danger">*</span></th>
                        <th width="20%">Harga (Rp) <span class="text-danger">*</span></th>
                        <th width="15%">Mandays (Hari) <span class="text-danger">*</span></th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($aktifitas->num_rows() > 0) {
                        foreach ($aktifitas->result() as $d) {
                    ?>
                            <tr>
                                <td>
                                    <textarea name="nm_aktifitas[]" id="nm_aktifitas" class="form-control form-control-modern" rows="3" placeholder="Nama aktifitas..." required><?= @$d->nm_aktifitas ?></textarea>
                                    <input type='hidden' name='aktifitas_num[]' value="<?php echo @$d->id_aktifitas; ?>">
                                    <input type='hidden' name='aktifitas_unique_id[]' value="<?php echo @$d->unique_id; ?>">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-addon" style="background: #f8fafc; font-weight: 600;">Rp</span>
                                        <input type='text' class='form-control form-control-modern auto_num text-right' name='hrg_aktifitas[]' value="<?php echo @$d->harga_aktifitas; ?>" required>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type='number' class='form-control form-control-modern text-center' name='mandays[]' value="<?php echo @$d->mandays; ?>" min="0" required>
                                        <span class="input-group-addon" style="background: #f8fafc;">Hari</span>
                                    </div>
                                </td>
                                <td>
                                    <textarea name="keterangan[]" class="form-control form-control-modern" rows="3" placeholder="Keterangan tambahan (opsional)..."><?= $d->keterangan ?></textarea>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="action-btn-bar">
            <div>
                <a href="<?php echo site_url('master_aktifitas'); ?>" class="btn btn-default" style="border-radius: 6px; font-weight: 600;">
                    <i class="fa fa-arrow-left"></i> Kembali ke Data
                </a>
            </div>
            <div>
                <button type="button" class="btn btn-primary" id="SaveKonfirmasi" style="border-radius: 6px; font-weight: 600; padding: 6px 20px;">
                    <i class="fa fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="modal fade" id="MyModal" role="dialog" aria-labelledby="MyModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" id="modal-header" style="background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%); color: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 0.9;"><i class="fa fa-times-circle"></i></button>
                <h4 class="modal-title" id="modal-title" style="font-weight: 600;">Add Check Point</h4>
            </div>
            <div class="modal-body" id="modal-body" style="padding: 20px;"></div>
            <div class="modal-footer" id="modal-footer" style="border-top: 1px solid #eef2f6;"></div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/basic.js') ?>"></script>
<script src="<?= base_url('assets/js/autoNumeric.js') ?>"></script>
<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.auto_num').autoNumeric('init');

        $("#<?php echo $form_id; ?>").keypress(function(e) {
            if (e.which == 13 && e.target.tagName != 'TEXTAREA') {
                return false;
            }
        });

        $('.chosen-select').chosen({
            width: '100%'
        });

        // Proses Checking Before Saving
        $(document).on('click', '#SaveKonfirmasi', function(e) {
            e.preventDefault();

            var isValid = true;
            var emptyFields = 0;

            $('.Tableaktifitas tbody tr').each(function() {
                var nm = $(this).find('textarea[name="nm_aktifitas[]"]').val().trim();
                var hrg = $(this).find('input[name="hrg_aktifitas[]"]').val().trim();
                var mandays = $(this).find('input[name="mandays[]"]').val().trim();

                if (nm === '' || hrg === '' || mandays === '') {
                    emptyFields++;
                }
            });

            if (emptyFields > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Form Belum Lengkap',
                    text: 'Pastikan Nama Aktifitas, Harga, dan Mandays sudah diisi!'
                });
                return false;
            }

            Swal.fire({
                title: 'Konfirmasi Simpan',
                text: 'Apakah Anda yakin ingin menyimpan perubahan data aktifitas ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3c8dbc',
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fa fa-save"></i> Ya, Simpan!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan Perubahan...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: $('#<?php echo $form_id; ?>').attr('action'),
                        cache: false,
                        type: 'POST',
                        data: $('#<?php echo $form_id; ?>').serialize(),
                        dataType: 'json',
                        success: function(data) {
                            if (data.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: data.pesan,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Gagal Menyimpan',
                                    text: data.pesan
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Gagal terhubung ke server. Silakan coba lagi.'
                            });
                        }
                    });
                }
            });
        });
    });

    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * BUTTON FOR PROCESS SHOW MODAL FORM CHECK POINT
     */
    $(document).on('click', '#AddChekPoint', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        var nm_aktifitas = $(this).parent().parent().find('td:nth-child(1) input');
        var hrg_aktifitas = $(this).parent().parent().find('td:nth-child(2) input');
        var bobot = $(this).parent().parent().find('td:nth-child(3) input');
        var mandays = $(this).parent().parent().find('td:nth-child(4) input');
        var serialize = $(this).parent().parent().find('input').serialize();
        var index_parent = $(this).parent().parent().index();

        if (nm_aktifitas.val() == '') {
            alert('Aktifitas tidak boleh kosong');
            nm_aktifitas.focus();
            return false;
        } else if (hrg_aktifitas.val() == '') {
            alert('Harga tidak boleh kosong');
            hrg_aktifitas.focus();
            return false;
        } else if (bobot.val() == '') {
            alert('Bobot tidak boleh kosong');
            bobot.focus();
            return false;
        } else if (mandays.val() == '') {
            alert('Mandays tidak boleh kosong');
            mandays.focus();
            return false;
        } else {
            BlurPage('MyModal');
            $('.modal-dialog').removeClass('modal-lg');
            $('.modal-dialog').removeClass('modal-sm');
            $('.modal-dialog').addClass('modal-lg');
            $('#modal-title').html('Add Check Point');
            $('#modal-body').load(link + "?" + serialize + "&indexnya=" + index_parent);
            $('#MyModal').modal({
                backdrop: 'static',
                keyboard: false
            });
            $('#MyModal').modal('show');
        }
    });

    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * PROCESS NEW LINE & REMOVE LINE CHECK POINT
     */
    $(document).on('click', '#NewLine', function(e) {
        e.preventDefault();
        AppendCheckPoint();
    });
    $(document).on('click', '#RemoveCheck', function(e) {
        e.preventDefault();
        $(this).parent().parent().remove();
        var Nomor = 1;
        $('.TableCheckPoint tbody tr').each(function() {
            $(this).find('td:nth-child(1)').html(Nomor);
            Nomor++;
        });
    });
    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * FUNCTION ADD NEW LINE CHECK POINT
     */
    function AppendCheckPoint() {
        var Nomor = $('.TableCheckPoint tbody tr').length + 1;
        var Hapus = "<a href='#' class='btn btn-xs btn-danger' id='RemoveCheck' title='Hapus Baris'><i class='fa fa-trash'></i></a>";
        if (Nomor == 1) {
            Hapus = "";
        }
        var Baris = "<tr>";
        Baris += "    <td style='vertical-align:middle; width:40px;'>" + Nomor + "</td>";
        Baris += "    <td>";
        Baris += "        <input type='text' class='form-control' name='check_point[]' id='check_point'>";
        Baris += "        <input type='hidden' name='id_chk_point[]' value=''>";
        Baris += "    </td>";
        Baris += "    <td align='center' style='padding-top:13px'>" + Hapus + "</td>";
        Baris += "</tr>";
        $('.TableCheckPoint tbody').append(Baris);
    }

    /*
     *
     * //////////////////////////////////////////////////////////////////////////////////
     * PROCESSING SAVE CHECK POINT
     */
    $(document).on('click', '#SaveFormPoint', function(e) {
        e.preventDefault();
        var FormID = $(this).data('form-id');
        $.ajax({
            url: $('#' + FormID).attr('action'),
            cache: false,
            type: 'POST',
            data: $('#' + FormID).serialize(),
            dataType: 'json',
            success: function(data) {
                if (data.status == 0) {
                    $('#Notification_' + FormID).html("<div class='alert alert-danger fade in alert-dismissible'>" + data.msg + "</div>");
                    setTimeout(function() {
                        $('#Notification_' + FormID).html('')
                    }, 3000);
                }
                if (data.status == 1) {
                    AjaxNotif(data.msg);
                    $('#MyModal').modal('hide');
                    $('.Tableaktifitas tbody tr:eq(' + data.indexnya + ') td:nth-child(5) #AddChekPoint').html(data.count_point + " POINT");
                    $('.Tableaktifitas tbody tr:eq(' + data.indexnya + ') td:nth-child(5) #DeleteChekPoint').show();
                }
                if (data.status == 2) {
                    $('#Notification_' + FormID).html("<div class='alert alert-danger fade in alert-dismissible'>" + data.msg + "</div>");
                    setTimeout(function() {
                        $('#Notification_' + FormID).html('')
                    }, 3000);
                }
            }
        });
    });

    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * PROCESSING DELETE CHECK POINT
     */
    $(document).on('click', '#DeleteChekPoint', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        var index = $(this).parent().parent().index();
        var btnSave = "<button type='button' class='btn btn-primary' id='ProsesDeletePoint' data-link-id='" + link + "' data-indexnya='" + index + "'>Yes</button>";
        var btnClose = "<button type='button' class='btn btn-default' data-dismiss='modal'>Cancel</button>";

        BlurPage('MyModal');
        $('.modal-dialog').removeClass('modal-lg');
        $('.modal-dialog').addClass('modal-sm');
        $('#modal-title').html('Konfirmasi');
        $('#modal-body').html("Anda yakin ingin menghapus semua point ini ?");
        $('#modal-footer').html(btnSave + btnClose);
        $('#MyModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        $('#MyModal').modal('show');
    });
    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * PROCESSING DELETE CHECK POINT
     */
    $(document).on('click', '#ProsesDeletePoint', function(e) {
        e.preventDefault();
        var link_URL = $(this).data('link-id');
        var index = $(this).data('indexnya');
        $.ajax({
            url: link_URL,
            cache: false,
            type: 'POST',
            data: 'indexnya=' + index,
            dataType: 'json',
            success: function(data) {
                if (data.status == 1) {
                    AjaxNotif(data.pesan);
                    $('.Tableaktifitas tbody tr:eq(' + data.indexnya + ') td:nth-child(5) #AddChekPoint').html("ADD POINT");
                    $('.Tableaktifitas tbody tr:eq(' + data.indexnya + ') td:nth-child(5) #DeleteChekPoint').hide();
                    $('#MyModal').modal('hide');
                } else {
                    AjaxNotif(data.pesan);
                }
            }
        });
    });
</script>