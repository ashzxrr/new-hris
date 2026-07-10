<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\AuthUser;
use App\Models\Payroll;
use App\Services\FingerprintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTarikAbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tars_absensi_and_counts_inserted_and_skipped_records(): void
    {
        $admin = AuthUser::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => 'secret',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $payroll = Payroll::create([
            'periode' => '2026-07-1',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $service = new class extends FingerprintService {
            public function getAttendanceRange(string $tanggalDari, string $tanggalSampai, array $users = []): array
            {
                return [
                    [
                        'pin' => '100',
                        'datetime' => '2026-07-09 08:00:00',
                        'tanggal' => '2026-07-09',
                        'status' => 'IN',
                        'verified' => '1',
                        'machine_name' => 'Machine 1',
                    ],
                    [
                        'pin' => '100',
                        'datetime' => '2026-07-09 08:00:00',
                        'tanggal' => '2026-07-09',
                        'status' => 'IN',
                        'verified' => '1',
                        'machine_name' => 'Machine 1',
                    ],
                    [
                        'pin' => '200',
                        'datetime' => '2026-07-09 17:00:00',
                        'tanggal' => '2026-07-09',
                        'status' => 'OUT',
                        'verified' => '1',
                        'machine_name' => 'Machine 1',
                    ],
                ];
            }
        };

        $this->app->instance(FingerprintService::class, $service);

        $this->actingAs($admin, 'admin')
            ->postJson('/payroll/' . $payroll->id . '/tarik-absensi', ['tanggal' => '2026-07-09'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'inserted' => 2,
                'skipped' => 1,
            ]);

        $this->assertDatabaseHas('attendance_logs', ['pin' => '100', 'datetime' => '2026-07-09 08:00:00']);
        $this->assertDatabaseHas('attendance_logs', ['pin' => '200', 'datetime' => '2026-07-09 17:00:00']);
        $this->assertEquals(2, AttendanceLog::count());
    }
}
