<?php

namespace Tests\Feature;

use App\Models\AbsenceNote;
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
}
