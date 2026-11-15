<?php

use App\Support\Audit;

function write_log($module, $action, $description, $data = null, $ref_table = null, $ref_id = null, $ref_no = null)
{
    return Audit::write($module, $action, $description, $data, $ref_table, $ref_id, $ref_no);
}
