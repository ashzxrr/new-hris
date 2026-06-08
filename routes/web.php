<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
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

    Route::middleware(['auth.admin', 'role:admin,hrd,ga'])->group(function () {
        Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
        Route::post('/absensi/detail', [AbsensiController::class, 'detail'])->name('absensi.detail');
        Route::post('/absensi/detail/export', [AbsensiController::class, 'exportDetail'])->name('absensi.detail.export');
        Route::post('/absensi/notes/bulk', [AbsensiController::class, 'storeBulkNotes'])->name('absensi.notes.bulk');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('/setting', SettingController::class);
    });
});
