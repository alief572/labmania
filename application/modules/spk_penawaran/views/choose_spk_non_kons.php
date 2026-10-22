<div class="box">
    <div class="box-header">
        
    </div>

    <div class="box-body">
        <table class="table table-striped" id="table_penawaran_non_kons">
            <thead>
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">ID Quotation</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">PIC Penawaran</th>
                    <th class="text-center">Penawaran</th>
                    <th class="text-center">Customer</th>
                    <th class="text-center">Grand Total</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        DataTables();
    });

    function DataTables() {
    // 1. Definisikan list kolom dalam array
    const columnList = [
        'no', 
        'id_quotation', 
        'date', 
        'pic_penawaran', 
        'penawaran', 
        'customer', 
        'grand_total', 
        'action'
    ];

    // 2. Map array di atas jadi format objek DataTables
    const columns = columnList.map(col => ({ data: col }));

    // 3. Inisialisasi DataTable
    var dataTables = $('#table_penawaran_non_kons').dataTable({
        ajax: {
            url: siteurl + active_controller + 'table_penawaran_non_kons',
            type: "POST",
            dataType: "JSON",
            data: function(d) {
                // Tambahkan parameter filter di sini kalau ada
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: "There's an error occured, please try again later !",
                });
            }
        },
        columns: columns, // Pakai variabel hasil map tadi
        responsive: true,
        processing: true,
        serverSide: true,
        stateSave: true,
        destroy: true,
        paging: true
    });
}
</script>