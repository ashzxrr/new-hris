<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AttendanceLog;
use App\Services\FingerprintService;

class DashboardController extends Controller
{
    public function index(FingerprintService $fp)
    {
        $user = Auth::guard('admin')->user();
        $today = date('Y-m-d');

        // Total karyawan aktif
        $totalKaryawan = User::where('is_active', 1)->count();

        // Hadir hari ini (ada log IN hari ini)
        $hadirHariIni = AttendanceLog::where('tanggal', $today)
            ->where('status', 'IN')
            ->distinct('pin')
            ->count('pin');

        // Tidak hadir = total karyawan - hadir
        $tidakHadir = $totalKaryawan - $hadirHariIni;

        // Status mesin
        $mesinStatus = $fp->testConnections();

        $lastSync = AttendanceLog::max('datetime');
        $needsSync = !$lastSync || \Carbon\Carbon::parse($lastSync)->diffInHours(now()) >= 3;

        return view('dashboard', compact(
            'user', 'totalKaryawan', 'hadirHariIni', 'tidakHadir', 'mesinStatus', 'lastSync', 'needsSync'
        ));
    }

    public function sync(FingerprintService $fp)
    {
        Artisan::call('attendance:sync');
        return back()->with('success', 'Sync berhasil! Data absensi telah diperbarui.');
    }
}
