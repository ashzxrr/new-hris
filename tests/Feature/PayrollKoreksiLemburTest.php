<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollKoreksiLemburTest extends TestCase
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

        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();
            $table->string('periode');
            $table->date('tanggal_dari');
            $table->date('tanggal_sampai');
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->string('pin');
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->integer('nominal_harian')->default(0);
            $table->integer('hadir')->default(0);
            $table->integer('alpha')->default(0);
            $table->integer('izin')->default(0);
            $table->integer('sakit')->default(0);
            $table->integer('setengah_hari')->default(0);
            $table->integer('lembur_menit')->default(0);
            $table->integer('gaji_pokok')->default(0);
            $table->integer('gaji_lembur')->default(0);
            $table->integer('tambahan')->default(0);
            $table->integer('potongan')->default(0);
            $table->integer('total_gaji')->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->string('pin');
            $table->date('tanggal');
            $table->time('jam_in')->nullable();
            $table->time('jam_out')->nullable();
            $table->string('status')->default('H');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->integer('lembur_menit')->nullable();
            $table->boolean('lembur_approved')->default(false);
            $table->timestamps();
        });

        Schema::create('overtime_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('pin');
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->date('tanggal');
            $table->time('jam_out')->nullable();
            $table->integer('lembur_menit')->default(0);
            $table->string('status');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('pin');
            $table->date('tanggal');
            $table->dateTime('datetime');
            $table->string('status');
        });

        Schema::create('absence_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('pin');
            $table->date('date');
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    public function test_approving_overtime_saves_correction_and_recalculates_pay(): void
    {
        $admin = AuthUser::create([
            'name' => 'Payroll Admin',
            'username' => 'payroll-'.Str::uuid(),
            'password' => 'password',
            'role' => 'admin',
        ]);

        $payroll = Payroll::create([
            'periode' => '2026-10-2',
            'tanggal_dari' => '2026-10-28',
            'tanggal_sampai' => '2026-10-30',
            'status' => 'draft',
        ]);

        $detail = PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'pin' => '1001',
            'nip' => 'NIP-1001',
            'nama' => 'Test Employee',
            'nominal_harian' => 100000,
            'gaji_pokok' => 100000,
            'total_gaji' => 100000,
        ]);

        $csrfToken = Str::random(40);
        $response = $this->actingAs($admin, 'admin')
            ->withSession(['_token' => $csrfToken])
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson(route('payroll.detail.koreksi.save', $detail->id), [
                'rows' => [[
                    'tgl' => '2026-10-29',
                    'jam_in' => '08:00',
                    'jam_out' => '18:00',
                    'status' => 'H',
                    'lembur_menit' => 60,
                    'lembur_approved' => true,
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendance_corrections', [
            'pin' => '1001',
            'tanggal' => '2026-10-29 00:00:00',
            'lembur_approved' => true,
        ]);
        $this->assertDatabaseHas('overtime_requests', [
            'pin' => '1001',
            'tanggal' => '2026-10-29 00:00:00',
            'lembur_menit' => 60,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('payroll_details', [
            'id' => $detail->id,
            'gaji_lembur' => 18750,
            'total_gaji' => 118750,
        ]);

        $this->putJson(route('payroll.detail.update', $detail->id), [
            'tambahan' => 5000,
            'potongan' => 0,
            'keterangan' => 'Penyesuaian lembur',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('payroll_details', [
            'id' => $detail->id,
            'tambahan' => 5000,
            'total_gaji' => 123750,
        ]);
    }

    public function test_unauthenticated_ajax_save_returns_json_instead_of_login_html(): void
    {
        $response = $this->post(route('payroll.detail.koreksi.save', 1), [
            'rows' => [],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }
}
