<?php

namespace App\Http\Controllers;

use App\Models\AbsenceNote;
use App\Models\User;
use App\Services\FingerprintService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
            ->map(fn($n) => $n->keyBy(fn($item) => $item->date->format('Y-m-d')));

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
        ->map(fn($n) => $n->keyBy(fn($item) => $item->date->format('Y-m-d')));

    $periode = [];
    $current = new \DateTime($tanggalDari);
    $end = new \DateTime($tanggalSampai);
    while ($current <= $end) {
        $periode[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Detail Absensi');

    $headers = ['No','NIP','Nama','L/P','Jabatan','Kategori Gaji','Tanggal','In','Out','Overtime','Keterangan','TL','Ringkasan'];
    $sheet->fromArray($headers, null, 'A1');

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
    ];
    $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
    $sheet->freezePane('A2');

    $codeLabels = [
        'S' => 'S (Sakit)',
        'A' => 'A (Alpha)',
        'I' => 'I (Izin)',
        'SSD' => 'SSD',
        'Cuti' => 'Cuti',
        'GL' => 'GL',
        'DLL' => 'DLL',
    ];

    $row = 2;
    $no  = 1;

    foreach ($selectedUsers as $pin) {
        $karyawan = $nipData[$pin] ?? null;
        $tlName = $tlMap[$karyawan->tl_id ?? null] ?? '-';
        $jabatan = trim(($karyawan->job_title ?? '-') . ' (' . ($karyawan->job_level ?? '-') . ')');
        $kategori = $karyawan->kategori_gaji ?? '-';

        $totalHadir = 0;
        $totalTidakHadir = 0;
        $codes = ['A' => 0, 'S' => 0, 'I' => 0, 'SSD' => 0, 'Cuti' => 0, 'GL' => 0, 'DLL' => 0];

        foreach ($periode as $tgl) {
            $isSunday = date('N', strtotime($tgl)) == 7;
            if ($isSunday) {
                continue;
            }

            $dayKey = $pin . '_' . $tgl;
            $dayLogs = $logs[$dayKey] ?? collect();
            $absenceNote = $absenceNotes[$pin][$tgl] ?? null;
            $absenceCode = $absenceNote->code ?? null;

            if ($dayLogs->isEmpty()) {
                $totalTidakHadir++;
                if ($absenceCode && isset($codes[$absenceCode])) {
                    $codes[$absenceCode]++;
                }
            } else {
                $totalHadir++;
            }
        }

        $ringkasan = "Total Hadir: {$totalHadir} | Tidak Absen: {$totalTidakHadir} | Alpha (A): {$codes['A']} | Sakit (S): {$codes['S']} | Ijin (I): {$codes['I']} | SSD: {$codes['SSD']} | Cuti: {$codes['Cuti']} | GL: {$codes['GL']} | DLL: {$codes['DLL']}";
        $isFirstRow = true;

        foreach ($periode as $tgl) {
            $isSunday = date('N', strtotime($tgl)) == 7;
            $tglDisplay = date('d/m/Y', strtotime($tgl));

            $dayKey = $pin . '_' . $tgl;
            $dayLogs = $logs[$dayKey] ?? collect();

            $inTimes = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string) $l->datetime));
            $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string) $l->datetime));

            $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
            $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

            $inDisplay = $inTs ? date('H:i', $inTs) : '-';
            $outDisplay = $outTs ? date('H:i', $outTs) : '-';

            $overtimeDisplay = '';
            if ($outTs) {
                $threshold = strtotime($tgl . ' 16:30:00');
                $minutes = $outTs > $threshold ? floor(($outTs - $threshold) / 60) : 0;
                $overtimeDisplay = $minutes > 0 ? $minutes . ' menit' : '';
            }

            $absenceNote = $absenceNotes[$pin][$tgl] ?? null;
            $absenceCode = $absenceNote->code ?? null;
            $absenceText = trim((string) ($absenceNote->note ?? ''));

            $isAbsent = false;
            $isSundayRow = false;

            if ($isSunday) {
                $keterangan = 'Minggu';
                $isSundayRow = true;
            } elseif ($dayLogs->isEmpty()) {
                $isAbsent = true;
                $keterangan = $absenceCode ? strtoupper($absenceCode) : '-';
                if ($absenceText !== '') {
                    $keterangan .= ' — ' . $absenceText;
                } elseif ($absenceCode && strtoupper($absenceCode) !== 'DLL') {
                    $defaultNoteText = [
                        'S' => 'Sakit',
                        'I' => 'Izin',
                        'A' => 'Alpha',
                        'SSD' => 'Sakit Surat Dokter',
                        'Cuti' => 'Cuti',
                        'GL' => 'Ganti Libur',
                        'DLL' => '',
                    ];
                    $defaultText = $defaultNoteText[strtoupper($absenceCode)] ?? '';
                    if ($defaultText !== '') {
                        $keterangan .= ' -- ' . $defaultText;
                    }
                }
            } else {
                $keterangan = '----';
            }

            $ringkasanCell = $isFirstRow ? $ringkasan : '';
            $isFirstRow = false;

            $sheet->fromArray([
                $no++,
                $karyawan->nip ?? '-',
                $karyawan->nama ?? '-',
                $karyawan->jk ?? '-',
                $jabatan,
                $kategori,
                $tglDisplay,
                $inDisplay,
                $outDisplay,
                $overtimeDisplay,
                $keterangan,
                $tlName,
                $ringkasanCell,
            ], null, "A{$row}");

            if ($isSundayRow) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    'font' => ['color' => ['rgb' => '9CA3AF']],
                ]);
            } elseif ($isAbsent) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                    'font' => ['color' => ['rgb' => 'DC2626']],
                ]);
            } else {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                ]);
            }

            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            $row++;
        }
    }

    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'laporan-absensi-' . $tanggalDari . '-sampai-' . $tanggalSampai . '.xlsx';

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
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

        $defaultNoteText = [
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alpha',
            'SSD' => 'Sakit Surat Dokter',
            'Cuti' => 'Cuti',
            'GL' => 'Ganti Libur',
            'Dll' => trim((string) $request->note),
        ];

        foreach ($pins as $pin) {
            foreach ($periode as $tanggal) {
                $updateData = [
                    'code' => $code,
                    'created_by' => $createdBy,
                    'note' => $defaultNoteText[$code] ?? trim((string) $request->note),
                ];

                \App\Models\AbsenceNote::updateOrCreate(
                    ['pin' => $pin, 'date' => $tanggal],
                    $updateData
                );
            }
        }

        return back()->with('success', count($pins) . ' karyawan berhasil ditambahkan keterangan ' . $code . '.');
    }
}
