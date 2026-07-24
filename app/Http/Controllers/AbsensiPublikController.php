<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class AbsensiPublikController extends Controller
{
    public function index()
    {
        $bagianList = User::where('is_active', 1)
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '')
            ->where('bagian', '!=', '-')
            ->distinct()
            ->orderBy('bagian')
            ->pluck('bagian');

        return view('absensi.publik', compact('bagianList'));
    }

    public function cari(Request $request)
    {
        $request->validate([
            'keyword'        => 'required|string|min:2',
            'tanggal_dari'   => 'required|date',
            'tanggal_sampai' => 'required|date|after_or_equal:tanggal_dari',
        ]);

        $keyword = $request->keyword;
        $tanggalDari = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;

        // Cari user by nama/nip/bagian
        $users = User::where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nip', 'like', "%{$keyword}%")
                  ->orWhere('bagian', 'like', "%{$keyword}%");
            })
            ->orderBy('nama')
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'html' => '<div class="text-center py-12 text-slate-400 text-sm">Karyawan tidak ditemukan.</div>',
            ]);
        }

        // Ambil attendance logs untuk user yang ditemukan
        $pins = $users->pluck('pin')->filter()->values();
        $logs = AttendanceLog::whereIn('pin', $pins)
            ->whereBetween('tanggal', [$tanggalDari, $tanggalSampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($l) => $l->pin . '_' . $l->tanggal);

        // Generate periode
        $periode = [];
        $start = \Carbon\Carbon::parse($tanggalDari);
        $end = \Carbon\Carbon::parse($tanggalSampai);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $periode[] = $d->format('Y-m-d');
        }

        $namaHari = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];

        $html = '';
        foreach ($users as $user) {
            $html .= '<div class="bg-white rounded-xl border border-[#E5E7EB] overflow-hidden mb-4">';
            $html .= '<div class="bg-[#F8FAFC] px-4 py-2.5 border-b border-[#E5E7EB] flex items-center justify-between">';
            $html .= '<div><span class="font-semibold text-slate-800 text-sm">' . e($user->nama) . '</span>';
            $html .= ' <span class="text-xs text-slate-400">(' . e($user->nip) . ')</span></div>';
            $html .= '<span class="text-xs text-slate-500">' . e($user->bagian ?? '-') . '</span>';
            $html .= '</div>';
            $html .= '<div class="overflow-x-auto"><table class="w-full text-xs">';
            $html .= '<thead><tr class="bg-[#F8FAFC] text-[11px] font-medium text-slate-400 uppercase tracking-wide">';
            $html .= '<th class="px-3 py-2 text-left">Tanggal</th>';
            $html .= '<th class="px-3 py-2 text-left">Masuk</th>';
            $html .= '<th class="px-3 py-2 text-left">Pulang</th>';
            $html .= '<th class="px-3 py-2 text-left">Status</th>';
            $html .= '</tr></thead><tbody>';

            $hasData = false;
            foreach ($periode as $tgl) {
                $dayName = $namaHari[date('l', strtotime($tgl))] ?? '';
                $tglDisplay = $dayName . ', ' . date('d/m/Y', strtotime($tgl));
                $isSunday = date('N', strtotime($tgl)) == 7;

                $key = $user->pin . '_' . $tgl;
                $dayLogs = $logs[$key] ?? collect();

                if ($dayLogs->isEmpty() && !$isSunday) {
                    continue;
                }
                $hasData = true;

                if ($isSunday) {
                    $html .= '<tr class="border-t border-slate-100 bg-yellow-50">';
                    $html .= '<td class="px-3 py-2 text-slate-500">' . $tglDisplay . '</td>';
                    $html .= '<td class="px-3 py-2 text-slate-400" colspan="3"><span class="text-amber-600 font-medium">Minggu / Libur</span></td></tr>';
                    continue;
                }

                $inLog = $dayLogs->first();
                $outLog = $dayLogs->last();
                $inTime = $inLog ? date('H:i', strtotime($inLog->datetime)) : '-';
                $outTime = $outLog ? date('H:i', strtotime($outLog->datetime)) : '-';

                $html .= '<tr class="border-t border-slate-100 hover:bg-[#F9FBFD]">';
                $html .= '<td class="px-3 py-2 text-slate-600">' . $tglDisplay . '</td>';
                $html .= '<td class="px-3 py-2 font-semibold text-emerald-600">' . $inTime . '</td>';
                $html .= '<td class="px-3 py-2 font-semibold text-red-500">' . $outTime . '</td>';
                $html .= '<td class="px-3 py-2"><span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Hadir</span></td>';
                $html .= '</tr>';
            }

            if (!$hasData) {
                $html .= '<tr><td colspan="4" class="px-3 py-6 text-center text-slate-400">Tidak ada data absensi di periode ini.</td></tr>';
            }

            $html .= '</tbody></table></div></div>';
        }

        return response()->json(['html' => $html]);
    }
}