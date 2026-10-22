<form action="#" method="POST" id="form_proses_bro">
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title"><?= $title; ?></h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class='form-group row'>
                <label class='label-control col-sm-2' readonly><b>Divisions Code <span class='text-red'>*</span></b> </label>
                <div class='col-sm-4'>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-file"></i></span>
                        <?php
                        echo form_input(array('readonly' => 'readonly', 'id' => 'id', 'name' => 'id', 'class' => 'form-control input-sm', 'autocomplete' => 'off', 'placeholder' => 'Departement Code'), $row[0]->id);
                        ?>
                    </div>

                </div>
            </div>
            <div class='form-group row'>
                <label class='label-control col-sm-2'><b>Companies Name<span class='text-red'>*</span></b></label>
                <div class='col-sm-4'>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <?php
                        $data_companies[0]    = 'Select An Option';
                        echo form_dropdown('company_id', $data_companies, $row[0]->company_id, array('id' => 'company_id', 'class' => 'form-control input-sm'));
                        ?>
                    </div>
                </div>
            </div>
            <div class='form-group row'>
                <label class='label-control col-sm-2'><b>Divisions Name<span class='text-red'>*</span></b></label>
                <div class='col-sm-4'>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-file"></i></span>
                        <?php
                        echo form_input(array('id' => 'name', 'name' => 'name', 'class' => 'form-control input-sm', 'autocomplete' => 'off', 'placeholder' => 'Departement Name'), $row[0]->name);
                        ?>
                    </div>

                </div>
            </div>

        </div>
        <div class='box-footer'>
            <a href="<?= base_url('divisions') ?>" class="btn btn-sm btn-danger">
                <i class="fa fa-arrow-left"></i> back
            </a>
        </div>
        <!-- /.box-body -->
    </div>
    <!-- /.box -->
</form>
<script>
    $(document).ready(function() {
        $('input').attr('readonly', true);
        $('select').attr('readonly', true);
        $('textarea').attr('readonly', true);
        $('input').attr('disabled', true);
    });
</script>