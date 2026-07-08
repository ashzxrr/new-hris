<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\BoronganController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/sync', [DashboardController::class, 'sync'])->name('dashboard.sync');

    Route::get('/fingerprint-test', function () {
        $fp = app(\App\Services\FingerprintService::class);

        $connections = $fp->testConnections();
        $users = $fp->getUsers();

        return response()->json([
            'connections' => $connections,
            'total_users' => count($users),
            'sample_users' => array_slice($users, 0, 5, true),
        ]);
    })->name('fingerprint.test');

    Route::middleware(['auth.admin', 'role:admin,hrd'])->group(function () {
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('/karyawan/sync', [KaryawanController::class, 'syncPreview'])->name('karyawan.sync');
        Route::post('/karyawan/store', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::get('/karyawan/edit-bulk', [KaryawanController::class, 'editBulk'])->name('karyawan.editBulk');
        Route::put('/karyawan/update-bulk', [KaryawanController::class, 'updateBulk'])->name('karyawan.updateBulk');
        Route::put('/karyawan/resign-bulk', [KaryawanController::class, 'resignBulk'])->name('karyawan.resignBulk');
        Route::delete('/karyawan/destroy-permanent', [KaryawanController::class, 'destroyPermanent'])->name('karyawan.destroyPermanent');
        Route::get('/karyawan/{id}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
        Route::put('/karyawan/{id}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
    });

    Route::middleware(['auth.admin', 'role:admin,payroll'])->group(function () {
        Route::get('/karyawan/bank', [\App\Http\Controllers\KaryawanBankController::class, 'index'])->name('karyawan.bank.index');
        Route::post('/karyawan/bank/{nip}', [\App\Http\Controllers\KaryawanBankController::class, 'update'])->name('karyawan.bank.update');
    });

    Route::middleware(['auth.admin', 'role:admin,hrd,ga'])->group(function () {
        Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
        Route::post('/absensi/detail', [AbsensiController::class, 'detail'])->name('absensi.detail');
        Route::post('/absensi/detail/export', [AbsensiController::class, 'exportDetail'])->name('absensi.detail.export');
        Route::post('/absensi/notes/bulk', [AbsensiController::class, 'storeBulkNotes'])->name('absensi.notes.bulk');
    });

    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/setting', [SettingController::class, 'index'])->name('setting.index');
        Route::post('/setting/profile', [SettingController::class, 'updateProfile'])->name('setting.profile');

        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/',                          [PayrollController::class, 'index'])->name('index');
            Route::get('/create',                    [PayrollController::class, 'create'])->name('create');
            Route::post('/preview',                  [PayrollController::class, 'preview'])->name('preview');
            Route::post('/',                         [PayrollController::class, 'store'])->name('store');
            Route::get('/{id}',                      [PayrollController::class, 'show'])->name('show');
            Route::post('/{id}/generate-pengajuan',  [PayrollController::class, 'generatePengajuan'])->name('generatePengajuan');
            Route::get('/{id}/pengajuan',            [PayrollController::class, 'showPengajuan'])->name('pengajuan');
            Route::get('/{id}/export-pengajuan',     [PayrollController::class, 'exportPengajuan'])->name('exportPengajuan');
            Route::get('/{id}/harian',               [PayrollController::class, 'showHarian'])->name('harian.show');
            Route::get('/{id}/export-slip',          [PayrollController::class, 'exportSlipGaji'])->name('export.slip');
            Route::put('/detail/{id}',               [PayrollController::class, 'updateDetail'])->name('detail.update');
            Route::put('/detail/{id}/toggle-lembur', [PayrollController::class, 'toggleLembur'])->name('detail.toggle.lembur');
            Route::get('/detail/{id}/koreksi',      [PayrollController::class, 'getKoreksiData'])->name('detail.koreksi.get');
            Route::post('/detail/{id}/koreksi',     [PayrollController::class, 'saveKoreksi'])->name('detail.koreksi.save');
            Route::put('/{id}/finalize',             [PayrollController::class, 'finalize'])->name('finalize');
            Route::post('/{id}/generate-grand-total', [PayrollController::class, 'generateGrandTotal'])->name('generateGrandTotal');
            Route::delete('/{id}',                   [PayrollController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('borongan')->name('borongan.')->group(function () {
            Route::get('/',             [BoronganController::class, 'index'])->name('index');
            Route::get('/create',       [BoronganController::class, 'create'])->name('create');
            Route::post('/upload',      [BoronganController::class, 'upload'])->name('upload');
            Route::get('/{id}/review',  [BoronganController::class, 'review'])->name('review');
            Route::get('/{id}/review-detail/{nip}', [BoronganController::class, 'getReviewDetail'])->name('review.detail');
            Route::post('/{id}/update-upah-sistem', [BoronganController::class, 'updateUpahSistem'])->name('update.upah.sistem');
            Route::put('/{id}/approve', [BoronganController::class, 'approve'])->name('approve');
            Route::delete('/{id}/undo', [BoronganController::class, 'undo'])->name('undo');
            Route::get('/{id}/rekap',   [BoronganController::class, 'rekapIndex'])->name('rekapIndex');
            Route::get('/{id}/detail/{nip}', [BoronganController::class, 'getDetail'])->name('getDetail');
            Route::put('/rekap/{rekapId}', [BoronganController::class, 'updateRekap'])->name('updateRekap');
            Route::delete('/{id}',      [BoronganController::class, 'destroy'])->name('destroy');
            Route::post('/mutasi/{logId}/resolve', [BoronganController::class, 'resolveMutasi'])->name('borongan.mutasi.resolve');
        });

        Route::middleware(['role:admin'])->group(function () {
            Route::post('/setting/users', [SettingController::class, 'storeUser'])->name('setting.users.store');
            Route::put('/setting/users/{id}', [SettingController::class, 'updateUser'])->name('setting.users.update');
            Route::put('/setting/users/{id}/toggle', [SettingController::class, 'toggleUser'])->name('setting.users.toggle');
            Route::delete('/setting/users/{id}', [SettingController::class, 'destroyUser'])->name('setting.users.destroy');
            Route::get('/setting/backup', [SettingController::class, 'backup'])->name('setting.backup');
        });
    });
});
