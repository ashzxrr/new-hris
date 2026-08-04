<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReceiveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_receives_new_attendance_records_and_skips_duplicates(): void
    {
        $this->withHeader('X-API-KEY', env('ATTENDANCE_API_KEY'));

        AttendanceLog::create([
            'pin' => '1001',
            'datetime' => '2026-08-04 08:00:00',
            'tanggal' => '2026-08-04',
            'status' => 'IN',
            'verified' => 1,
            'machine_name' => 'Existing Machine',
        ]);

        $payload = [
            'records' => [
                [
                    'pin' => '1001',
                    'datetime' => '2026-08-04 08:00:00',
                    'tanggal' => '2026-08-04',
                    'status' => 'IN',
                    'verified' => true,
                ],
                [
                    'pin' => '1002',
                    'datetime' => '2026-08-04 09:00:00',
                    'tanggal' => '2026-08-04',
                    'status' => 'OUT',
                    'verified' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/attendance/receive', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'inserted' => 1,
                'skipped_duplicates' => 1,
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'pin' => '1002',
            'datetime' => '2026-08-04 09:00:00',
            'machine_name' => 'Mobile App',
        ]);

        $this->assertDatabaseCount('attendance_logs', 2);
    }
}
