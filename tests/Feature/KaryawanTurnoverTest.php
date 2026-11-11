<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class KaryawanTurnoverTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('pin')->unique();
            $table->string('nip')->nullable();
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('jk')->nullable();
            $table->string('job_title')->nullable();
            $table->string('job_level')->nullable();
            $table->string('bagian')->nullable();
            $table->string('departemen')->nullable();
            $table->string('kategori_gaji')->nullable();
            $table->unsignedBigInteger('tl_id')->nullable();
            $table->string('alamat')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_03_015953_add_turnover_dates_to_users_table.php');
        $migration->up();
    }

    public function test_new_employee_records_store_their_start_date(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('karyawan.store'), [
            'pin' => 'EMP-100',
            'nip' => 'NIP-100',
            'nama' => 'Employee One',
            'jk' => 'L',
            'tanggal_masuk' => '2024-03-10',
        ]);

        $response->assertRedirect(route('karyawan.sync'));
        $this->assertDatabaseHas('users', [
            'pin' => 'EMP-100',
            'tanggal_masuk' => '2024-03-10 00:00:00',
            'tanggal_resign' => null,
        ]);
    }

    public function test_new_employee_cannot_be_saved_without_a_start_date(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('karyawan.store'), [
            'pin' => 'EMP-101',
            'nip' => 'NIP-101',
            'nama' => 'Employee Missing Date',
            'jk' => 'L',
        ]);

        $response->assertSessionHasErrors('tanggal_masuk');
        $this->assertDatabaseMissing('users', ['pin' => 'EMP-101']);
    }

    public function test_bulk_resignation_stores_exit_date_and_reason(): void
    {
        $admin = $this->createAdmin();
        $employee = User::create([
            'pin' => 'EMP-200',
            'nip' => 'NIP-200',
            'nama' => 'Employee Two',
            'jk' => 'P',
            'is_active' => true,
            'tanggal_masuk' => '2021-06-01',
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('karyawan.resignBulk'), [
            'ids' => [$employee->id],
            'tanggal_resign' => '2026-10-31',
            'alasan_resign' => 'Mengundurkan diri',
        ]);

        $response->assertRedirect(route('karyawan.index'));
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'is_active' => false,
            'tanggal_masuk' => '2021-06-01 00:00:00',
            'tanggal_resign' => '2026-10-31 00:00:00',
            'alasan_resign' => 'Mengundurkan diri',
        ]);
    }

    public function test_resignation_before_start_date_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $employee = User::create([
            'pin' => 'EMP-201',
            'nama' => 'Employee Invalid Dates',
            'jk' => 'L',
            'is_active' => true,
            'tanggal_masuk' => '2021-06-01',
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('karyawan.resignBulk'), [
            'ids' => [$employee->id],
            'tanggal_resign' => '2021-05-31',
            'alasan_resign' => 'Pindah kerja',
        ]);

        $response->assertSessionHasErrors('tanggal_resign');
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'is_active' => true,
            'tanggal_resign' => null,
        ]);
    }

    public function test_resigned_employee_dates_can_be_backfilled_from_bulk_edit(): void
    {
        $admin = $this->createAdmin();
        $employee = User::create([
            'pin' => 'EMP-300',
            'nip' => 'NIP-300',
            'nama' => 'Employee Three',
            'jk' => 'L',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('karyawan.updateBulk'), [
            'karyawan' => [
                $employee->id => [
                    'id' => $employee->id,
                    'nama' => 'Employee Three Updated',
                    'nip' => $employee->nip,
                    'nik' => '',
                    'jk' => 'L',
                    'job_title' => '',
                    'job_level' => '',
                    'bagian' => '',
                    'departemen' => '',
                    'kategori_gaji' => '',
                    'tl_id' => '',
                    'tanggal_masuk' => '2020-05-01',
                    'tanggal_resign' => '2025-12-15',
                    'alasan_resign' => 'Pensiun',
                ],
            ],
        ]);

        $response->assertRedirect(route('karyawan.index'));
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'nama' => 'Employee Three Updated',
            'is_active' => false,
            'tanggal_masuk' => '2020-05-01 00:00:00',
            'tanggal_resign' => '2025-12-15 00:00:00',
            'alasan_resign' => 'Pensiun',
        ]);
    }

    public function test_legacy_deactivate_route_does_not_create_an_untracked_resignation(): void
    {
        $admin = $this->createAdmin();
        $employee = User::create([
            'pin' => 'EMP-400',
            'nip' => 'NIP-400',
            'nama' => 'Employee Four',
            'jk' => 'P',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->delete(route('karyawan.destroy', $employee->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'is_active' => true,
            'tanggal_resign' => null,
        ]);
    }

    private function createAdmin(): AuthUser
    {
        return AuthUser::create([
            'name' => 'HR Admin',
            'username' => 'hr-'.Str::uuid(),
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
