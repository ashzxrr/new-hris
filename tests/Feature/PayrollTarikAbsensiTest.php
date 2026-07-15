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

        Schema::create('payroll_pengajuan', function ($table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->string('section')->nullable();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('jenis')->nullable();
            $table->integer('gaji_real')->default(0);
            $table->integer('komplain')->default(0);
            $table->integer('insentif')->default(0);
            $table->integer('potongan_lain')->default(0);
            $table->integer('potongan_bpjs')->default(0);
            $table->integer('total_lembur')->default(0);
            $table->integer('total_akhir')->default(0);
            $table->string('no_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->unsignedBigInteger('diajukan_by')->nullable();
            $table->timestamps();
        });

        Schema::create('karyawan_bank', function ($table) {
            $table->id();
            $table->string('nip')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('email')->nullable();
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

        Schema::create('absence_notes', function ($table) {
            $table->id();
            $table->string('pin')->nullable();
            $table->date('date');
            $table->string('code')->nullable();
            $table->text('keterangan')->nullable();
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

    public function test_generate_grand_total_uses_section_specific_borongan_imports_for_same_nip(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-5',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        User::create([
            'pin' => '900',
            'nip' => '900',
            'nama' => 'Same NIP User',
            'bagian' => 'Cabut',
            'kategori_gaji' => 'cabut',
        ]);

        $cabutImport = \App\Models\BoronganImport::create([
            'payroll_id' => $payroll->id,
            'jenis' => 'cabut',
            'status' => 'approved',
        ]);

        $mouldingImport = \App\Models\BoronganImport::create([
            'payroll_id' => $payroll->id,
            'jenis' => 'moulding',
            'status' => 'approved',
        ]);

        \App\Models\BoronganRekap::create([
            'borongan_import_id' => $cabutImport->id,
            'nip' => '900',
            'total_upah' => 50000,
            'total_gram' => 10,
            'tambahan' => 0,
            'komplain' => 0,
            'potongan_lain' => 0,
            'potongan_bpjs' => 0,
        ]);

        \App\Models\BoronganRekap::create([
            'borongan_import_id' => $mouldingImport->id,
            'nip' => '900',
            'total_upah' => 20000,
            'total_gram' => 5,
            'tambahan' => 0,
            'komplain' => 0,
            'potongan_lain' => 0,
            'potongan_bpjs' => 0,
        ]);

        \App\Models\BoronganHarian::create([
            'borongan_import_id' => $cabutImport->id,
            'nip' => '900',
            'tanggal' => '2026-07-02',
            'upah_sistem' => 50000,
        ]);

        \App\Models\BoronganHarian::create([
            'borongan_import_id' => $mouldingImport->id,
            'nip' => '900',
            'tanggal' => '2026-07-02',
            'upah_sistem' => 20000,
        ]);

        app(PayrollController::class)->generateGrandTotal(new Request(['force' => true]), $payroll->id);

        $grandTotal = PayrollGrandTotal::where('payroll_id', $payroll->id)
            ->where('nip', '900')
            ->first();

        $this->assertNotNull($grandTotal);
        $this->assertSame('cabut', $grandTotal->section);

        $detailHarian = json_decode($grandTotal->detail_harian, true);
        $this->assertSame(['2026-07-02' => 50000], $detailHarian);
        $this->assertSame(50000, (int) $grandTotal->total_akhir);
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

    public function test_generate_pengajuan_uses_gaji_pokok_for_harian_and_detail_sum_for_borongan(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-3',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        // Harian employee with authoritative gaji_pokok and lembur
        PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'pin' => '400',
            'nip' => '400',
            'nama' => 'Harian Test',
            'nominal_harian' => 100000,
            'hadir' => 15,
            'gaji_pokok' => 300000,
            'gaji_lembur' => 42187,
            'total_gaji' => 342187,
        ]);

        PayrollGrandTotal::create([
            'payroll_id' => $payroll->id,
            'nip' => '400',
            'nama' => 'Harian Test',
            'section' => 'harian',
            'detail_harian' => json_encode([150000]),
            'total_lembur' => 42187,
            'total_akhir' => 342187,
        ]);

        // Borongan (moulding) employee where gaji_real comes from sum(detail_harian)
        PayrollGrandTotal::create([
            'payroll_id' => $payroll->id,
            'nip' => '500',
            'nama' => 'Borongan Test',
            'section' => 'moulding',
            'detail_harian' => json_encode([100000]),
            'total_lembur' => 0,
            'total_akhir' => 100000,
        ]);

        app(PayrollController::class)->generatePengajuan($payroll->id);

        $harianPengajuan = \App\Models\PayrollPengajuan::where('payroll_id', $payroll->id)->where('nip', '400')->first();
        $boronganPengajuan = \App\Models\PayrollPengajuan::where('payroll_id', $payroll->id)->where('nip', '500')->first();

        $this->assertNotNull($harianPengajuan);
        $this->assertSame(300000, (int) $harianPengajuan->gaji_real);
        $this->assertSame(42187, (int) $harianPengajuan->total_lembur);

        $this->assertNotNull($boronganPengajuan);
        $this->assertSame(100000, (int) $boronganPengajuan->gaji_real);
    }

    public function test_export_grouping_uses_section_for_sheet_assignment(): void
    {
        $payroll = Payroll::create([
            'periode' => '2026-07-4',
            'tanggal_dari' => '2026-07-01',
            'tanggal_sampai' => '2026-07-15',
            'status' => 'final',
            'created_by' => 1,
        ]);

        \App\Models\PayrollPengajuan::create([
            'payroll_id' => $payroll->id,
            'nip' => '700',
            'nama' => 'Sanitasi User',
            'jenis' => 'Sanitasi',
            'section' => 'harian',
            'gaji_real' => 300000,
            'total_lembur' => 42187,
            'total_akhir' => 342187,
        ]);

        \App\Models\PayrollPengajuan::create([
            'payroll_id' => $payroll->id,
            'nip' => '701',
            'nama' => 'Operator User',
            'jenis' => 'Operator',
            'section' => 'cabut',
            'gaji_real' => 100000,
            'total_lembur' => 0,
            'total_akhir' => 100000,
        ]);

        $pengajuan = \App\Models\PayrollPengajuan::where('payroll_id', $payroll->id)->get();

        $cabutRows = [];
        $mouldingRows = [];
        $harianRows = [];
        foreach ($pengajuan as $row) {
            $section = strtolower((string) ($row->section ?? ''));
            if (in_array($section, ['cabut', 'hcr'], true)) {
                $cabutRows[] = $row;
            } elseif ($section === 'moulding') {
                $mouldingRows[] = $row;
            } elseif ($section === 'harian') {
                $harianRows[] = $row;
            } else {
                $cabutRows[] = $row;
            }
        }

        $this->assertCount(1, $harianRows);
        $this->assertSame('700', $harianRows[0]->nip);
        $this->assertCount(1, $cabutRows);
        $this->assertSame('701', $cabutRows[0]->nip);
    }
}
