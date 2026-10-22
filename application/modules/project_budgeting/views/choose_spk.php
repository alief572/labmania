<div class="box">
    <div class="box-header">
        
    </div>

    <div class="box-body">
        <table class="table table-striped" id="table_penawaran">
            <thead>
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Marketing</th>
                    <th class="text-center">Package</th>
                    <th class="text-center">Customer</th>
                    <th class="text-center">Grand Total</th>
                    <th class="text-center">Option</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        DataTables();
    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no',
                }, {
                    data: 'tgl_quotation'
                },
                {
                    data: 'nm_marketing'
                },
                {
                    data: 'nm_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'option'
                }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true
        });
    }
</script>