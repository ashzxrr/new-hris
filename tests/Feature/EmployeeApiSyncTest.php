<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeApiSyncTest extends TestCase
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
            $table->string('jk')->nullable();
            $table->string('job_title')->nullable();
            $table->string('job_level')->nullable();
            $table->unsignedBigInteger('tl_id')->nullable();
            $table->string('bagian')->nullable();
            $table->string('departemen')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        config(['services.attendance_api_key' => 'employee-sync-test-key']);
    }

    public function test_updated_employee_and_supervisor_pin_are_returned_for_incremental_sync(): void
    {
        $oldTimestamp = Carbon::parse('2026-10-01 08:00:00');
        $supervisorId = DB::table('users')->insertGetId([
            'pin' => 'SUP-100',
            'nama' => 'Supervisor',
            'is_active' => true,
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);

        $employeeId = DB::table('users')->insertGetId([
            'pin' => 'EMP-200',
            'nama' => 'Employee Old Name',
            'tl_id' => $supervisorId,
            'is_active' => true,
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);

        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
        User::findOrFail($employeeId)->update(['nama' => 'Employee Updated']);

        $this->withHeader('X-API-KEY', 'employee-sync-test-key')
            ->getJson('/api/employees?updated_since=2026-10-30T11:59:00Z')
            ->assertOk()
            ->assertJsonFragment([
                'pin' => 'EMP-200',
                'nama' => 'Employee Updated',
                'tl_pin' => 'SUP-100',
            ]);
    }
}
