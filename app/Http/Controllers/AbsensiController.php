<?php

namespace App\Http\Controllers;

use App\Models\AbsenceNote;
use App\Models\User;
use App\Services\FingerprintService;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    private FingerprintService $fp;

    public function __construct(FingerprintService $fp)
    {
        $this->fp = $fp;
    }

    public function index()
    {
        $karyawan = User::where('is_active', 1)
            ->orderBy('nama')
            ->get();

        $tlMap = User::whereIn('id', $karyawan->pluck('tl_id')->filter()->unique())
            ->pluck('nama', 'id');
        
        $bagianList = User::where('is_active', 1)
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '')
            ->where('bagian', '!=', '-')
            ->distinct()
            ->orderBy('bagian')
            ->pluck('bagian');

        $tlList = User::where('is_active', 1)
            ->whereIn('job_level', ['Team Leader', 'Group Team Leader', 'Supervisor'])
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view('absensi.index', compact('karyawan', 'bagianList', 'tlList', 'tlMap'));
    }

    public function detail(Request $request)
    {
        $request->validate([
            'selected_users'  => 'required|array',
            'tanggal_dari'    => 'required|date',
            'tanggal_sampai'  => 'required|date',
        ]);

        $tanggalDari   = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;

        $selectedUsers = array_map(function ($p) {
            $p = trim((string) $p);
            return preg_match('/^\d+$/', $p) ? (string) intval($p) : $p;
        }, $request->selected_users);

        $tlOrder = $request->tl_order ?? [];
        if (!empty($tlOrder)) {
            // Ambil semua user data keyed by pin
            $allUsers = User::whereIn('pin', $selectedUsers)
                ->get()
                ->keyBy(fn($u) => (string) intval($u->pin));

            // Group pins by tl_id sesuai urutan tlOrder
            $grouped = [];
            foreach ($tlOrder as $tlId) {
                $grouped[(string) $tlId] = [];
            }
            $ungrouped = [];

            foreach ($selectedUsers as $pin) {
                $user = $allUsers[$pin] ?? null;
                $tlId = $user ? (string) $user->tl_id : null;
                if ($tlId && array_key_exists($tlId, $grouped)) {
                    $grouped[$tlId][] = $pin;
                } else {
                    $ungrouped[] = $pin;
                }
            }

            // Flatten
            $ordered = [];
            foreach ($grouped as $group) {
                foreach ($group as $pin) {
                    $ordered[] = $pin;
                }
            }
            foreach ($ungrouped as $pin) {
                $ordered[] = $pin;
            }
            $selectedUsers = $ordered;
        }

        // Ambil data karyawan dari DB
        $nipData = User::whereIn('pin', $selectedUsers)
            ->get()
            ->keyBy(fn($u) => (string) intval($u->pin));

        // Ambil TL names
        $tlIds = $nipData->pluck('tl_id')->filter()->unique();
        $tlMap = User::whereIn('id', $tlIds)->pluck('nama', 'id');

        // Ambil logs dari DB per pin per tanggal
        $logs = \App\Models\AttendanceLog::whereIn('pin', $selectedUsers)
            ->whereBetween('tanggal', [$tanggalDari, $tanggalSampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(function ($item) {
                return $item->pin . '_' . substr((string) $item->tanggal, 0, 10);
            });

        // Ambil absence notes
        $absenceNotes = AbsenceNote::whereIn('pin', $selectedUsers)
            ->whereBetween('date', [$tanggalDari, $tanggalSampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy('date'));

        // Build periode
        $periode = [];
        $current = new \DateTime($tanggalDari);
        $end = new \DateTime($tanggalSampai);
        while ($current <= $end) {
            $periode[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        $summary = [];
        foreach ($selectedUsers as $pin) {
            $karyawan = $nipData[$pin] ?? null;

            $totalHadir = 0;
            $totalTidakHadir = 0;
            $codes = ['A' => 0, 'S' => 0, 'I' => 0, 'SSD' => 0, 'Cuti' => 0, 'GL' => 0, 'DLL' => 0];

            foreach ($periode as $tgl) {
                $isSunday = date('N', strtotime($tgl)) == 7;
                if ($isSunday) continue;

                $dayKey = $pin . '_' . $tgl;
                $dayLogs = $logs[$dayKey] ?? collect();
                $hasIN = $dayLogs->where('status', 'IN')->isNotEmpty();
                $hasOUT = $dayLogs->where('status', 'OUT')->isNotEmpty();

                if ($hasIN || $hasOUT) {
                    $totalHadir++;
                } else {
                    $totalTidakHadir++;
                    $note = $absenceNotes[$pin][$tgl] ?? null;
                    if ($note && isset($codes[$note->code])) {
                        $codes[$note->code]++;
                    }
                }
            }

            $summary[$pin] = [
                'hadir' => $totalHadir,
                'tidak_hadir' => $totalTidakHadir,
                'codes' => $codes,
            ];
        }

        return view('absensi.detail', compact(
            'logs', 'absenceNotes', 'nipData', 'tlMap',
            'selectedUsers', 'tanggalDari', 'tanggalSampai',
            'periode', 'summary'
        ));
    }

    public function exportDetail(Request $request)
    {
        $request->validate([
            'selected_users'  => 'required|array',
            'tanggal_dari'    => 'required|date',
            'tanggal_sampai'  => 'required|date',
        ]);

        $tanggalDari   = $request->tanggal_dari;
        $tanggalSampai = $request->tanggal_sampai;

        $selectedUsers = array_map(function ($p) {
            $p = trim((string) $p);
            return preg_match('/^\d+$/', $p) ? (string) intval($p) : $p;
        }, $request->selected_users);

        $tlOrder = $request->tl_order ?? [];
        if (!empty($tlOrder)) {
            $allUsers = User::whereIn('pin', $selectedUsers)
                ->get()
                ->keyBy(fn($u) => (string) intval($u->pin));

            $grouped = [];
            foreach ($tlOrder as $tlId) {
                $grouped[(string) $tlId] = [];
            }
            $ungrouped = [];

            foreach ($selectedUsers as $pin) {
                $user = $allUsers[$pin] ?? null;
                $tlId = $user ? (string) $user->tl_id : null;
                if ($tlId && array_key_exists($tlId, $grouped)) {
                    $grouped[$tlId][] = $pin;
                } else {
                    $ungrouped[] = $pin;
                }
            }

            $ordered = [];
            foreach ($grouped as $group) {
                foreach ($group as $pin) {
                    $ordered[] = $pin;
                }
            }
            foreach ($ungrouped as $pin) {
                $ordered[] = $pin;
            }

            $selectedUsers = $ordered;
        }

        $nipData = User::whereIn('pin', $selectedUsers)
            ->get()
            ->keyBy(fn($u) => (string) intval($u->pin));

        $tlIds = $nipData->pluck('tl_id')->filter()->unique();
        $tlMap = User::whereIn('id', $tlIds)->pluck('nama', 'id');

        $logs = \App\Models\AttendanceLog::whereIn('pin', $selectedUsers)
            ->whereBetween('tanggal', [$tanggalDari, $tanggalSampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(function ($item) {
                return $item->pin . '_' . substr((string) $item->tanggal, 0, 10);
            });

        $absenceNotes = AbsenceNote::whereIn('pin', $selectedUsers)
            ->whereBetween('date', [$tanggalDari, $tanggalSampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy('date'));

        $periode = [];
        $current = new \DateTime($tanggalDari);
        $end = new \DateTime($tanggalSampai);
        while ($current <= $end) {
            $periode[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        $filename = 'laporan-absensi-' . $tanggalDari . '-sampai-' . $tanggalSampai . '.csv';

        $callback = function () use ($selectedUsers, $nipData, $tlMap, $logs, $absenceNotes, $periode) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No', 'NIP', 'Nama', 'L/P', 'Jabatan', 'Tanggal', 'In', 'Out', 'Overtime', 'Keterangan', 'TL']);

            $namaHari = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
            ];
            $no = 1;

            foreach ($selectedUsers as $pin) {
                $karyawan = $nipData[$pin] ?? null;
                foreach ($periode as $tgl) {
                    $dayName = $namaHari[date('l', strtotime($tgl))] ?? '';
                    $tglDisplay = $dayName . ', ' . date('d/m/Y', strtotime($tgl));
                    $isSunday = date('N', strtotime($tgl)) == 7;

                    $dayLogs = $logs[$pin . '_' . $tgl] ?? collect();
                    $inTimes = $dayLogs->where('status', 'IN')->pluck('datetime')->map(fn($d) => strtotime($d));
                    $outTimes = $dayLogs->where('status', 'OUT')->pluck('datetime')->map(fn($d) => strtotime($d));

                    $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

                    $inDisplay = $inTs ? date('H.i', $inTs) : '-';
                    $outDisplay = $outTs ? date('H.i', $outTs) : '-';

                    $overtimeDisplay = '----';
                    if ($outTs) {
                        $threshold = strtotime($tgl . ' 16:30:00');
                        $minutes = $outTs > $threshold ? floor(($outTs - $threshold) / 60) : 0;
                        $overtimeDisplay = $minutes > 0 ? $minutes . ' menit' : '----';
                    }

                    $absenceNote = $absenceNotes[$pin][$tgl] ?? null;
                    $absenceCode = $absenceNote->code ?? null;
                    $absenceText = $absenceNote->note ?? null;
                    $codeLabels = [
                        'S' => 'S (Sakit)',
                        'A' => 'A (Alpha)',
                        'I' => 'I (Izin)',
                        'SSD' => 'SSD (Sakit Surat Dokter)',
                        'Cuti' => 'Cuti',
                        'GL' => 'GL (Ganti Libur)',
                        'Dll' => 'Dll (Lainnya)',
                    ];

                    if ($isSunday) {
                        $keterangan = 'Minggu';
                    } elseif ($dayLogs->isEmpty()) {
                        $keterangan = $absenceCode ? ($codeLabels[$absenceCode] ?? $absenceCode) : '-';
                        if ($absenceText) {
                            $keterangan .= ' — ' . $absenceText;
                        }
                    } else {
                        $keterangan = '----';
                    }

                    $jabatan = trim(($karyawan->job_title ?? '-') . ' (' . ($karyawan->job_level ?? '-') . ')');
                    $tlName = $tlMap[$karyawan->tl_id ?? null] ?? '-';

                    fputcsv($out, [
                        $no++,
                        $karyawan->nip ?? '-',
                        $karyawan->nama ?? '-',
                        $karyawan->jk ?? '-',
                        $jabatan,
                        $tglDisplay,
                        $inDisplay,
                        $outDisplay,
                        $overtimeDisplay,
                        $keterangan,
                        $tlName,
                    ]);
                }
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeBulkNotes(Request $request)
    {
        $request->validate([
            'selected_pins'  => 'required|array',
            'tanggal_dari'   => 'required|date',
            'tanggal_sampai' => 'required|date',
            'code'           => 'required|in:S,I,A,SSD,Cuti,GL,Dll',
            'note'           => 'required_if:code,Dll|string|max:255',
        ]);

        $pins          = $request->selected_pins;
        $tanggalDari   = new \DateTime($request->tanggal_dari);
        $tanggalSampai = new \DateTime($request->tanggal_sampai);
        $code          = $request->code;
        $createdBy     = \Auth::guard('admin')->id();

        $periode = [];
        $current = clone $tanggalDari;
        while ($current <= $tanggalSampai) {
            if ($current->format('N') != 7) {
                $periode[] = $current->format('Y-m-d');
            }
            $current->modify('+1 day');
        }

        foreach ($pins as $pin) {
            foreach ($periode as $tanggal) {
                $updateData = ['code' => $code, 'created_by' => $createdBy];
                if ($request->filled('note')) {
                    $updateData['note'] = trim((string) $request->note);
                }

                \App\Models\AbsenceNote::updateOrCreate(
                    ['pin' => $pin, 'date' => $tanggal],
                    $updateData
                );
            }
        }

        return back()->with('success', count($pins) . ' karyawan berhasil ditambahkan keterangan ' . $code . '.');
    }
}
