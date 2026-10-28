<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthRememberLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('auth_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_remember_login_persists_a_remember_token(): void
    {
        $migration = require database_path('migrations/2026_10_28_021610_add_remember_token_to_auth_users_table.php');
        $migration->up();

        $username = 'hr-admin-'.Str::uuid();

        $user = AuthUser::create([
            'name' => 'HR Admin',
            'username' => $username,
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $csrfToken = 'remember-login-test-token';
        $response = $this->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->post(route('login'), [
                'username' => $username,
                'password' => 'password',
                'remember' => '1',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertNotEmpty($user->fresh()->getRememberToken());
    }

    public function test_login_page_has_password_visibility_and_remember_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="toggle-password"', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('hris.remembered-username', false)
            ->assertSee('Simpan login di perangkat ini');
    }
}
