<?php

namespace Tests\Feature;

use App\Models\AbsenceNote;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AbsensiPublikControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('bagian')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('job_title')->nullable();
            $table->string('job_level')->nullable();
        });

        Schema::create('attendance_logs', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->date('tanggal');
            $table->dateTime('datetime');
            $table->string('status')->nullable();
        });

        Schema::create('absence_notes', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->date('date');
            $table->string('code')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_uses_absence_note_code_in_public_attendance_status(): void
    {
        User::create([
            'pin' => '1001',
            'nip' => '1001',
            'nama' => 'Budi Santoso',
            'bagian' => 'Produksi',
            'is_active' => 1,
            'job_title' => 'Operator',
            'job_level' => 'Staff',
        ]);

        AbsenceNote::create([
            'pin' => '1001',
            'date' => '2026-08-03',
            'code' => 'I',
            'note' => 'Keperluan keluarga',
        ]);

        $response = $this->postJson(route('absensi.publik.cari'), [
            'keyword' => 'Budi',
            'tanggal_dari' => '2026-08-01',
            'tanggal_sampai' => '2026-08-05',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('I (Izin)', $response->json('html'));
        $this->assertStringContainsString('Keperluan keluarga', $response->json('html'));
    }

    public function test_it_keeps_the_next_calendar_date_visible_after_an_overnight_shift(): void
    {
        User::create([
            'pin' => '1002',
            'nip' => '1002',
            'nama' => 'Asep Security',
            'bagian' => 'Security',
            'is_active' => 1,
            'job_title' => 'Security',
            'job_level' => 'Staff',
        ]);

        AttendanceLog::create([
            'pin' => '1002',
            'tanggal' => '2026-10-14',
            'datetime' => '2026-10-14 22:00:00',
            'status' => 'IN',
        ]);
        AttendanceLog::create([
            'pin' => '1002',
            'tanggal' => '2026-10-15',
            'datetime' => '2026-10-15 07:00:00',
            'status' => 'OUT',
        ]);

        $response = $this->postJson(route('absensi.publik.cari'), [
            'keyword' => 'Asep',
            'tanggal_dari' => '2026-10-15',
            'tanggal_sampai' => '2026-10-15',
        ]);

        $html = $response->json('html');

        $this->assertStringContainsString('Kamis, 15/10/2026', $html);
        $this->assertStringContainsString('Tidak Ada', $html);
        $this->assertStringNotContainsString('07:00', $html);
    }

    public function test_it_pairs_night_and_afternoon_shifts_by_timestamp(): void
    {
        User::create([
            'pin' => '1003',
            'nip' => '1003',
            'nama' => 'Bagus Setyono',
            'bagian' => 'Security',
            'is_active' => 1,
            'job_title' => 'Security',
            'job_level' => 'Staff',
        ]);

        foreach ([
            ['2026-10-11 22:59:00', '2026-10-11', 'IN'],
            ['2026-10-12 07:00:41', '2026-10-12', 'OUT'],
            ['2026-10-12 23:00:05', '2026-10-13', 'IN'],
            ['2026-10-13 07:02:24', '2026-10-13', 'OUT'],
            ['2026-10-13 14:52:14', '2026-10-13', 'IN'],
            ['2026-10-13 23:00:05', '2026-10-13', 'OUT'],
        ] as [$datetime, $date, $status]) {
            AttendanceLog::create([
                'pin' => '1003',
                'datetime' => $datetime,
                'tanggal' => $date,
                'status' => $status,
            ]);
        }

        $response = $this->postJson(route('absensi.publik.cari'), [
            'keyword' => 'Bagus',
            'tanggal_dari' => '2026-10-11',
            'tanggal_sampai' => '2026-10-13',
        ]);

        $html = $response->json('html');

        $this->assertMatchesRegularExpression('/11\/10\/2026.*?22:59.*?07:00/s', $html);
        $this->assertMatchesRegularExpression('/12\/10\/2026.*?23:00.*?07:02/s', $html);
        $this->assertMatchesRegularExpression('/13\/10\/2026.*?14:52.*?23:00/s', $html);
    }
}
