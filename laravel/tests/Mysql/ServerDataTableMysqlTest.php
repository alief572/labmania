<?php

namespace Tests\Mysql;

use Tests\Support\ServerDataTableScenarios;

class ServerDataTableMysqlTest extends MysqlTestCase
{
    use ServerDataTableScenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $this->loginAs($this->superadmin());
    }
}
