<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" integrity="sha512-yVvxUQV0QESBt1SyZbNJMAwyKvFTLMyXSyBHDO4BG5t7k/Lw34tyqlSDlKIrIENIzCl+RVUNjmCPG+V/GMesRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="col-md-6">
    <form id="frm-data">
        <table class="table">
            <tr>
                <th>Nomor SPK</th>
                <th>
                    <select name="nomor_spk" class="form-control form-control-sm chosen">
                        <option value="">- Pilih No. SPK -</option>
                        <?php foreach ($list_spk as $item_spk) : ?>
                            <option value="<?= $item_spk->id_spk_penawaran ?>"><?= $item_spk->id_spk_penawaran ?></option>
                        <?php endforeach; ?>
                    </select>
                </th>
            </tr>
            <tr>
                <th>Nomor SPK (Baru)</th>
                <th>
                    <input type="text" class="form-control form-control-sm" name="nomor_spk_baru">
                </th>
            </tr>
            <tr>
                <th colspan="2" class="text-right">
                    <button type="submit" name="save" class="btn btn-sm btn-primary"><i class="fa fa-save"></i> Save</button>
                </th>
            </tr>
        </table>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    $(document).ready(function() {
        $('.chosen').chosen({
            width: '100%'
        });
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'Nomor SPK akan berubah sesuai inputan !',
            showConfirmButton: true,
            showCancelButton: true,
            allowEscapeKey: false,
            allowOutsideClick: false
        }).then((next) => {
            if (next.isConfirmed) {
                var frmdata = $('#frm-data').serialize();

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'ubah_no_spk',
                    data: frmdata,
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: result.msg,
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowEscapeKey: false,
                            allowOutsideClick: false,
                            timer: 3000
                        }).then(() => {
                            location.reload(true);
                        });
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: result.msg,
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowEscapeKey: false,
                            allowOutsideClick: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    });
</script>