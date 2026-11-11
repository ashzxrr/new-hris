<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TurnoverControllerTest extends TestCase
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
            $table->string('bagian')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_03_015953_add_turnover_dates_to_users_table.php');
        $migration->up();
    }

    public function test_hrd_sees_selected_year_turnover_and_resign_records_without_dates(): void
    {
        $admin = $this->createAdmin('hrd');
        $this->createEmployee('EMP-001', 'Karyawan Aktif', true, '2026-03-01');
        $this->createEmployee('EMP-002', 'Resign Tahun Ini', false, '2021-01-01', '2026-10-14', 'Mengundurkan diri');
        $this->createEmployee('EMP-003', 'Resign Tahun Lalu', false, '2020-01-01', '2025-12-20', 'Pensiun');
        $this->createEmployee('EMP-004', 'Resign Tanpa Tanggal', false, '2019-01-01');
        $this->createEmployee('EMP-005', 'Resign Tanpa Alasan', false, '2022-01-01', '2026-09-10');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('turnover.index', ['tahun' => 2026]));

        $response->assertOk()
            ->assertSee('Turnover Karyawan')
            ->assertSee('Karyawan masuk · 2026')
            ->assertSee('Resign Tahun Ini')
            ->assertSee('Resign Tanpa Tanggal')
            ->assertDontSee('Resign Tahun Lalu')
            ->assertSee('Okt')
            ->assertSee('<option value="2026" selected>', false)
            ->assertSee('id="chartPergerakan"', false)
            ->assertSee('data-months=', false)
            ->assertSee('data-year="2026"', false)
            ->assertSee('turnover-', false)
            ->assertSee('Resign Tanpa Alasan')
            ->assertSee('resign belum punya alasan')
            ->assertDontSee('border-t-2 border-[#2F4156]');
    }

    public function test_pagination_links_keep_the_selected_year(): void
    {
        $admin = $this->createAdmin('hrd');

        for ($index = 1; $index <= 13; $index++) {
            $this->createEmployee(
                'EMP-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'Resign '.$index,
                false,
                '2020-01-01',
                '2026-10-14',
                'Mengundurkan diri',
            );
        }

        $this->actingAs($admin, 'admin')
            ->get(route('turnover.index', ['tahun' => 2026]))
            ->assertOk()
            ->assertSee('tahun=2026&amp;page=2', false);
    }

    public function test_hrd_can_search_and_filter_turnover_history_by_name_and_department(): void
    {
        $admin = $this->createAdmin('hrd');
        $this->createEmployee('EMP-101', 'Budi Produksi', false, '2020-01-01', '2026-10-10', 'Pensiun', 'Produksi');
        $this->createEmployee('EMP-102', 'Sari Packing', false, '2020-01-01', '2026-10-11', 'Pensiun', 'Packing');

        $this->actingAs($admin, 'admin')
            ->get(route('turnover.index', [
                'tahun' => 2026,
                'search' => 'Budi',
                'bagian' => 'Produksi',
            ]))
            ->assertOk()
            ->assertSee('Budi Produksi')
            ->assertDontSee('Sari Packing')
            ->assertSee('value="Produksi" selected', false)
            ->assertSee('name="search" type="search" value="Budi"', false);
    }

    public function test_hrd_can_update_each_selected_resigned_employee_individually(): void
    {
        $admin = $this->createAdmin('hrd');
        $firstEmployee = $this->createEmployee('EMP-201', 'Resign Pertama', false, '2020-01-01', '2026-01-10', 'Pensiun');
        $secondEmployee = $this->createEmployee('EMP-202', 'Resign Kedua', false, '2021-01-01');
        $unselectedEmployee = $this->createEmployee('EMP-203', 'Tidak Dipilih', false, '2020-01-01', '2026-02-10', 'Kesehatan');

        $this->actingAs($admin, 'admin')
            ->put(route('turnover.update'), [
                'tahun' => 2026,
                'search' => 'Resign',
                'bagian' => 'Produksi',
                'ids' => [$firstEmployee->id, $secondEmployee->id],
                'employees' => [
                    $firstEmployee->id => [
                        'tanggal_resign' => '2026-02-20',
                        'alasan_resign' => 'personal',
                    ],
                    $secondEmployee->id => [
                        'tanggal_resign' => '2026-03-10',
                        'alasan_resign' => 'other',
                        'alasan_lainnya' => 'Menerima pekerjaan di kota lain',
                    ],
                ],
            ])
            ->assertRedirect(route('turnover.index', [
                'tahun' => 2026,
                'search' => 'Resign',
                'bagian' => 'Produksi',
            ]))
            ->assertSessionHas('success', '2 data riwayat resign berhasil diperbarui.');

        $firstEmployee->refresh();
        $secondEmployee->refresh();
        $unselectedEmployee->refresh();

        $this->assertSame('2026-02-20', $firstEmployee->tanggal_resign->toDateString());
        $this->assertSame('Mengundurkan diri (keinginan pribadi)', $firstEmployee->alasan_resign);
        $this->assertSame('2026-03-10', $secondEmployee->tanggal_resign->toDateString());
        $this->assertSame('Menerima pekerjaan di kota lain', $secondEmployee->alasan_resign);
        $this->assertSame('2026-02-10', $unselectedEmployee->tanggal_resign->toDateString());
        $this->assertSame('Kesehatan', $unselectedEmployee->alasan_resign);
    }

    public function test_hrd_cannot_save_an_other_reason_without_custom_text(): void
    {
        $admin = $this->createAdmin('hrd');
        $employee = $this->createEmployee('EMP-301', 'Resign Tanpa Alasan', false, '2020-01-01');

        $this->actingAs($admin, 'admin')
            ->from(route('turnover.index', ['tahun' => 2026]))
            ->put(route('turnover.update'), [
                'tahun' => 2026,
                'ids' => [$employee->id],
                'employees' => [
                    $employee->id => [
                        'tanggal_resign' => '2026-03-10',
                        'alasan_resign' => 'other',
                        'alasan_lainnya' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('turnover.index', ['tahun' => 2026]))
            ->assertSessionHasErrors("employees.{$employee->id}.alasan_lainnya");

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'tanggal_resign' => null,
            'alasan_resign' => null,
        ]);
    }

    public function test_hrd_cannot_set_a_resign_date_before_the_employee_start_date(): void
    {
        $admin = $this->createAdmin('hrd');
        $employee = $this->createEmployee('EMP-302', 'Resign Terlalu Awal', false, '2020-05-01', '2026-03-10', 'Pensiun');

        $this->actingAs($admin, 'admin')
            ->from(route('turnover.index', ['tahun' => 2026]))
            ->put(route('turnover.update'), [
                'tahun' => 2026,
                'ids' => [$employee->id],
                'employees' => [
                    $employee->id => [
                        'tanggal_resign' => '2020-04-30',
                        'alasan_resign' => 'retirement',
                    ],
                ],
            ])
            ->assertRedirect(route('turnover.index', ['tahun' => 2026]))
            ->assertSessionHasErrors([
                "employees.{$employee->id}.tanggal_resign" => "Tanggal resign {$employee->nama} tidak boleh sebelum tanggal masuk.",
            ]);

        $employee->refresh();

        $this->assertSame('2026-03-10', $employee->tanggal_resign->toDateString());
        $this->assertSame('Pensiun', $employee->alasan_resign);
    }

    public function test_payroll_role_cannot_view_turnover(): void
    {
        $user = $this->createAdmin('payroll');

        $this->actingAs($user, 'admin')
            ->get(route('turnover.index'))
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put(route('turnover.update'))
            ->assertForbidden();
    }

    public function test_ga_can_view_and_update_turnover_with_hrd_navigation_access(): void
    {
        $user = $this->createAdmin('ga');
        $employee = $this->createEmployee('EMP-GA-001', 'Resign Belum Lengkap', false, '2020-01-01');

        $this->actingAs($user, 'admin')
            ->get(route('turnover.index', ['tahun' => 2026]))
            ->assertOk()
            ->assertSee('Turnover Karyawan')
            ->assertSee('Karyawan')
            ->assertSee('Bawahan')
            ->assertSee('Export ID Card')
            ->assertSee('Absensi')
            ->assertSee('Payroll Harian')
            ->assertSee('Atur Gaji Harian')
            ->assertSee('Export PKWT');

        $this->actingAs($user, 'admin')
            ->put(route('turnover.update'), [
                'tahun' => 2026,
                'ids' => [$employee->id],
                'employees' => [
                    $employee->id => [
                        'tanggal_resign' => '2026-03-10',
                        'alasan_resign' => 'retirement',
                    ],
                ],
            ])
            ->assertRedirect(route('turnover.index', ['tahun' => 2026]));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'tanggal_resign' => '2026-03-10 00:00:00',
            'alasan_resign' => 'Pensiun',
        ]);
    }

    private function createAdmin(string $role): AuthUser
    {
        return AuthUser::create([
            'name' => 'HR Admin',
            'username' => 'hr-'.Str::uuid(),
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function createEmployee(
        string $pin,
        string $name,
        bool $isActive,
        string $startDate,
        ?string $resignDate = null,
        ?string $reason = null,
        string $department = 'Produksi',
    ): User {
        return User::create([
            'pin' => $pin,
            'nip' => $pin,
            'nama' => $name,
            'bagian' => $department,
            'is_active' => $isActive,
            'tanggal_masuk' => $startDate,
            'tanggal_resign' => $resignDate,
            'alasan_resign' => $reason,
        ]);
    }
}
