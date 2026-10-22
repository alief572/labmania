<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<style>
    .card-modern {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
        margin-bottom: 25px;
        overflow: visible !important;
    }
    .card-header-modern {
        padding: 18px 24px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        border-radius: 14px 14px 0 0;
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
        background: #e0f2fe;
        color: #0284c7;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .card-body-modern {
        padding: 24px;
        overflow: visible !important;
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

    /* Table Container & Dropdown Visibility */
    .table-container-modern {
        overflow: visible !important;
        position: relative;
        min-height: 180px;
        margin-bottom: 15px;
    }
    .table-modern {
        width: 100% !important;
        border-collapse: collapse !important;
        border-radius: 10px;
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
    .table-modern tbody tr {
        position: relative;
    }
    .table-modern tbody tr.chosen-active-row {
        z-index: 99999 !important;
    }
    .table-modern tbody td {
        padding: 10px 12px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        overflow: visible !important;
        position: static !important;
    }
    .table-modern tfoot th {
        background: linear-gradient(180deg, #3c8dbc 0%, #357ca5 100%) !important;
        color: #ffffff !important;
        padding: 12px 14px !important;
        font-size: 13px;
        font-weight: 700;
        border-top: 2px solid #357ca5;
    }
    .table-modern tfoot th span,
    .table-modern tfoot th.ttl_harga,
    .table-modern tfoot th.ttl_bobot,
    .table-modern tfoot th.ttl_mandays {
        color: #ffffff !important;
    }
    .chosen-container {
        position: relative !important;
        font-size: 13px !important;
    }
    .chosen-container.chosen-container-active {
        z-index: 999999 !important;
    }
    .chosen-container .chosen-drop {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        z-index: 9999999 !important;
        background: #ffffff !important;
        border: 1px solid #3c8dbc !important;
        border-radius: 0 0 8px 8px !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22) !important;
    }
    .chosen-container-single .chosen-single {
        height: 36px !important;
        line-height: 34px !important;
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        color: #334155 !important;
    }
    .chosen-container-active.chosen-with-drop .chosen-single {
        border-color: #3c8dbc !important;
        box-shadow: 0 0 0 3px rgba(60, 141, 188, 0.15) !important;
    }
    .chosen-container .chosen-results {
        max-height: 240px !important;
        margin: 4px 4px !important;
        padding: 0 !important;
    }
    .chosen-container .chosen-results li.highlighted {
        background: #3c8dbc !important;
        background-image: none !important;
        color: #ffffff !important;
        border-radius: 4px !important;
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
        position: relative;
        z-index: 1;
    }
</style>

<div class="card-modern">
    <div class="card-header-modern">
        <div class="card-title-modern">
            <span class="card-title-icon"><i class="fa fa-plus-circle"></i></span>
            <span>Tambah Master Konsultasi</span>
        </div>
    </div>
    <div class="card-body-modern">
        <ul class="tab-segmented">
            <li>
                <a href="<?php echo site_url('master_konsultasi'); ?>">
                    <i class="fa fa-table"></i> Data Konsultasi
                </a>
            </li>
            <li class="active">
                <a href="<?php echo site_url('master_konsultasi/konsultasi_new/' . $id_paket); ?>">
                    <i class="fa fa-plus"></i> Tambah Konsultasi
                </a>
            </li>
        </ul>

        <?php
        $form_id  = 'FormKonsultasi';
        echo form_open(site_url('master_konsultasi/konsultasi_new'), array('id' => $form_id));
        ?>
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-6">
                <div class="form-group">
                    <label style="font-weight: 600; color: #334155;">Nama Paket Konsultasi <span class="text-danger">*</span></label>
                    <input type="text" name="konsultasi" id="konsultasi" class="form-control form-control-modern" placeholder="Masukkan nama paket konsultasi..." required>
                </div>
            </div>
        </div>

        <div class="table-container-modern">
            <table id="my-grid" class="table table-bordered table-modern TableKonsultasi" width="100%">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">#</th>
                        <th width="35%">Aktifitas <span class="text-danger">*</span></th>
                        <th width="20%">Harga (Rp) <span class="text-danger">*</span></th>
                        <th width="12%">Bobot (%)</th>
                        <th width="12%">Mandays (Hari)</th>
                        <th width="10%" style="text-align: center;">Check Point</th>
                        <th width="6%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="listKonsultasi"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right" style="color: #ffffff !important;">TOTAL:</th>
                        <th class="text-right ttl_harga" style="color: #ffffff !important; font-weight: 700;">0</th>
                        <th class="text-center ttl_bobot" style="color: #ffffff !important; font-weight: 700;">0</th>
                        <th class="text-center ttl_mandays" style="color: #ffffff !important; font-weight: 700;">0</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="action-btn-bar">
            <div>
                <a href="<?php echo site_url('master_konsultasi'); ?>" class="btn btn-default" style="border-radius: 6px; font-weight: 600;">
                    <i class="fa fa-arrow-left"></i> Kembali ke Data
                </a>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-success" id="BarisBaru" style="border-radius: 6px; font-weight: 600;">
                    <i class="fa fa-plus"></i> Tambah Baris
                </button>
                <button type="button" class="btn btn-primary" id="SaveKonfirmasi" style="border-radius: 6px; font-weight: 600; padding: 6px 20px;">
                    <i class="fa fa-save"></i> Simpan Data
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
<!-- End main content-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
<script src="<?= base_url('assets/js/autoNumeric.js') ?>"></script>
<script type="text/javascript">
    $(document).ready(function() {
        run_select2();

        for (i = 1; i <= 1; i++) {
            AppendBaris();
        }

        $("#<?php echo $form_id; ?>").keypress(function(e) {
            if (e.which == 13 && e.target.tagName != 'TEXTAREA') {
                return false;
            }
        });

        $(document).on('chosen:showing_dropdown', 'select', function() {
            $(this).closest('tr').addClass('chosen-active-row').css('z-index', '99999');
        });
        $(document).on('chosen:hiding_dropdown', 'select', function() {
            $(this).closest('tr').removeClass('chosen-active-row').css('z-index', '');
        });

        // Append New Line
        $(document).on('click', '#BarisBaru', function(e) {
            e.preventDefault();
            AppendBaris();
        });

        // Delete New Line
        $(document).on('click', '#Batalkan', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();

            var Nomor = 1;
            $('.TableKonsultasi tbody tr').each(function() {
                $(this).find('td:nth-child(1)').html(Nomor + '.');
                Nomor++;
            });
            hitung_all();
        });

        // Proses Checking Before Saving via SweetAlert2
        $(document).on('click', '#SaveKonfirmasi', function(e) {
            e.preventDefault();

            var namaPaket = $('#konsultasi').val().trim();
            if (namaPaket === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nama Paket Kosong',
                    text: 'Silakan isi Nama Paket Konsultasi terlebih dahulu!'
                });
                $('#konsultasi').focus();
                return false;
            }

            var hasAktifitas = false;
            $('.TableKonsultasi tbody tr').each(function() {
                var akt = $(this).find('select[name="id_aktifitas[]"]').val();
                if (akt !== '' && akt !== null && akt !== undefined) {
                    hasAktifitas = true;
                }
            });

            if (!hasAktifitas) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Aktifitas Belum Dipilih',
                    text: 'Silakan pilih minimal satu aktifitas pada tabel!'
                });
                return false;
            }

            Swal.fire({
                title: 'Konfirmasi Simpan',
                text: 'Apakah data paket konsultasi sudah benar?',
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
                        title: 'Menyimpan Data...',
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
                                    window.location = siteurl + active_controller;
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
     * FUNCTION ADD NEW LINE ACTIVITY
     * //////////////////////////////////////////////////////////////////////////////////
     */
    function auto_num() {
        $('.auto_num').autoNumeric({
            decimalCharacter: '.',
            decimalPlaces: 2,
            minimumValue: '0.00',
        });
    }

    function run_select2() {
        $('.select2').chosen();

    }

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function AppendBaris() {
        var no = $('.TableKonsultasi tbody tr').length;
        var arr_akt = [];
        for (i = 1; i <= no; i++) {
            $('.id_aktifitas_' + i).each(function() {
                var val_akt = $(this).val();
                if (val_akt !== '' && val_akt !== null) {
                    arr_akt.push(val_akt);
                }
            });
        }

        var UnikNumber = "ACT-" + WaktuUnik();
        var Nomor = $('.TableKonsultasi tbody tr').length + 1;
        var Hapus = "<button type='button' class='btn btn-danger btn-xs' id='Batalkan' title='Hapus Baris' style='border-radius: 4px; padding: 4px 8px;'><i class='fa fa-trash'></i></button>";
        if (Nomor == 1) {
            Hapus = "";
        }
        var Baris = "<tr>";
        Baris += "    <td style='vertical-align:middle; text-align:center; font-weight:600; color:#64748b;'>" + Nomor + "</td>";
        Baris += "    <td style='vertical-align:middle;'>";
        Baris += "         <select class='form-control form-control-modern id_aktifitas_" + Nomor + "' name='id_aktifitas[]' id='NamaAktifitas' style='width:100%'>";
        Baris += "              <option value=''>-- Pilih Aktifitas --</option>";
        Baris += "              <?php if ($all_aktifitas->num_rows() > 0) { ?>";
        Baris += "                  <?php foreach ($all_aktifitas->result() as $d) { ?>";
        Baris += "<option value='<?php echo $d->id_aktifitas . '*_*' . str_replace(["\r", "\n"], '', $d->nm_aktifitas); ?>'><?php echo str_replace(["\r", "\n"], '', $d->nm_aktifitas); ?></option>";
        Baris += "                  <?php } ?>";
        Baris += "              <?php } ?>";
        Baris += "         </select>";
        Baris += "    </td>";
        Baris += "    <td>";
        Baris += "        <div class='input-group'>";
        Baris += "            <span class='input-group-addon' style='background:#f8fafc; font-size:12px;'>Rp</span>";
        Baris += "            <input type='text' class='form-control form-control-modern text-right auto_num' name='hrg_aktifitas[]' id='hrg_aktifitas' onchange='hitung_all()'>";
        Baris += "        </div>";
        Baris += "        <input type='hidden' class='form-control' name='nm_aktifitas[]' id='nm_aktifitas'>";
        Baris += "    </td>";
        Baris += "    <td>";
        Baris += "        <div class='input-group'>";
        Baris += "            <input type='number' class='form-control form-control-modern text-center' name='bobot[]' id='bobot' onchange='hitung_all()' min='0' step='any'>";
        Baris += "            <span class='input-group-addon' style='background:#f8fafc; font-size:12px;'>%</span>";
        Baris += "        </div>";
        Baris += "    </td>";
        Baris += "    <td>";
        Baris += "        <div class='input-group'>";
        Baris += "            <input type='number' class='form-control form-control-modern text-center' name='mandays[]' id='mandays' onchange='hitung_all()' min='0' step='any'>";
        Baris += "            <span class='input-group-addon' style='background:#f8fafc; font-size:12px;'>Hari</span>";
        Baris += "        </div>";
        Baris += "    </td>";
        Baris += "    <td class='text-center' style='vertical-align:middle;'><span class='text-muted' style='font-size:11px;'>-</span></td>";
        Baris += "    <td align='center' style='vertical-align:middle;'>" + Hapus + "</td>";
        Baris += "</tr>";
        $('.listKonsultasi').append(Baris);

        $('.id_aktifitas_' + Nomor).chosen({
            width: '100%'
        });
        auto_num();
    }

    function get_num(nilai = null) {
        if (nilai !== '' && nilai !== null) {
            nilai = nilai.split(',').join('');
            if (isNaN(nilai)) {
                nilai = 0;
            } else {
                nilai = parseFloat(nilai);
            }
        } else {
            nilai = 0;
        }

        return nilai;
    }

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function hitung_all() {
        var ttl_harga = 0;
        var ttl_bobot = 0;
        var ttl_mandays = 0;

        $('input[name="hrg_aktifitas[]"]').each(function() {
            var harga = get_num($(this).val());

            ttl_harga += harga;
        });

        $('input[name="bobot[]"]').each(function() {
            var bobot = get_num($(this).val());

            ttl_bobot += bobot;
        });

        $('input[name="mandays[]"]').each(function() {
            var mandays = get_num($(this).val());

            ttl_mandays += mandays;
        });

        $('.ttl_harga').html(number_format(ttl_harga));
        $('.ttl_bobot').html(number_format(ttl_bobot));
        $('.ttl_mandays').html(number_format(ttl_mandays));
    }

    $(document).on('change', '#NamaAktifitas', function(e) {
        e.preventDefault();
        var nm_aktifitas = $(this).parent().parent().find('td:nth-child(3) input#nm_aktifitas');
        var hrg_aktifitas = $(this).parent().parent().find('td:nth-child(3) input#hrg_aktifitas');
        var bobot = $(this).parent().parent().find('td:nth-child(4) input');
        var mandays = $(this).parent().parent().find('td:nth-child(5) input');
        var total_check = $(this).parent().parent().find('td:nth-child(6)');
        $.ajax({
            url: "<?php echo site_url('master_konsultasi/get_data_aktifitas'); ?>",
            cache: false,
            data: "id_aktifitas=" + $(this).val(),
            type: "POST",
            dataType: "json",
            success: function(data) {
                if (data.status == 1) {
                    nm_aktifitas.val(data.nm_aktifitas);
                    hrg_aktifitas.val(number_format(data.harga, 2));
                    bobot.val(data.bobot);
                    mandays.val(data.mandays);
                    total_check.html("<a href='<?php echo base_url("master_konsultasi/aktifitas_check_point"); ?>/" + data.id_aktifitas + "' class='btn btn-default btn-xs add-point' id='AddChekPoint'>" + data.total_chk + " POINT</a>");

                    hitung_all();
                }
            }
        });
    });

    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * BUTTON FOR PROCESS SHOW MODAL FORM CHECK POINT
     * //////////////////////////////////////////////////////////////////////////////////
     */
    $(document).on('click', '#AddChekPoint', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        var serialize = $(this).parent().parent().find('input').serialize();
        var index_parent = $(this).parent().parent().index();
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
    });

    /*
     * //////////////////////////////////////////////////////////////////////////////////
     * PROCESS NEW LINE & REMOVE LINE CHECK POINT
     * //////////////////////////////////////////////////////////////////////////////////
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
     * //////////////////////////////////////////////////////////////////////////////////
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
     * //////////////////////////////////////////////////////////////////////////////////
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
                // if (data.status == 1) {
                //     swal({
                //         title: 'Success !',
                //         text: data.msg,
                //         type: 'success'
                //     });
                // } else {
                //     swal({
                //         title: 'Failed !',
                //         text: data.msg,
                //         type: 'warning'
                //     });
                // }
                if (data.status == 0) {
                    $('#Notification_' + FormID).html("<div class='alert alert-danger fade in alert-dismissible'>" + data.msg + "</div>");
                    setTimeout(function() {
                        $('#Notification_' + FormID).html('')
                    }, 3000);
                }
                if (data.status == 1) {
                    AjaxNotif(data.msg);
                    $('#MyModal').modal('hide');
                    $('.TableKonsultasi tbody tr:eq(' + data.indexnya + ') td:nth-child(6) #AddChekPoint').html(data.count_point + " POINT");
                    $('.TableKonsultasi tbody tr:eq(' + data.indexnya + ') td:nth-child(6) #DeleteChekPoint').show();
                }
                if (data.status == 2) {
                    $('#Notification_' + FormID).html("<div class='alert alert-danger fade in alert-dismissible'>" + data.pesan + "</div>");
                    setTimeout(function() {
                        $('#Notification_' + FormID).html('')
                    }, 3000);
                }
            },
            error: function(result) {
                swal({
                    title: 'Error !',
                    text: 'Please try again later!',
                    type: 'error'
                });
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
</script>