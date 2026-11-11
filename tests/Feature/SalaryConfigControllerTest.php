<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\SalaryConfig;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalaryConfigControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['auth_users', 'users', 'salary_configs'] as $table) {
            Schema::dropIfExists($table);
        }

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
            $table->string('pin')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('bagian')->nullable();
            $table->string('kategori_gaji')->nullable();
            $table->unsignedBigInteger('salary_config_id')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('pin', 20);
            $table->string('nip', 50)->nullable();
            $table->string('nama', 100)->nullable();
            $table->enum('kategori_gaji', ['harian', 'borongan', 'bulanan']);
            $table->unsignedBigInteger('nominal')->default(0);
            $table->date('berlaku_dari');
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['pin', 'berlaku_dari']);
        });
    }

    public function test_payroll_role_can_search_and_filter_daily_wage_employees(): void
    {
        $admin = $this->createAdmin('payroll');
        $this->createEmployee('101', 'NIP-001', 'Budi Packing', 'Packing');
        $this->createEmployee('102', 'NIP-002', 'Siti Produksi', 'Produksi');
        $this->createEmployee('103', 'NIP-003', 'Budi Bulanan', 'Packing', 'bulanan');

        $response = $this->actingAs($admin, 'admin')->get(route('payroll.salary-config.index', [
            'search' => 'NIP-001',
            'bagian' => 'Packing',
        ]));

        $response->assertOk()
            ->assertSee('Atur Gaji Harian')
            ->assertSee('Budi Packing')
            ->assertDontSee('Siti Produksi')
            ->assertDontSee('Budi Bulanan');
    }

    public function test_employees_can_be_sorted_by_latest_start_date(): void
    {
        $admin = $this->createAdmin('payroll');
        $earlierEmployee = $this->createEmployee('111', 'NIP-111', 'Karyawan Lebih Lama', 'Produksi');
        $earlierEmployee->tanggal_masuk = '2024-01-15';
        $earlierEmployee->save();
        $newerEmployee = $this->createEmployee('112', 'NIP-112', 'Karyawan Terbaru', 'Produksi');
        $newerEmployee->tanggal_masuk = '2026-10-25';
        $newerEmployee->save();

        $response = $this->actingAs($admin, 'admin')->get(route('payroll.salary-config.index', [
            'sort' => 'terbaru',
        ]));

        $response->assertOk()
            ->assertSeeInOrder([
                'Karyawan Terbaru',
                '25/10/2026',
                'Karyawan Lebih Lama',
                '15/01/2024',
            ]);
    }

    public function test_selected_daily_wages_are_saved_as_new_history_and_linked_to_employees(): void
    {
        $admin = $this->createAdmin('payroll');
        $firstEmployee = $this->createEmployee('201', 'NIP-201', 'Karyawan Satu', 'Produksi');
        $secondEmployee = $this->createEmployee('202', 'NIP-202', 'Karyawan Dua', 'Packing');

        $oldConfig = SalaryConfig::create([
            'pin' => $firstEmployee->pin,
            'nip' => $firstEmployee->nip,
            'nama' => $firstEmployee->nama,
            'kategori_gaji' => 'harian',
            'nominal' => 100000,
            'berlaku_dari' => '2025-01-01',
            'created_by' => $admin->id,
        ]);
        $firstEmployee->salary_config_id = $oldConfig->id;
        $firstEmployee->save();

        $response = $this->actingAs($admin, 'admin')->post(route('payroll.salary-config.store'), [
            'employee_ids' => [$firstEmployee->id, $secondEmployee->id],
            'entries' => [
                $firstEmployee->id => [
                    'nominal' => 150000,
                    'berlaku_dari' => '2026-07-01',
                    'keterangan' => 'Penyesuaian tahunan',
                ],
                $secondEmployee->id => [
                    'nominal' => 125000,
                    'berlaku_dari' => '2026-08-01',
                    'keterangan' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('payroll.salary-config.index'));
        $this->assertDatabaseCount('salary_configs', 3);
        $this->assertDatabaseHas('salary_configs', [
            'id' => $oldConfig->id,
            'nominal' => 100000,
        ]);
        $this->assertDatabaseHas('salary_configs', [
            'pin' => $firstEmployee->pin,
            'nominal' => 150000,
            'berlaku_dari' => '2026-07-01 00:00:00',
            'keterangan' => 'Penyesuaian tahunan',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('salary_configs', [
            'pin' => $secondEmployee->pin,
            'nominal' => 125000,
            'berlaku_dari' => '2026-08-01 00:00:00',
        ]);
        $this->assertNotSame($oldConfig->id, $firstEmployee->fresh()->salary_config_id);
        $this->assertNotNull($secondEmployee->fresh()->salary_config_id);
    }

    public function test_invalid_wages_and_ineligible_employees_are_rejected(): void
    {
        $admin = $this->createAdmin('payroll');
        $employee = $this->createEmployee('301', 'NIP-301', 'Karyawan Harian', 'Packing');

        $response = $this->actingAs($admin, 'admin')->from(route('payroll.salary-config.index'))
            ->post(route('payroll.salary-config.store'), [
                'employee_ids' => [$employee->id],
                'entries' => [
                    $employee->id => [
                        'nominal' => 0,
                        'berlaku_dari' => '2026-07-01',
                    ],
                ],
            ]);

        $response->assertRedirect(route('payroll.salary-config.index'))
            ->assertSessionHasErrors("entries.{$employee->id}.nominal");
        $this->assertDatabaseCount('salary_configs', 0);

        $inactiveEmployee = $this->createEmployee('302', 'NIP-302', 'Karyawan Nonaktif', 'Packing', 'harian', false);
        $response = $this->actingAs($admin, 'admin')->post(route('payroll.salary-config.store'), [
            'employee_ids' => [$inactiveEmployee->id],
            'entries' => [
                $inactiveEmployee->id => [
                    'nominal' => 150000,
                    'berlaku_dari' => '2026-07-01',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('employee_ids');
        $this->assertDatabaseCount('salary_configs', 0);
    }

    public function test_only_authorized_payroll_roles_can_manage_daily_wages(): void
    {
        $admin = $this->createAdmin('supervisor');

        $this->actingAs($admin, 'admin')
            ->get(route('payroll.salary-config.index'))
            ->assertForbidden();
    }

    public function test_ga_can_access_daily_wage_configuration_like_hrd(): void
    {
        $admin = $this->createAdmin('ga');

        $this->actingAs($admin, 'admin')
            ->get(route('payroll.salary-config.index'))
            ->assertOk()
            ->assertSee('Atur Gaji Harian');
    }

    private function createAdmin(string $role): AuthUser
    {
        return AuthUser::create([
            'name' => 'Test Admin',
            'username' => 'test-'.$role,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createEmployee(
        string $pin,
        string $nip,
        string $name,
        string $section,
        string $salaryCategory = 'harian',
        bool $isActive = true,
    ): User {
        return User::create([
            'pin' => $pin,
            'nip' => $nip,
            'nama' => $name,
            'bagian' => $section,
            'kategori_gaji' => $salaryCategory,
            'is_active' => $isActive,
        ]);
    }
}
