<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceShiftTrait;
use App\Models\User;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class AbsensiPublikController extends Controller
{
    use AttendanceShiftTrait;
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
            ->groupBy(fn($l) => $l->pin . '_' . substr((string) $l->tanggal, 0, 10));

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

            $totalHadir = 0;
            foreach ($periode as $tgl) {
                $dayName = $namaHari[date('l', strtotime($tgl))] ?? '';
                $tglDisplay = $dayName . ', ' . date('d/m/Y', strtotime($tgl));
                $isSunday = date('N', strtotime($tgl)) == 7;

                $key = $user->pin . '_' . $tgl;
                $dayLogs = $logs[$key] ?? collect();

                // Cross-day shift (security) — logika sama persis dengan absensi index
                $result = $this->getInOutForDay($user->pin, $tgl, $logs, $user);
                if ($result['skip'] ?? false) {
                    continue; // OUT sudah dipakai shift malam hari sebelumnya
                }

                $inTs = $result['in_ts'] ?? null;
                $outTs = $result['out_ts'] ?? null;
                $hasChecklok = $dayLogs->isNotEmpty() || $inTs || $outTs;

                // Minggu tanpa data absensi → tampilkan label libur
                if ($isSunday && !$hasChecklok) {
                    $html .= '<tr class="border-t border-slate-100 bg-yellow-50">';
                    $html .= '<td class="px-3 py-2 text-slate-500">' . $tglDisplay . '</td>';
                    $html .= '<td class="px-3 py-2 text-slate-400" colspan="3"><span class="text-amber-600 font-medium">Minggu / Libur</span></td></tr>';
                    continue;
                }

                if ($hasChecklok) {
                    $totalHadir++;
                }

                $inTime = $inTs ? date('H:i', $inTs) : '-';
                $outTime = $outTs ? date('H:i', $outTs) : '-';

                // Sunday dengan data absensi → tampilkan data, tanpa warna merah
                if ($isSunday) {
                    $html .= '<tr class="border-t border-slate-100 hover:bg-[#F9FBFD]">';
                    $html .= '<td class="px-3 py-2 text-slate-600">' . $tglDisplay . '</td>';
                    $html .= '<td class="px-3 py-2 font-semibold text-emerald-600">' . $inTime . '</td>';
                    $html .= '<td class="px-3 py-2 font-semibold text-slate-600">' . $outTime . '</td>';
                    $html .= '<td class="px-3 py-2"><span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2.5 py-0.5">Minggu</span></td>';
                    $html .= '</tr>';
                    continue;
                }

                // Hari biasa tanpa data
                if (!$hasChecklok) {
                    $html .= '<tr class="border-t border-slate-100">';
                    $html .= '<td class="px-3 py-2 text-slate-600">' . $tglDisplay . '</td>';
                    $html .= '<td class="px-3 py-2 text-slate-300">-</td>';
                    $html .= '<td class="px-3 py-2 text-slate-300">-</td>';
                    $html .= '<td class="px-3 py-2"><span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400 bg-slate-50 border border-slate-200 rounded-full px-2.5 py-0.5">Tidak Ada</span></td>';
                    $html .= '</tr>';
                    continue;
                }

                $html .= '<tr class="border-t border-slate-100 hover:bg-[#F9FBFD]">';
                $html .= '<td class="px-3 py-2 text-slate-600">' . $tglDisplay . '</td>';
                $html .= '<td class="px-3 py-2 font-semibold text-emerald-600">' . $inTime . '</td>';
                $html .= '<td class="px-3 py-2 font-semibold text-red-500">' . $outTime . '</td>';
                $html .= '<td class="px-3 py-2"><span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Hadir</span></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            // Total hari masuk
            $html .= '<div class="px-4 py-2 bg-[#F8FAFC] border-t border-[#E5E7EB] text-xs text-slate-500 flex items-center gap-2">';
            $html .= '<svg class="w-4 h-4 text-[#567C8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
            $html .= 'Total <strong class="text-[#2F4156]">' . $totalHadir . '</strong> hari masuk dari ' . count($periode) . ' hari';
            $html .= '</div>';
            $html .= '</div>';
        }

        return response()->json(['html' => $html]);
    }
}