<!-- Main content-->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="tabs-container">
                    <ul class="nav nav-tabs">
                        <li><a href="<?php echo site_url('master_kategori'); ?>">
                                Data Kategori</a>
                        </li>
                        <li class="active"><a href="<?php echo site_url('master_kategori/kategori_new'); ?>">
                                Tambah Kategori</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <div class="panel panel-filled">
                                <div class="panel-body">
                                    <div class="row">
                                        <?php
                                        $form_id = "FormTambah";
                                        echo form_open(site_url('master_kategori/kategori_new'), array('class' => 'form-horizontal', 'id' => $form_id));
                                        ?>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Nama kategori</label>
                                                <div class="col-md-4">
                                                    <?php
                                                    echo form_input(array(
                                                        'name' => 'nm_kategori',
                                                        'class' => 'form-control',
                                                        'autocomplete' => 'off',
                                                        'placeholder' => 'Nama kategori'
                                                    ));
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-3"></label>
                                                <div class="col-md-9 pull-right">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-save"></i> Simpan Data
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $("#<?php echo $form_id; ?>").keypress(function(e) {
            //Enter key
            if (e.which == 13) {
                return false;
            }
        });
    });

    $(document).on('submit', '#FormTambah', function(e) {
        e.preventDefault()

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be saved',
            showCancelButton: true,
            showConfirmButton: true,
            allowOutsideClick: false
        }).then((next) => {
            if (next.isConfirmed) {
                var formData = $('#FormTambah').serialize();

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'save_new_kategori',
                    data: formData,
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false,
                                timer: 3000
                            }).then(() => {
                                window.location = siteurl + active_controller
                            })
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.msg,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false,
                                timer: 3000
                            })
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later !',
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowOutsideClick: false,
                            timer: 3000
                        })
                    }
                });
            }
        })
    });
</script>