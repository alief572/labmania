<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasswordLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! app()->environment('testing') || DB::getDriverName() !== 'sqlite' || DB::getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Fixture hanya SQLite :memory:.');
        }
        $this->withoutVite();
        DB::unprepared(file_get_contents(__DIR__.'/../fixtures/foundation.sql'));
    }

    private function account(?string $secret = null): User
    {
        return User::forceCreate(['id_user' => 73, 'username' => 'legacy', 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'ga_secret' => $secret, 'st_aktif' => 1, 'deleted' => 0]);
    }

    private function login(): void
    {
        $this->post('/login', ['username' => 'legacy', 'password' => 'known-password'])->assertRedirect('/');
    }

    public function test_password_login_preserves_credentials_rotates_session_and_clears_pending_enrollment(): void
    {
        $user = $this->account('encrypted:archived-value');
        $before = $user->only(['id_user', 'password', 'ga_secret']);
        $this->withSession(['auth.pending_id' => 999, 'auth.setup_secret' => 'obsolete']);
        $oldId = session()->getId();
        $this->login();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNull(session('auth.pending_id'));
        $this->assertNull(session('auth.setup_secret'));
        $this->assertSame($before, $user->fresh()->only(array_keys($before)));
        $this->assertNotNull($user->fresh()->login_terakhir);
        $this->assertDatabaseCount('logs', 0);
        $this->get('/permissions')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNull(session('auth.factor'));
    }

    public function test_password_login_works_in_production_without_uat_bootstrap(): void
    {
        $user = $this->account();
        config(['labmania.local_uat_enabled' => false]);
        app()['env'] = 'production';
        try {
            $this->withSession(['_token' => 'csrf-test'])->post('/login', ['username' => 'legacy', 'password' => 'known-password', '_token' => 'csrf-test'])->assertRedirect('/');
            $this->assertAuthenticatedAs($user);
            $this->artisan('labmania:uat')->assertFailed();
        } finally {
            app()['env'] = 'testing';
        }
    }

    public function test_challenge_enrollment_and_reset_endpoints_are_retired(): void
    {
        $user = $this->account('original-secret');
        $this->login();
        $this->get('/two-factor')->assertNotFound();
        $this->post('/two-factor', ['code' => '123456'])->assertNotFound();
        $this->post('/permissions/users/73/enroll-token')->assertNotFound();
        $this->post('/master/user/73/reset-2fa')->assertNotFound();
        $this->assertSame('original-secret', $user->fresh()->ga_secret);
        $this->assertDatabaseCount('logs', 0);
    }

    public function test_changed_password_invalidates_previous_session(): void
    {
        $user = $this->account();
        $this->login();
        $user->forceFill(['password' => password_hash('replacement', PASSWORD_BCRYPT, ['cost' => 4])])->save();
        Auth::forgetUser();
        $this->get('/permissions')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_legacy_totp_verified_session_is_rejected(): void
    {
        $user = $this->account('legacy-secret');
        $this->actingAs($user)->withSession(['auth.verified_id' => 73, 'auth.factor' => hash('sha256', $user->ga_secret)])->get('/permissions')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_disabled_account_cannot_reuse_password_session(): void
    {
        $user = $this->account();
        $this->login();
        $user->forceFill(['st_aktif' => 0])->save();
        Auth::forgetUser();
        $this->get('/permissions')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_write_failure_does_not_authenticate(): void
    {
        $user = $this->account();
        DB::unprepared("CREATE TRIGGER reject_login BEFORE UPDATE ON users BEGIN SELECT RAISE(ABORT, 'synthetic failure'); END;");
        $this->post('/login', ['username' => 'legacy', 'password' => 'known-password'])->assertStatus(500);
        $this->assertGuest();
        $this->assertNull($user->fresh()->login_terakhir);
        $this->assertNull(session('auth.factor'));
    }

    public function test_pair_and_account_rate_limits_survive_password_only_login(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'missing', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->post('/login', ['username' => 'missing', 'password' => 'wrong'])->assertStatus(429);
        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.($i + 1)])->post('/login', ['username' => 'victim', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.0.1'])->post('/login', ['username' => 'victim', 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }
}
