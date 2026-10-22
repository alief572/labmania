<?php
$ENABLE_ADD     = has_permission('Approval_Request_Payment_Checker.Add');
$ENABLE_MANAGE  = has_permission('Approval_Request_Payment_Checker.Manage');
$ENABLE_VIEW    = has_permission('Approval_Request_Payment_Checker.View');
$ENABLE_DELETE  = has_permission('Approval_Request_Payment_Checker.Delete');

$count_transport = 0;
$count_kasbon = 0;
$count_expense = 0;
$count_periodik = 0;
$count_pembayaran_po = 0;
$count_direct_payment = 0;

foreach ($data as $item) :
    if ($item->tipe == 'kasbon' && $item->status !== '2' && is_null($item->app_checker)) {
        $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $item->no_doc))->row();

        if (!empty($get_kasbon_header)) {
            if ($get_kasbon_header->metode_pembayaran == '1') {
                $count_kasbon += 1;
            }
            if ($get_kasbon_header->metode_pembayaran == '2') {
                $count_direct_payment += 1;
            }
        }
    }

endforeach;

// Count expense dari data_expense (langsung dari kons_tr_expense_report_project_header)
$count_expense = 0;
foreach ($data_expense as $item_exp) :
    // Hitung yang ada di request_payment dan belum di-approve checker
    if (!is_null($item_exp->rp_status) && $item_exp->rp_status !== '2' && is_null($item_exp->rp_app_checker) && $item_exp->selisih < 0) {
        $count_expense += 1;
    }
endforeach;
?>
<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<?= form_open($this->uri->uri_string(), array('id' => 'frm_data', 'name' => 'frm_data', 'role' => 'form', 'class' => 'form-horizontal')); ?>
<div class="box">
    <div class="container">
        <div class="row">

            <div class="col-md-4" style="margin-top: 2vh;">
                <div class="panel panel-default">
                    <div class="panel-heading bg-yellow">Kasbon</div>
                    <div class="panel-body">
                        <h2><?= $count_kasbon ?></h2>
                    </div>
                    <div class="panel-footer w-100">
                        <button type="button" class="btn btn-sm btn-primary btn_view_req" style="width: 100%;" data-val="kasbon"><i class="fa fa-eye"></i> View</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="margin-top: 2vh;">
                <div class="panel panel-default">
                    <div class="panel-heading bg-blue">Expense</div>
                    <div class="panel-body">
                        <h2><?= $count_expense ?></h2>
                    </div>
                    <div class="panel-footer w-100">
                        <button type="button" class="btn btn-sm btn-primary btn_view_req" style="width: 100%;" data-val="expense"><i class="fa fa-eye"></i> View</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="margin-top: 2vh;">
                <div class="panel panel-default">
                    <div class="panel-heading bg-green">Direct Payment</div>
                    <div class="panel-body">
                        <h2><?= $count_direct_payment ?></h2>
                    </div>
                    <div class="panel-footer w-100">
                        <button type="button" class="btn btn-sm btn-primary btn_view_req" style="width: 100%;" data-val="direct_payment"><i class="fa fa-eye"></i> View</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 list_kasbon" style="display: none;">
                <a href="<?= base_url('approval_request_payment/export_excel_kasbon_checker/?tingkat=1') ?>" class="btn btn-sm btn-success"><i class="fa fa-files"></i> Export Excel</a>
                <h2>Request Payment Kasbon</h2>
                <table class="table table-bordered" style="width: 100%;" id="example_kasbon">
                    <thead>
                        <tr>
                            <th class="text-center">No. Kasbon</th>
                            <th class="text-center">Request By</th>
                            <th class="text-center">Tanggal Pengajuan</th>
                            <th class="text-center">Deskripsi Pengajuan</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Nilai Pengajuan</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ttl_kasbon = 0;
                        foreach ($data as $item_kasbon) :

                            $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $item_kasbon->no_doc, 'metode_pembayaran ' => '1'))->row();

                            if (!empty($get_kasbon_header)) {
                                $tipe = '';
                                $link_view = '';
                                if (!empty($get_kasbon_header)) {
                                    if ($get_kasbon_header->tipe == '1') {
                                        $tipe = 'Kasbon Subcont';
                                    }
                                    if ($get_kasbon_header->tipe == '2') {
                                        $tipe = 'Kasbon Akomodasi';
                                    }
                                    if ($get_kasbon_header->tipe == '3') {
                                        $tipe = 'Kasbon Others';
                                    }
                                    if ($get_kasbon_header->tipe == '4') {
                                        $tipe = 'Kasbon Lab';
                                    }
                                    if ($get_kasbon_header->tipe == '5') {
                                        $tipe = 'Kasbon Subcont Tenaga Ahli';
                                    }
                                    if ($get_kasbon_header->tipe == '6') {
                                        $tipe = 'Kasbon Subcont Perusahaan';
                                    }



                                    if ($get_kasbon_header->tipe == '1') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '2') {
                                        $link_view = base_url('kasbon_project/view_kasbon_akomodasi/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '3') {
                                        $link_view = base_url('kasbon_project/view_kasbon_others/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '4') {
                                        $link_view = base_url('kasbon_project/view_kasbon_lab/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '5') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont_tenaga_ahli/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '6') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont_perusahaan/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                }

                                echo '<tr>';
                                echo '<td>' . $item_kasbon->no_doc . '</td>';
                                echo '<td>' . $item_kasbon->nama . '</td>';
                                echo '<td>' . $item_kasbon->tgl_doc . '</td>';
                                echo '<td>' . $item_kasbon->keperluan . '</td>';
                                echo '<td>' . $tipe . '</td>';
                                echo '<td class="text-right">' . number_format($item_kasbon->jumlah) . '</td>';
                                echo '<td>';
                                if ($ENABLE_MANAGE) :
                                    if (($item_kasbon->status !== '2' && is_null($item_kasbon->app_checker))) :
                                        echo '<a href="' . base_url($this->uri->segment(1) . '/approval_payment_checker/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc))) . '" class="btn btn-primary btn-sm">';
                                        echo '<i class="fa fa-check-square-o"></i>';
                                        echo ' Approve';
                                        echo '</a>';
                                    endif;

                                    echo ' <a href="' . base_url('approval_request_payment/print_kasbon/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc))) . '" class="btn btn-sm btn-info" title="Print PDF">';
                                    echo '<i class="fa fa-print"></i>';
                                    echo '</a>';


                                    echo ' <a href="' . $link_view . '" class="btn btn-sm btn-info" title="View Kasbon" target="_blank"><i class="fa fa-eye"></i></a>';
                                endif;
                                echo '</td>';
                                echo '</tr>';

                                $ttl_kasbon += $item_kasbon->jumlah;
                            }
                        endforeach;
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Grand Total</th>
                            <th class="text-right"><?= number_format($ttl_kasbon) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-12 list_expense" style="display: none;">
                <a href="<?= base_url('approval_request_payment/export_excel_expense_checker/?tingkat=1') ?>" class="btn btn-sm btn-success"><i class="fa fa-files"></i> Export Excel</a>
                <h2>Request Payment Expense Report</h2>
                <table class="table table-bordered" style="width: 100%;" id="example_expense">
                    <thead>
                        <tr>
                            <th class="text-center">No. Expense</th>
                            <th class="text-center">Request By</th>
                            <th class="text-center">Tanggal Pengajuan</th>
                            <th class="text-center">Deskripsi Pengajuan</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Nilai Pengajuan</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ttl_expense = 0;
                        foreach ($data_expense as $item_expense) :
                            $tipe = 'Expense';
                            $deskripsi = !empty($item_expense->deskripsi) ? $item_expense->deskripsi : '-';

                            echo '<tr>';
                            echo '<td>' . $item_expense->id . '</td>';
                            echo '<td>' . $item_expense->nama . '</td>';
                            echo '<td>' . date('Y-m-d', strtotime($item_expense->created_date)) . '</td>';
                            echo '<td>' . $deskripsi . '</td>';
                            echo '<td>' . $tipe . '</td>';
                            echo '<td class="text-right">' . number_format($item_expense->total_expense_report) . '</td>';
                            echo '<td>';
                            if ($ENABLE_MANAGE) :
                                // Tombol approve hanya muncul jika ada record di request_payment dan belum di-approve
                                if (!is_null($item_expense->rp_status) && $item_expense->rp_status !== '2' && is_null($item_expense->rp_app_checker) && $item_expense->selisih < 0) :
                                    echo '<a href="' . base_url($this->uri->segment(1) . '/approval_payment_checker/' . urlencode(str_replace('/', '|', $item_expense->id))) . '" class="btn btn-primary btn-sm">';
                                    echo '<i class="fa fa-check-square-o"></i>';
                                    echo ' Approve';
                                    echo '</a>';
                                endif;

                                echo ' <a href="' . base_url('approval_request_payment/print_expense/' . urlencode(str_replace('/', '|', $item_expense->id))) . '" class="btn btn-sm btn-info" title="Print PDF">';
                                echo '<i class="fa fa-print"></i>';
                                echo '</a>';

                                if (!empty($item_expense->id_header)) :
                                    echo ' <a href="' . base_url('expense_report_project/view_expense_subcont/' . urlencode(str_replace('/', '|', $item_expense->id_header))) . '" class="btn btn-sm btn-info" title="View Expense" target="_blank"><i class="fa fa-eye"></i></a>';
                                endif;
                            endif;
                            echo '</td>';
                            echo '</tr>';

                            $ttl_expense += $item_expense->total_expense_report;
                        endforeach;
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Grand Total</th>
                            <th class="text-right"><?= number_format($ttl_expense) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-12 list_direct_payment" style="display: none;">
                <a href="<?= base_url('approval_request_payment/export_excel_direct_payment_checker/?tingkat=1') ?>" class="btn btn-sm btn-success"><i class="fa fa-files"></i> Export Excel</a>
                <h2>Request Direct Payment</h2>
                <table class="table table-bordered" style="width: 100%;" id="example_direct_payment">
                    <thead>
                        <tr>
                            <th class="text-center">No. Direct Payment</th>
                            <th class="text-center">Request By</th>
                            <th class="text-center">Tanggal Pengajuan</th>
                            <th class="text-center">Deskripsi Pengajuan</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Nilai Pengajuan</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ttl_kasbon = 0;
                        foreach ($data as $item_kasbon) :

                            $get_kasbon_header = $this->db->get_where('kons_tr_kasbon_project_header', array('id' => $item_kasbon->no_doc, 'metode_pembayaran =' => '2'))->row();

                            if (!empty($get_kasbon_header)) {
                                $tipe = '';
                                $link_view = '';
                                if (!empty($get_kasbon_header)) {
                                    if ($get_kasbon_header->tipe == '1') {
                                        $tipe = 'Direct Payment Subcont';
                                    }
                                    if ($get_kasbon_header->tipe == '2') {
                                        $tipe = 'Direct Payment Akomodasi';
                                    }
                                    if ($get_kasbon_header->tipe == '3') {
                                        $tipe = 'Direct Payment Others';
                                    }
                                    if ($get_kasbon_header->tipe == '4') {
                                        $tipe = 'Direct Payment Lab';
                                    }
                                    if ($get_kasbon_header->tipe == '5') {
                                        $tipe = 'Direct Payment Subcont Tenaga Ahli';
                                    }
                                    if ($get_kasbon_header->tipe == '6') {
                                        $tipe = 'Direct Payment Subcont Perusahaan';
                                    }



                                    if ($get_kasbon_header->tipe == '1') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '2') {
                                        $link_view = base_url('kasbon_project/view_kasbon_akomodasi/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '3') {
                                        $link_view = base_url('kasbon_project/view_kasbon_others/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '4') {
                                        $link_view = base_url('kasbon_project/view_kasbon_lab/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '5') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont_tenaga_ahli/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                    if ($get_kasbon_header->tipe == '6') {
                                        $link_view = base_url('kasbon_project/view_kasbon_subcont_perusahaan/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc)));
                                    }
                                }

                                echo '<tr>';
                                echo '<td>' . $item_kasbon->no_doc . '</td>';
                                echo '<td>' . $item_kasbon->nama . '</td>';
                                echo '<td>' . $item_kasbon->tgl_doc . '</td>';
                                echo '<td>' . $item_kasbon->keperluan . '</td>';
                                echo '<td>' . $tipe . '</td>';
                                echo '<td class="text-right">' . number_format($item_kasbon->jumlah) . '</td>';
                                echo '<td>';
                                if ($ENABLE_MANAGE) :

                                    if (($item_kasbon->status !== '2' && is_null($item_kasbon->app_checker))) :
                                        echo '<a href="' . base_url($this->uri->segment(1) . '/approval_payment_checker/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc))) . '" class="btn btn-primary btn-sm">';
                                        echo '<i class="fa fa-check-square-o"></i>';
                                        echo ' Approve';
                                        echo '</a>';
                                    endif;

                                    echo ' <a href="' . base_url('approval_request_payment/print_direct_payment/' . urlencode(str_replace('/', '|', $item_kasbon->no_doc))) . '" class="btn btn-sm btn-info" title="Print PDF">';
                                    echo '<i class="fa fa-print"></i>';
                                    echo '</a>';


                                    echo ' <a href="' . $link_view . '" class="btn btn-sm btn-info" title="View Direct Payment" target="_blank"><i class="fa fa-eye"></i></a>';
                                endif;
                                echo '</td>';
                                echo '</tr>';

                                $ttl_kasbon += $item_kasbon->jumlah;
                            }
                        endforeach;
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Grand Total</th>
                            <th class="text-right"><?= number_format($ttl_kasbon) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- /.box-body -->
</div>
<?= form_close() ?>

<div class="modal modal-default fade" id="modal_view_receive_invoice" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title title_modal" id="myModalLabel">View Receive Invoice</h4>
            </div>
            <div class="modal-body" id="ModalViewSPPLM">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Tutup</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
<script type="text/javascript">
    $('#example_kasbon').dataTable({
        order: [
            [6, 'desc']
        ]
    });
    $('#example_direct_payment').dataTable({
        order: [
            [6, 'desc']
        ]
    });
    $('#example_expense').dataTable({
        order: [
            [6, 'desc']
        ]
    });

    function trshowall() {
        $(".trows").removeClass("hidden");
    }

    function trshow(id) {
        $(".trows").addClass("hidden");
        $(".rowshow" + id).removeClass("hidden");
    }
    var url_save = siteurl + 'request_payment/save_approval/';
    //Save

    $(document).on("click", ".btn_view_req", function() {
        var val = $(this).data('val');
        // alert(val);

        var all_lists = [
            ".list_transportasi",
            ".list_kasbon",
            ".list_expense",
            ".list_periodik",
            ".list_pembayaran_po",
            ".list_direct_payment"
        ];

        $.each(all_lists, function(index, item) {
            if (item !== ".list_" + val) {
                $(item).hide();
            }
        });

        $(".list_" + val).toggle();
    });

    $(document).on('click', '.view_receive_invoice', function() {
        var id_invoice = $(this).data('id_invoice');

        $.ajax({
            type: "POST",
            url: siteurl + active_controller + "view_receive_invoice",
            data: {
                "id_invoice": id_invoice
            },
            cache: false,
            success: function(result) {
                $('#ModalViewSPPLM').html(result);
                $('#modal_view_receive_invoice').modal('show');
            },
            error: function(result) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please try again later!',
                    icon: 'error'
                });
            }
        });
    });

    $('#frm_data').on('submit', function(e) {
        e.preventDefault();
        var errors = "";
        if (errors == "") {
            Swal.fire({
                    title: "Anda Yakin?",
                    text: "Data Akan Di Setujui!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Setujui!",
                    cancelButtonText: "Tidak!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        var formdata = new FormData($('#frm_data')[0]);
                        $.ajax({
                            url: url_save,
                            dataType: "json",
                            type: 'POST',
                            data: formdata,
                            processData: false,
                            contentType: false,
                            success: function(msg) {
                                if (msg['save'] == '1') {
                                    Swal.fire({
                                        title: "Sukses!",
                                        text: "Data Berhasil Di Setujui",
                                        icon: "success",
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    window.location.href = window.location.href;
                                } else {
                                    Swal.fire({
                                        title: "Gagal!",
                                        text: "Data Gagal Di Setujui",
                                        icon: "error",
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                };
                                console.log(msg);
                            },
                            error: function(msg) {
                                Swal.fire({
                                    title: "Gagal!",
                                    text: "Ajax Data Gagal Di Proses",
                                    icon: "error",
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                console.log(msg);
                            }
                        });
                    }
                });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errors
            });
            return false;
        }
    });
    $("#btnxls").click(function() {
        $("#mytabledata").table2excel({
            exclude: ".exclass",
            name: "Request Payment Approval",
            filename: "RequestPaymentApproval.xls", // do include extension
            preserveColors: false // set to true if you want background colors and font colors preserved
        });
    });
</script>