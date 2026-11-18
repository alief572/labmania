<?php

namespace Tests\Mysql;

use Tests\Feature\Master\SidebarAllScenarios;

class SidebarAllMysqlTest extends MysqlTestCase
{
    use SidebarAllScenarios;

    protected function admin(): \App\Models\User
    {
        return $this->superadmin();
    }
}
