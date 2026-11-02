<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeApiProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('pin')->unique();
            $table->string('nip')->nullable();
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('alamat')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jk')->nullable();
            $table->string('job_title')->nullable();
            $table->string('job_level')->nullable();
            $table->unsignedBigInteger('tl_id')->nullable();
            $table->string('bagian')->nullable();
            $table->string('departemen')->nullable();
            $table->string('kategori_gaji')->nullable();
            $table->unsignedBigInteger('salary_config_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        config(['services.attendance_api_key' => 'profile-api-test-key']);
    }

    public function test_returns_allowlisted_profile_data_for_the_requested_pin(): void
    {
        $supervisorId = DB::table('users')->insertGetId([
            'pin' => 'SUP-100',
            'nama' => 'Supervisor',
            'is_active' => true,
        ]);

        DB::table('users')->insert([
            'pin' => 'EMP-200',
            'nip' => 'NIP-200',
            'nama' => 'Employee',
            'nik' => 'NIK-200',
            'alamat' => 'Jl. Contoh 1',
            'no_hp' => '08123456789',
            'tempat_lahir' => 'Lamongan',
            'tanggal_lahir' => '1990-04-15',
            'jk' => 'L',
            'job_title' => 'Operator',
            'job_level' => 'Staff',
            'tl_id' => $supervisorId,
            'bagian' => 'Produksi',
            'departemen' => 'Operasional',
            'kategori_gaji' => 'harian',
            'salary_config_id' => 999,
            'is_active' => true,
        ]);

        $this->withHeader('X-API-KEY', 'profile-api-test-key')
            ->getJson('/api/employees/EMP-200/profile')
            ->assertOk()
            ->assertJson([
                'pin' => 'EMP-200',
                'nip' => 'NIP-200',
                'nama' => 'Employee',
                'nik' => 'NIK-200',
                'alamat' => 'Jl. Contoh 1',
                'no_hp' => '08123456789',
                'tempat_lahir' => 'Lamongan',
                'tanggal_lahir' => '1990-04-15',
                'jk' => 'L',
                'job_title' => 'Operator',
                'job_level' => 'Staff',
                'bagian' => 'Produksi',
                'departemen' => 'Operasional',
                'kategori_gaji' => 'harian',
                'tl_nama' => 'Supervisor',
            ])
            ->assertJsonMissingPath('salary_config_id');
    }

    public function test_requires_the_attendance_api_key_to_read_a_profile(): void
    {
        $this->withHeader('X-API-KEY', 'invalid-profile-api-key')
            ->getJson('/api/employees/EMP-200/profile')
            ->assertUnauthorized();
    }

    public function test_returns_not_found_for_an_inactive_pin(): void
    {
        DB::table('users')->insert([
            'pin' => 'EMP-INACTIVE',
            'nama' => 'Inactive Employee',
            'is_active' => false,
        ]);

        $this->withHeader('X-API-KEY', 'profile-api-test-key')
            ->getJson('/api/employees/EMP-INACTIVE/profile')
            ->assertNotFound();
    }
}
