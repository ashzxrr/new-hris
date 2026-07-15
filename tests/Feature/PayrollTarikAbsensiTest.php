<?php

namespace Tests\Feature;

use App\Http\Controllers\PayrollController;
use App\Models\AttendanceLog;
use App\Models\AuthUser;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollGrandTotal;
use App\Models\User;
use App\Services\FingerprintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollTarikAbsensiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['auth_users','payrolls','attendance_logs','users','salary_configs','overtime_requests','payroll_details','payroll_grand_total','borongan_imports','borongan_rekap','borongan_harian','bpjs_master','attendance_corrections'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('auth_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payrolls', function ($table) {
            $table->id();
            $table->string('periode');
            $table->date('tanggal_dari');
            $table->date('tanggal_sampai');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_logs', function ($table) {
            $table->id();
            $table->string('pin');
            $table->date('tanggal');
            $table->dateTime('datetime');
            $table->string('status')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('machine_name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('nik')->nullable();
            $table->string('jk')->nullable();
            $table->string('job_title')->nullable();
            $table->string('job_level')->nullable();
            $table->string('bagian')->nullable();
            $table->string('departemen')->nullable();
            $table->string('kategori_gaji')->nullable();
            $table->unsignedBigInteger('salary_config_id')->nullable();
            $table->string('tl_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_configs', function ($table) {
            $table->id();
            $table->string('nip');
            $table->integer('nominal')->default(0);
            $table->date('berlaku_dari');
            $table->timestamps();
        });

        Schema::create('overtime_requests', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('jam_out')->nullable();
            $table->integer('lembur_menit')->default(0);
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->string('pin')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->integer('nominal_harian')->default(0);
            $table->integer('hadir')->default(0);
            $table->integer('alpha')->default(0);
            $table->integer('izin')->default(0);
            $table->integer('sakit')->default(0);
            $table->integer('setengah_hari')->default(0);
            $table->integer('lembur_menit')->default(0);
            $table->boolean('lembur_approved')->default(false);
            $table->integer('gaji_pokok')->default(0);
            $table->integer('gaji_lembur')->default(0);
            $table->integer('tambahan')->default(0);
            $table->integer('potongan')->default(0);
            $table->integer('total_gaji')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_grand_total', function ($table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('job_label')->nullable();
            $table->string('section')->nullable();
            $table->text('detail_harian')->nullable();
            $table->integer('insentif')->default(0);
            $table->integer('komplain')->default(0);
            $table->integer('potongan_lain')->default(0);
            $table->integer('potongan_bpjs')->default(0);
            $table->integer('total_lembur')->default(0);
            $table->integer('total_akhir')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('borongan_imports', function ($table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->string('jenis')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('borongan_rekap', function ($table) {
            $table->id();
            $table->unsignedBigInteger('borongan_import_id')->nullable();
            $table->string('nip')->nullable();
            $table->integer('total_upah')->default(0);
            $table->integer('tambahan')->default(0);
            $table->integer('komplain')->default(0);
            $table->integer('potongan_lain')->default(0);
            $table->integer('potongan_bpjs')->default(0);
            $table->integer('total_gram')->default(0);
            $table->timestamps();
        });

        Schema::create('borongan_harian', function ($table) {
            $table->id();
            $table->unsignedBigInteger('borongan_import_id')->nullable();
            $table->string('nip')->nullable();
            $table->string('tanggal')->nullable();
            $table->integer('upah_sistem')->default(0);
            $table->timestamps();
        });

        Schema::create('bpjs_master', function ($table) {
            $table->id();
            $table->string('nip')->nullable();
            $table->integer('nominal')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_corrections', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->date('tanggal');
            $table->string('status')->nullable();
            $table->boolean('lembur_approved')->default(false);
            $table->timestamps();
        });
    }

    public function test_generate_grand_total_uses_existing_overtime_amount_from_payroll_detail(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-1',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        $user = User::create([
            'pin' => '157',
            'nip' => '157',
            'nama' => 'Test User',
            'bagian' => 'Harian',
            'kategori_gaji' => 'harian',
        ]);

        PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'pin' => '157',
            'nip' => '157',
            'nama' => 'Test User',
            'nominal_harian' => 100000,
            'hadir' => 15,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'setengah_hari' => 0,
            'lembur_menit' => 0,
            'gaji_pokok' => 500000,
            'gaji_lembur' => 42187,
            'tambahan' => 0,
            'potongan' => 0,
            'total_gaji' => 542187,
            'keterangan' => null,
        ]);

        $controller = app(PayrollController::class);
        $response = $controller->generateGrandTotal(new Request(['force' => true]), $payroll->id);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);

        $grandTotal = PayrollGrandTotal::where('payroll_id', $payroll->id)
            ->where('nip', '157')
            ->first();

        $this->assertNotNull($grandTotal);
        $this->assertSame(42187, (int) $grandTotal->total_lembur);
    }

    public function test_generate_grand_total_includes_harian_manual_tambahan_and_potongan(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-2',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        $user = User::create([
            'pin' => '200',
            'nip' => '200',
            'nama' => 'Manual Harian',
            'bagian' => 'Harian',
            'kategori_gaji' => 'harian',
        ]);

        PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'pin' => '200',
            'nip' => '200',
            'nama' => 'Manual Harian',
            'nominal_harian' => 100000,
            'hadir' => 15,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'setengah_hari' => 0,
            'lembur_menit' => 0,
            'gaji_pokok' => 300000,
            'gaji_lembur' => 0,
            'tambahan' => 50000,
            'potongan' => 20000,
            'total_gaji' => 330000,
            'keterangan' => null,
        ]);

        $controller = app(PayrollController::class);
        $controller->generateGrandTotal(new Request(['force' => true]), $payroll->id);

        $grandTotal = PayrollGrandTotal::where('payroll_id', $payroll->id)
            ->where('nip', '200')
            ->first();

        $this->assertNotNull($grandTotal);
        $this->assertSame(50000, (int) $grandTotal->insentif);
        $this->assertSame(20000, (int) $grandTotal->potongan_lain);
        $this->assertSame(330000, (int) $grandTotal->total_akhir);

        $detailHarian = json_decode($grandTotal->detail_harian, true);
        $this->assertIsArray($detailHarian);
        $this->assertSame(330000, $grandTotal->total_akhir);
        $this->assertNotSame(array_sum($detailHarian), $grandTotal->total_akhir);
    }

    public function test_sync_all_details_uses_approved_overtime_request_for_gaji_lembur(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-2',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        $salaryConfig = \App\Models\SalaryConfig::create([
            'nip' => '300',
            'nominal' => 100000,
            'berlaku_dari' => '2026-07-01',
        ]);

        $user = User::create([
            'pin' => '300',
            'nip' => '300',
            'nama' => 'Overtime User',
            'bagian' => 'Harian',
            'kategori_gaji' => 'harian',
            'salary_config_id' => $salaryConfig->id,
        ]);

        PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'pin' => '300',
            'nip' => '300',
            'nama' => 'Overtime User',
            'nominal_harian' => 100000,
            'hadir' => 1,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'setengah_hari' => 0,
            'lembur_menit' => 0,
            'gaji_pokok' => 100000,
            'gaji_lembur' => 0,
            'tambahan' => 0,
            'potongan' => 0,
            'total_gaji' => 100000,
            'keterangan' => null,
        ]);

        \App\Models\AttendanceLog::create([
            'pin' => '300',
            'tanggal' => '2026-07-02',
            'datetime' => '2026-07-02 08:00:00',
            'status' => 'IN',
        ]);
        \App\Models\AttendanceLog::create([
            'pin' => '300',
            'tanggal' => '2026-07-02',
            'datetime' => '2026-07-02 16:00:00',
            'status' => 'OUT',
        ]);

        \App\Models\OvertimeRequest::create([
            'pin' => '300',
            'nip' => '300',
            'nama' => 'Overtime User',
            'tanggal' => '2026-07-02',
            'jam_out' => '18:00',
            'lembur_menit' => 90,
            'status' => 'approved',
            'approved_by' => 1,
            'approved_at' => now(),
            'created_by' => 1,
        ]);

        app(PayrollController::class)->syncAllDetails($payroll->id);

        $detail = PayrollDetail::where('payroll_id', $payroll->id)
            ->where('pin', '300')
            ->first();

        $this->assertNotNull($detail);
        $this->assertSame(90, (int) $detail->lembur_menit);
        $this->assertSame(28125, (int) $detail->gaji_lembur);
        $this->assertSame(128125, (int) $detail->total_gaji);
    }
}
