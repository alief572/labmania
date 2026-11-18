<?php

namespace Tests\Mysql;

use App\Models\User;
use Tests\Feature\Sales\Phase4Scenarios;
use Tests\Feature\Sales\TopScenarios;

class TopMysqlTest extends MysqlTestCase
{
    use Phase4Scenarios;
    use TopScenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $this->seedPhase4();
    }

    protected function seedAttributes(): array
    {
        return $this->attributes();
    }

    protected function login(User $user): static
    {
        return $this->loginAs($user);
    }

    protected function admin(): User
    {
        return $this->superadmin();
    }

    protected function account(string $name): User
    {
        return User::forceCreate(['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }
}
