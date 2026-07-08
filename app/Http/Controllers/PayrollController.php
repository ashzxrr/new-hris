<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\AbsenceNote;
use App\Models\AttendanceCorrection;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\SalaryConfig;
use App\Models\BoronganImport;
use App\Models\BoronganRekap;
use App\Models\BoronganHarian;
use App\Models\PayrollGrandTotal;
use App\Models\PayrollPengajuan;
use App\Models\KaryawanBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PayrollController extends Controller
{
    // ========================
    // INDEX — List Payroll
    // ========================
    public function index()
    {
        $payrolls = Payroll::orderByDesc('tanggal_dari')->get();
        return view('payroll.index', compact('payrolls'));
    }

    // ========================
    // CREATE — Form buat payroll baru
    // ========================
    public function create()
    {
        return view('payroll.create');
    }

    // ========================
    // PREVIEW — Preview sebelum generate
    // ========================
    public function preview(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'required|date',
            'tanggal_sampai' => 'required|date|after_or_equal:tanggal_dari',
        ]);

        $dari   = $request->tanggal_dari;
        $sampai = $request->tanggal_sampai;

        // Ambil semua karyawan harian yang punya salary config
        $karyawan = User::where('is_active', 1)
            ->where('kategori_gaji', 'harian')
            ->whereNotNull('salary_config_id')
            ->orderBy('nama')
            ->get();

        // Ambil salary configs
        $salaryConfigs = SalaryConfig::whereIn('nip', $karyawan->pluck('nip'))
            ->where('berlaku_dari', '<=', $dari)
            ->orderByDesc('berlaku_dari')
            ->get()
            ->groupBy('nip')
            ->map(fn($g) => $g->first());

        // Ambil logs
        $logs = AttendanceLog::whereIn('pin', $karyawan->pluck('pin'))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($l) => $l->pin . '_' . substr((string)$l->tanggal, 0, 10));

        // Ambil absence notes
        $absenceNotes = AbsenceNote::whereIn('pin', $karyawan->pluck('pin'))
            ->whereBetween('date', [$dari, $sampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy(fn($i) => $i->date->format('Y-m-d')));

        // Ambil corrections
        $corrections = AttendanceCorrection::whereIn('pin', $karyawan->pluck('pin'))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy('tanggal'));

        // Build periode
        $periode = [];
        $cur = new \DateTime($dari);
        $end = new \DateTime($sampai);
        while ($cur <= $end) {
            $periode[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }

        $previewData = [];

        foreach ($karyawan as $k) {
            $pin = (string) intval($k->pin);
            $nip = $k->nip;
            $config = $salaryConfigs[$nip] ?? null;
            $nominal = $config ? $config->nominal : 0;

            $hadir       = 0;
            $alpha       = 0;
            $izin        = 0;
            $sakit       = 0;
            $lemburMenit = 0;
            $detailHarian = [];

            foreach ($periode as $tgl) {
                $isSunday = date('N', strtotime($tgl)) == 7;
                if ($isSunday) continue;

                // Cek koreksi dulu
                $correction = $corrections[$pin][$tgl] ?? null;

                if ($correction) {
                    // Pakai data koreksi
                    $status  = $correction->status;
                    $jamIn   = $correction->jam_in;
                    $jamOut  = $correction->jam_out;
                } else {
                    // Pakai data fingerprint
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs[$dayKey] ?? collect();

                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

                    $jamIn  = $inTs  ? date('H:i', $inTs)  : null;
                    $jamOut = $outTs ? date('H:i', $outTs) : null;

                    // Status dari absence notes
                    $note   = $absenceNotes[$pin][$tgl] ?? null;
                    $status = $inTs ? 'H' : ($note ? $note->code : 'A');
                }

                // Hitung lembur (threshold 16:30)
                $lemburMenitHari = 0;
                if ($jamOut) {
                    $outTs    = strtotime($tgl . ' ' . $jamOut);
                    $threshold = strtotime($tgl . ' 16:30:00');
                    if ($outTs > $threshold) {
                        $lemburMenitHari = floor(($outTs - $threshold) / 60);
                    }
                }

                // Hitung status kehadiran
                switch ($status) {
                    case 'H': $hadir++; break;
                    case 'A': $alpha++; break;
                    case 'I': $izin++;  break;
                    case 'S': $sakit++; break;
                }

                $lemburMenit += $lemburMenitHari;

                $detailHarian[] = [
                    'tgl'         => $tgl,
                    'status'      => $status,
                    'jam_in'      => $jamIn,
                    'jam_out'     => $jamOut,
                    'lembur_menit'=> $lemburMenitHari,
                    'is_koreksi'  => (bool) $correction,
                ];
            }

            // Hitung gaji
            $gajiPokok      = $hadir * $nominal;
            $upahPerJam     = $nominal / 8;
            $potensiLembur  = floor($upahPerJam * 1.5 * ($lemburMenit / 60)); // hanya untuk preview info
            $totalGaji      = $gajiPokok; // total tanpa lembur (karena belum di-approve)

            $previewData[] = [
                'pin'             => $pin,
                'nip'             => $nip,
                'nama'            => $k->nama,
                'job_title'       => $k->job_title,
                'nominal'         => $nominal,
                'hadir'           => $hadir,
                'alpha'           => $alpha,
                'izin'            => $izin,
                'sakit'           => $sakit,
                'lembur_menit'    => $lemburMenit,
                'gaji_pokok'      => $gajiPokok,
                'gaji_lembur'     => 0, // belum approved
                'potensi_lembur'  => $potensiLembur, // info saja, untuk ditampilkan beda warna
                'total_gaji'      => $totalGaji,
                'detail_harian'   => $detailHarian,
            ];
        }

        return view('payroll.preview', compact(
            'previewData', 'dari', 'sampai', 'periode'
        ));
    }

    // ========================
    // STORE — Simpan payroll
    // ========================
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'required|date',
            'tanggal_sampai' => 'required|date',
        ]);

        $dari   = $request->tanggal_dari;
        $sampai = $request->tanggal_sampai;

        // Cek apakah periode sudah ada
        $existing = Payroll::where('tanggal_dari', $dari)
            ->where('tanggal_sampai', $sampai)
            ->first();
        if ($existing) {
            return redirect()->route('payroll.show', $existing->id)
                ->with('error', 'Payroll periode ini sudah ada.');
        }

        // Tentukan periode label (misal: 2026-06-1 atau 2026-06-2)
        $bulan = date('Y-m', strtotime($dari));
        $half  = date('d', strtotime($dari)) <= 15 ? '1' : '2';
        $periodeLabel = $bulan . '-' . $half;

        DB::beginTransaction();
        try {
            $payroll = Payroll::create([
                'periode'        => $periodeLabel,
                'tanggal_dari'   => $dari,
                'tanggal_sampai' => $sampai,
                'status'         => 'draft',
                'created_by'     => Auth::guard('admin')->id(),
            ]);

            // Re-run preview logic untuk simpan detail
            $karyawan = User::where('is_active', 1)
                ->where('kategori_gaji', 'harian')
                ->whereNotNull('salary_config_id')
                ->orderBy('nama')
                ->get();

            $salaryConfigs = SalaryConfig::whereIn('nip', $karyawan->pluck('nip'))
                ->where('berlaku_dari', '<=', $dari)
                ->orderByDesc('berlaku_dari')
                ->get()
                ->groupBy('nip')
                ->map(fn($g) => $g->first());

            $logs = AttendanceLog::whereIn('pin', $karyawan->pluck('pin'))
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderBy('datetime')
                ->get()
                ->groupBy(fn($l) => $l->pin . '_' . substr((string)$l->tanggal, 0, 10));

            $absenceNotes = AbsenceNote::whereIn('pin', $karyawan->pluck('pin'))
                ->whereBetween('date', [$dari, $sampai])
                ->get()
                ->groupBy('pin')
                ->map(fn($n) => $n->keyBy(fn($i) => $i->date->format('Y-m-d')));

            $corrections = AttendanceCorrection::whereIn('pin', $karyawan->pluck('pin'))
                ->whereBetween('tanggal', [$dari, $sampai])
                ->get()
                ->groupBy('pin')
                ->map(fn($n) => $n->keyBy('tanggal'));

            $periode = [];
            $cur = new \DateTime($dari);
            $end = new \DateTime($sampai);
            while ($cur <= $end) {
                $periode[] = $cur->format('Y-m-d');
                $cur->modify('+1 day');
            }

            foreach ($karyawan as $k) {
                $pin     = (string) intval($k->pin);
                $nip     = $k->nip;
                $config  = $salaryConfigs[$nip] ?? null;
                $nominal = $config ? $config->nominal : 0;

                $hadir = $alpha = $izin = $sakit = $lemburMenit = 0;
                $setengahHari = 0;

                foreach ($periode as $tgl) {
                    if (date('N', strtotime($tgl)) == 7) continue;

                    $correction = $corrections[$pin][$tgl] ?? null;
                    if ($correction) {
                        $status = $correction->status;
                        $jamOut = $correction->jam_out;
                    } else {
                        $dayKey  = $pin . '_' . $tgl;
                        $dayLogs = $logs[$dayKey] ?? collect();
                        $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                        $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));
                        $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                        $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
                        $jamOut = $outTs ? date('H:i', $outTs) : null;
                        $note   = $absenceNotes[$pin][$tgl] ?? null;
                        $status = $inTs ? 'H' : ($note ? $note->code : 'A');
                    }

                    if ($jamOut) {
                        $outTs     = strtotime($tgl . ' ' . $jamOut);
                        $threshold = strtotime($tgl . ' 16:30:00');
                        if ($outTs > $threshold) {
                            $lemburMenit += floor(($outTs - $threshold) / 60);
                        }
                    }

                    switch ($status) {
                        case 'H': $hadir++; break;
                        case 'A': $alpha++; break;
                        case 'I': $izin++;  break;
                        case 'S': $sakit++; break;
                        case 'ST': $setengahHari++; break;
                    }
                }

                $gajiPokok   = ($hadir + $setengahHari) * $nominal;
                $potonganSt  = $setengahHari * ($nominal / 2);
                // Lembur belum di-approve saat generate awal, jadi gaji_lembur = 0
                $gajiLembur  = 0;
                $totalGaji   = $gajiPokok + $gajiLembur - $potonganSt;

                PayrollDetail::create([
                    'payroll_id'      => $payroll->id,
                    'pin'             => $pin,
                    'nip'             => $nip,
                    'nama'            => $k->nama,
                    'nominal_harian'  => $nominal,
                    'hadir'           => $hadir,
                    'alpha'           => $alpha,
                    'izin'            => $izin,
                    'sakit'           => $sakit,
                    'setengah_hari'   => $setengahHari,
                    'lembur_menit'    => $lemburMenit,
                    'gaji_pokok'      => $gajiPokok,
                    'gaji_lembur'     => $gajiLembur,
                    'tambahan'        => 0,
                    'potongan'        => 0,
                    'total_gaji'      => $totalGaji,
                ]);
            }

            DB::commit();
            return redirect()->route('payroll.show', $payroll->id)
                ->with('success', 'Payroll periode ' . $periodeLabel . ' berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat payroll: ' . $e->getMessage());
        }
    }

    // ========================
    // SHOW — Overview payroll (dashboard dengan jenis status)
    // ========================
    public function show($id)
    {
        $payroll = Payroll::findOrFail($id);
        
        // Query status masing-masing jenis
        $cabutImport = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'cabut')
            ->latest()
            ->first();
        
        $hcrImport = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'hcr')
            ->latest()
            ->first();
        
        $mouldingImport = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'moulding')
            ->latest()
            ->first();
        
        $harianDetailCount = PayrollDetail::where('payroll_id', $id)->count();

        $cabutOk = !$cabutImport || $cabutImport->status === 'approved';
        $hcrOk = !$hcrImport || $hcrImport->status === 'approved';
        $mouldingOk = !$mouldingImport || $mouldingImport->status === 'approved';
        $adaData = boolval($cabutImport || $hcrImport || $mouldingImport || $harianDetailCount > 0);
        $bisaGenerateGrandTotal = $cabutOk && $hcrOk && $mouldingOk && $payroll->status === 'final' && $adaData;
        
        $grandTotals = PayrollGrandTotal::where('payroll_id', $id)->orderBy('nama')->get();
        $sudahAdaPengajuan = PayrollPengajuan::where('payroll_id', $id)->exists();

        return view('payroll.show', compact('payroll', 'cabutImport', 'hcrImport', 'mouldingImport', 'harianDetailCount', 'bisaGenerateGrandTotal', 'grandTotals', 'sudahAdaPengajuan'));
    }

    // ========================
    // GENERATE PENGAJUAN — Generate payroll_pengajuan from grand totals
    // ========================
    public function generatePengajuan($id)
    {
        $payroll = Payroll::findOrFail($id);

        $grandTotals = PayrollGrandTotal::where('payroll_id', $id)->get();

        if ($grandTotals->isEmpty()) {
            return back()->with('error', 'Grand Total belum di-generate untuk periode ini.');
        }

        // Hapus data lama agar generate ulang tidak duplikat
        PayrollPengajuan::where('payroll_id', $id)->delete();

        foreach ($grandTotals as $row) {
            $bank = KaryawanBank::where('nip', $row->nip)->first();

            $detail = json_decode($row->detail_harian, true);
            $gajiReal = is_array($detail) ? array_sum($detail) : 0;

            PayrollPengajuan::create([
                'payroll_id' => $id,
                'nip' => $row->nip,
                'nama' => $row->nama,
                'jenis' => $row->job_label ?? $row->jenis ?? 'harian',
                'gaji_real' => $gajiReal,
                'komplain' => $row->komplain ?? 0,
                'insentif' => $row->insentif ?? 0,
                'potongan_lain' => $row->potongan_lain ?? 0,
                'potongan_bpjs' => $row->potongan_bpjs ?? 0,
                'total_akhir' => $row->total_akhir ?? 0,
                'no_rekening' => $bank->no_rekening ?? null,
                'nama_bank' => $bank->nama_bank ?? null,
                'email' => $bank->email ?? null,
                'diajukan_at' => now(),
                'diajukan_by' => auth()->id(),
            ]);
        }

        $tanpaBank = PayrollPengajuan::where('payroll_id', $id)->whereNull('no_rekening')->count();

        return redirect()->route('payroll.show', $id)->with('success', "Pengajuan berhasil di-generate. {$tanpaBank} karyawan belum punya data rekening.");
    }

    // ========================
    // SHOW PENGAJUAN — Tampilkan pengajuan grouped by jenis
    // ========================
    public function showPengajuan($id)
    {
        $payroll = Payroll::findOrFail($id);
        $pengajuan = PayrollPengajuan::where('payroll_id', $id)->get()->groupBy('jenis');

        return view('payroll.pengajuan', compact('payroll', 'pengajuan'));
    }

    // ========================
    // EXPORT PENGAJUAN — Export pengajuan grouped into sheets
    // ========================
    public function exportPengajuan($id)
    {
        $payroll = Payroll::findOrFail($id);

        $pengajuan = PayrollPengajuan::where('payroll_id', $id)->get();

        if ($pengajuan->isEmpty()) {
            return back()->with('error', 'Belum ada data pengajuan untuk periode ini.');
        }

        $spreadsheet = new Spreadsheet();

        // Remove default sheet
        $defaultIndex = $spreadsheet->getIndex($spreadsheet->getActiveSheet());
        $spreadsheet->removeSheetByIndex($defaultIndex);

        // Grouping
        $cabutRows = [];
        $mouldingRows = [];
        $harianRows = [];

        foreach ($pengajuan as $row) {
            $jenis = (string) ($row->jenis ?? '');
            if (stripos($jenis, 'harian') !== false) {
                $harianRows[] = $row;
            } elseif (stripos($jenis, 'moulding') !== false) {
                $mouldingRows[] = $row;
            } else {
                $cabutRows[] = $row;
            }
        }

        $groups = [
            'CABUT' => $cabutRows,
            'MOULDING' => $mouldingRows,
            'HARIAN' => $harianRows,
        ];

        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M'];

        foreach ($groups as $name => $rows) {
            if (empty($rows)) continue;

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($name, 0, 31));

            // Titles
            $sheet->mergeCells('A1:M1');
            $sheet->setCellValue('A1', 'PT WALET ABDILLAH JABLI');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:M2');
            $sheet->setCellValue('A2', 'REKAP GAJI BORONGAN ' . strtoupper($name) . ' PERIODE ' . $payroll->periode);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A3:M3');
            $sheet->setCellValue('A3', 'Periode ' . \Carbon\Carbon::parse($payroll->tanggal_dari)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($payroll->tanggal_sampai)->translatedFormat('d F Y'));
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Spacer row 4 left blank

            // Header row (row 5)
            $headers = [
                'No', 'NIP', 'NAMA', 'Jenis', 'Gaji Real', 'Komplain', 'Insentif', 'Potongan Lain', 'Potongan BPJS', 'TF PAYROL', 'No Rekening', 'Bank', 'Email'
            ];

            $headerRange = 'A5:M5';
            $sheet->fromArray($headers, null, 'A5');
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Freeze header (A6)
            $sheet->freezePane('A6');

            // Data rows
            $rowNum = 6;
            $no = 1;
            foreach ($rows as $r) {
                $sheet->setCellValue('A' . $rowNum, $no);
                $sheet->setCellValue('B' . $rowNum, $r->nip);
                $sheet->setCellValue('C' . $rowNum, $r->nama);
                $sheet->setCellValue('D' . $rowNum, $r->jenis);
                $sheet->setCellValue('E' . $rowNum, $r->gaji_real ?? 0);
                $sheet->setCellValue('F' . $rowNum, $r->komplain ?? 0);
                $sheet->setCellValue('G' . $rowNum, $r->insentif ?? 0);
                $sheet->setCellValue('H' . $rowNum, $r->potongan_lain ?? 0);
                $sheet->setCellValue('I' . $rowNum, $r->potongan_bpjs ?? 0);
                $sheet->setCellValue('J' . $rowNum, $r->total_akhir ?? 0);
                $sheet->setCellValue('K' . $rowNum, $r->no_rekening ?? '');
                $sheet->setCellValue('L' . $rowNum, $r->nama_bank ?? '');
                $sheet->setCellValue('M' . $rowNum, $r->email ?? '');

                // Number format for nominal columns E-J
                $sheet->getStyle("E{$rowNum}:J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');

                // Border for data row
                $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $rowNum++;
                $no++;
            }

            $lastDataRow = $rowNum - 1;

            // Total row
            $totalRow = $lastDataRow + 1;
            $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true);

            // Sum formulas for E-J
            $colsToSum = ['E','F','G','H','I','J'];
            foreach ($colsToSum as $col) {
                $sheet->setCellValue($col . $totalRow, "=SUM({$col}6:{$col}{$lastDataRow})");
                $sheet->getStyle($col . $totalRow)->getFont()->setBold(true);
                $sheet->getStyle($col . $totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
                $sheet->getStyle($col . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
            }

            // Border for total row
            $sheet->getStyle("A{$totalRow}:M{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Auto-size columns A-M
            foreach ($columns as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // BELUM ADA REKENING sheet
        $noBank = $pengajuan->filter(function($p) { return empty($p->no_rekening); })->values();
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('BELUM ADA REKENING');

        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'PT WALET ABDILLAH JABLI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'BELUM ADA REKENING PERIODE ' . $payroll->periode);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A3', 'Periode ' . \Carbon\Carbon::parse($payroll->tanggal_dari)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($payroll->tanggal_sampai)->translatedFormat('d F Y'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header A5:D5
        $sheet->fromArray(['No','NIP','Nama','Jenis'], null, 'A5');
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);
        $sheet->getStyle('A5:D5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('A5:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:D5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->freezePane('A6');

        $rnum = 6; $no = 1;
        foreach ($noBank as $p) {
            $sheet->setCellValue('A' . $rnum, $no);
            $sheet->setCellValue('B' . $rnum, $p->nip);
            $sheet->setCellValue('C' . $rnum, $p->nama);
            $sheet->setCellValue('D' . $rnum, $p->jenis);
            $sheet->getStyle("A{$rnum}:D{$rnum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rnum++; $no++;
        }

        foreach (['A','B','C','D'] as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Set first sheet as active (if exists)
        if ($spreadsheet->getSheetCount() > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        }

        // Save to temporary file
        $fileNameSafe = preg_replace('/[\/\\\s]+/', '_', $payroll->periode);
        $fileName = "Pengajuan_Gaji_{$fileNameSafe}.xlsx";
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, $fileName)->deleteFileAfterSend(true);
    }

    // ========================
    // SHOW HARIAN — Detail payroll (tabel detail harian)
    // ========================
    public function showHarian($id)
    {
        $payroll = Payroll::findOrFail($id);
        $details = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();
        
        foreach ($details as $d) {
            $upahPerJam = $d->nominal_harian / 8;
            $d->potensi_lembur = floor($upahPerJam * 1.5 * ($d->lembur_menit / 60));
        }
        
        return view('payroll.harian.show', compact('payroll', 'details'));
    }

    public function generateGrandTotal($id)
    {
        $payroll = Payroll::findOrFail($id);

        $cabutImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'cabut')
            ->get();
        $hcrImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'hcr')
            ->get();
        $mouldingImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'moulding')
            ->get();

        foreach (['cabut' => $cabutImports, 'hcr' => $hcrImports, 'moulding' => $mouldingImports] as $type => $imports) {
            $belumApproved = $imports->where('status', '!=', 'approved')->count();
            if ($belumApproved > 0) {
                return back()->with('error', "Ada {$belumApproved} import jenis {$type} yang belum di-approve. Selesaikan dulu sebelum generate Grand Total.");
            }
        }

        if ($payroll->status !== 'final') {
            return back()->with('error', 'Periode harus difinalisasi dulu (lewat halaman Harian) sebelum generate Grand Total.');
        }

        PayrollGrandTotal::where('payroll_id', $id)->delete();

        $cabutHcrImportIds = $cabutImports->pluck('id')
            ->merge($hcrImports->pluck('id'))
            ->filter()
            ->values()
            ->all();

        $mouldingImportIds = $mouldingImports->pluck('id')
            ->filter()
            ->values()
            ->all();

        $boronganImportIds = array_merge($cabutHcrImportIds, $mouldingImportIds);

        $boronganNips = BoronganRekap::whereIn('borongan_import_id', $boronganImportIds)
            ->pluck('nip')
            ->map(fn($n) => trim(strtoupper($n)))
            ->filter()
            ->unique();

        $harianNips = PayrollDetail::where('payroll_id', $id)
            ->pluck('nip')
            ->map(fn($n) => trim(strtoupper($n)))
            ->filter()
            ->unique();

        $allNips = $boronganNips->merge($harianNips)->unique()->values();

        $dateFrom = new \DateTime($payroll->tanggal_dari);
        $dateTo = new \DateTime($payroll->tanggal_sampai);

        foreach ($allNips as $nip) {
            $detailHarianGram = [];
            $totalLembur = 0;
            $user = User::whereRaw('TRIM(UPPER(nip)) = ?', [$nip])->first();

            $section = 'harian';
            $jobLabel = $user?->bagian ?? 'Harian';

            $adaCabutHcr = BoronganRekap::whereIn('borongan_import_id', $cabutHcrImportIds)
                ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
                ->exists();

            if ($adaCabutHcr) {
                $section = 'cabut';
                $rekapByJenis = BoronganRekap::whereIn('borongan_rekap.borongan_import_id', $cabutHcrImportIds)
                    ->whereRaw('TRIM(UPPER(borongan_rekap.nip)) = ?', [$nip])
                    ->join('borongan_imports', 'borongan_rekap.borongan_import_id', '=', 'borongan_imports.id')
                    ->selectRaw('borongan_imports.jenis, SUM(borongan_rekap.total_gram) as total_gram_jenis')
                    ->groupBy('borongan_imports.jenis')
                    ->orderByDesc('total_gram_jenis')
                    ->first();

                if ($rekapByJenis?->jenis === 'hcr') {
                    $jobLabel = 'Titil Hcr';
                } else {
                    $jobLabel = 'Cabut';
                }
            } else {
                $adaMoulding = BoronganRekap::whereIn('borongan_import_id', $mouldingImportIds)
                    ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
                    ->exists();

                if ($adaMoulding) {
                    $section = 'moulding';
                    $jobLabel = 'Moulding';
                }
            }

            $rekapQuery = BoronganRekap::whereIn('borongan_import_id', $boronganImportIds)
                ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip]);

            if ($rekapQuery->exists()) {
                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $tanggal = $currentDate->format('Y-m-d');

                    $dailyBorongan = BoronganHarian::whereIn('borongan_import_id', $boronganImportIds)
                        ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
                        ->where('tanggal', $tanggal)
                        ->get();

                    $detailHarianGram[$tanggal] = $dailyBorongan->sum('upah_sistem');
                    $currentDate->modify('+1 day');
                }
            } else {
                $payrollDetail = PayrollDetail::where('payroll_id', $id)
                    ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
                    ->first();
                $pin = $user?->pin;

                $corrections = AttendanceCorrection::where('pin', $pin)
                    ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
                    ->get()
                    ->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d'));

                $logs = AttendanceLog::where('pin', $pin)
                    ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
                    ->orderBy('datetime')
                    ->get()
                    ->groupBy(fn($l) => substr((string)$l->tanggal, 0, 10));

                $hariHadirList = [];
                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $tanggal = $currentDate->format('Y-m-d');

                    $correction = $corrections[$tanggal] ?? null;
                    $isHadir = false;

                    if ($correction) {
                        $isHadir = ($correction->status === 'H');
                    } else {
                        $dayLogs = $logs[$tanggal] ?? collect();
                        if ($dayLogs->isNotEmpty()) {
                            $isHadir = true;
                        }
                    }

                    if ($isHadir) {
                        $hariHadirList[] = $tanggal;
                    }

                    $currentDate->modify('+1 day');
                }

                $hadirCount = count($hariHadirList);
                $gajiPerHari = ($hadirCount > 0 && $payrollDetail) ? intdiv($payrollDetail->gaji_pokok, $hadirCount) : 0;

                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $tanggal = $currentDate->format('Y-m-d');

                    if (in_array($tanggal, $hariHadirList)) {
                        $correction = $corrections[$tanggal] ?? null;

                        $lemburHariIni = 0;
                        if ($correction?->lembur_approved) {
                            $lemburMenit = $correction->lembur_menit ?? 0;
                            $upahPerJam = $payrollDetail?->nominal_harian ? ($payrollDetail->nominal_harian / 8) : 0;
                            $lemburHariIni = (int) floor($upahPerJam * 1.5 * ($lemburMenit / 60));
                        }

                        $detailHarianGram[$tanggal] = $gajiPerHari;
                        $totalLembur += $lemburHariIni;
                    } else {
                        $detailHarianGram[$tanggal] = 0;
                    }

                    $currentDate->modify('+1 day');
                }
            }

            $insentif = $rekapQuery->sum('tambahan');
            $komplain = $rekapQuery->sum('komplain');
            $potonganLain = $rekapQuery->sum('potongan_lain');
            $potonganBpjs = $rekapQuery->sum('potongan_bpjs');

            $totalAkhir = array_sum($detailHarianGram) + $totalLembur + $insentif + $komplain - $potonganLain - $potonganBpjs;

            PayrollGrandTotal::create([
                'payroll_id'   => $id,
                'nip'          => $nip,
                'nama'         => $user?->nama ?? $nip,
                'job_label'    => $jobLabel,
                'section'      => $section,
                'detail_harian'=> json_encode($detailHarianGram),
                'total_lembur' => $totalLembur,
                'insentif'     => $insentif,
                'komplain'     => $komplain,
                'potongan_lain'=> $potonganLain,
                'potongan_bpjs'=> $potonganBpjs,
                'total_akhir'  => $totalAkhir,
                'generated_at' => now(),
            ]);
        }

        return redirect()->route('payroll.show', $id)
            ->with('success', 'Grand Total berhasil di-generate untuk ' . $allNips->count() . ' karyawan.');
    }

    // ========================
    // UPDATE DETAIL — Edit tambahan/potongan
    // ========================
    public function updateDetail(Request $request, $id)
    {
        $detail = PayrollDetail::findOrFail($id);
        $request->validate([
            'tambahan' => 'nullable|integer|min:0',
            'potongan' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $tambahan = $request->tambahan ?? 0;
        $manualPotongan = $request->potongan ?? 0;
        $potonganSt = $detail->setengah_hari * ($detail->nominal_harian / 2);
        $total    = $detail->gaji_pokok + $detail->gaji_lembur + $tambahan - $manualPotongan - $potonganSt;

        $detail->update([
            'tambahan'    => $tambahan,
            'potongan'    => $manualPotongan,
            'total_gaji'  => $total,
            'keterangan'  => $request->keterangan,
        ]);

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    // ========================
    // FINALIZE — Ubah status ke final
    // ========================
    public function finalize($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update(['status' => 'final']);
        return back()->with('success', 'Payroll berhasil difinalisasi.');
    }

    // ========================
    // DESTROY — Hapus payroll draft
    // ========================
    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status === 'final') {
            return back()->with('error', 'Payroll final tidak bisa dihapus.');
        }
        $payroll->delete();
        return redirect()->route('payroll.index')
            ->with('success', 'Payroll berhasil dihapus.');
    }

    public function exportSlipGaji($id)
    {
        $payroll = Payroll::findOrFail($id);
        $details = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();

        $dari   = $payroll->tanggal_dari->format('Y-m-d');
        $sampai = $payroll->tanggal_sampai->format('Y-m-d');

        $periode = [];
        $cur = new \DateTime($dari);
        $end = new \DateTime($sampai);
        while ($cur <= $end) {
            $periode[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }

        $pins = $details->pluck('pin')->toArray();

        $logs = \App\Models\AttendanceLog::whereIn('pin', $pins)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($l) => $l->pin . '_' . substr((string)$l->tanggal, 0, 10));

        $absenceNotes = \App\Models\AbsenceNote::whereIn('pin', $pins)
            ->whereBetween('date', [$dari, $sampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy(fn($i) => $i->date->format('Y-m-d')));

        $corrections = AttendanceCorrection::whereIn('pin', $pins)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get()
            ->groupBy('pin')
            ->map(fn($n) => $n->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d')));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Slip Gaji');

        $slipPerRow   = 5;
        $colsPerSlip  = 3;
        $colSeparator = 1;
        $colStep      = $colsPerSlip + $colSeparator;

        $colLetter = function(int $colIndex): string {
            $letters = '';
            $colIndex++;
            while ($colIndex > 0) {
                $mod = ($colIndex - 1) % 26;
                $letters = chr(65 + $mod) . $letters;
                $colIndex = (int)(($colIndex - $mod) / 26);
            }
            return $letters;
        };

        $allSlips = [];
        foreach ($details as $d) {
            $pin     = $d->pin;
            $nominal = $d->nominal_harian;
            $user    = \App\Models\User::where('pin', $pin)->first();

            $rows = [];
            foreach ($periode as $tgl) {
                if (date('N', strtotime($tgl)) == 7) continue;

                $correction = $corrections[$pin][$tgl] ?? null;
                if ($correction) {
                    $status = $correction->status;
                    $jamOut = $correction->jam_out ? substr($correction->jam_out, 0, 5) : null;
                    $jamIn  = $correction->jam_in  ? substr($correction->jam_in,  0, 5) : null;
                } else {
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs[$dayKey] ?? collect();
                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));
                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
                    $jamIn  = $inTs  ? date('H:i', $inTs)  : null;
                    $jamOut = $outTs ? date('H:i', $outTs) : null;
                    $note   = $absenceNotes[$pin][$tgl] ?? null;
                    $status = $inTs ? 'H' : ($note ? $note->code : 'A');
                }

                $lemburJam = 0;
                if ($jamOut) {
                    $outTs     = strtotime($tgl . ' ' . $jamOut);
                    $threshold = strtotime($tgl . ' 16:30:00');
                    $selisihMenit = ($outTs - $threshold) / 60;
                    if ($outTs > $threshold && $selisihMenit >= 60) {
                        $lemburJam = round($selisihMenit / 3600, 1);
                    }
                }

                $gajiHari = 0;
                if ($status === 'H') {
                    $upahPerJam = $nominal / 8;
                    $gajiHari   = $nominal + floor($upahPerJam * 1.5 * $lemburJam);
                }

                $rows[] = [
                    'tgl'       => date('j-M-Y', strtotime($tgl)),
                    'lembur'    => $lemburJam > 0 ? $lemburJam : '-',
                    'gaji'      => $gajiHari > 0  ? $gajiHari  : '-',
                    'is_hadir'  => $status === 'H',
                ];
            }

            $allSlips[] = [
                'nama'      => $d->nama,
                'nip'       => $d->nip,
                'bagian'    => $user->bagian    ?? '-',
                'job_title' => $user->job_title ?? '-',
                'periode'   => \Carbon\Carbon::parse($dari)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($sampai)->format('d M Y'),
                'total'     => $d->total_gaji,
                'rows'      => $rows,
            ];
        }

        $gridStartRows = [0 => 1];
        foreach (array_chunk($allSlips, $slipPerRow) as $gi => $chunk) {
            $maxRows = 0;
            foreach ($chunk as $slip) {
                $slipRows = 2 + 5 + 1 + count($slip['rows']) + 1 + 1 + 2;
                $maxRows  = max($maxRows, $slipRows);
            }
            $gridStartRows[$gi + 1] = ($gridStartRows[$gi] ?? 1) + $maxRows;
        }

        foreach (array_chunk($allSlips, $slipPerRow) as $gi => $chunk) {
            foreach ($chunk as $si => $slip) {
                $colStart = $si * $colStep;
                $r = $gridStartRows[$gi];

                $cA = $colLetter($colStart);
                $cB = $colLetter($colStart + 1);
                $cC = $colLetter($colStart + 2);

                $sheet->mergeCells("{$cA}{$r}:{$cC}{$r}");
                $sheet->setCellValue("{$cA}{$r}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("{$cA}{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $r++;

                $sheet->mergeCells("{$cA}{$r}:{$cC}{$r}");
                $sheet->setCellValue("{$cA}{$r}", '*Private and Confidential');
                $sheet->getStyle("{$cA}{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $r++;

                $infoRows = [
                    ['NAMA',    $slip['nama']],
                    ['NIP',     $slip['nip']],
                    ['DIVISI',  $slip['bagian']],
                    ['JABATAN', $slip['job_title']],
                    ['PERIODE', $slip['periode']],
                ];
                foreach ($infoRows as $info) {
                    $sheet->setCellValue("{$cA}{$r}", $info[0]);
                    $sheet->setCellValue("{$cB}{$r}", '');
                    $sheet->setCellValue("{$cC}{$r}", $info[1]);
                    $sheet->getStyle("{$cA}{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $r++;
                }

                $sheet->setCellValue("{$cA}{$r}", 'Tanggal');
                $sheet->setCellValue("{$cB}{$r}", 'Lembur');
                $sheet->setCellValue("{$cC}{$r}", 'Gaji');
                $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $r++;

                foreach ($slip['rows'] as $row) {
                    $sheet->setCellValue("{$cA}{$r}", $row['tgl']);
                    $sheet->setCellValue("{$cB}{$r}", $row['lembur']);
                    $sheet->setCellValue("{$cC}{$r}", $row['gaji']);

                    if (is_numeric($row['gaji'])) {
                        $sheet->getStyle("{$cC}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                    }

                    $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->applyFromArray([
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("{$cA}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $r++;
                }

                $sheet->mergeCells("{$cA}{$r}:{$cC}{$r}");
                $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $r++;

                $sheet->mergeCells("{$cA}{$r}:{$cB}{$r}");
                $sheet->setCellValue("{$cA}{$r}", 'Total Gaji');
                $sheet->setCellValue("{$cC}{$r}", $slip['total']);
                $sheet->getStyle("{$cC}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->applyFromArray([
                    'font'    => ['bold' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
            }
        }

        for ($si = 0; $si < $slipPerRow; $si++) {
            $colStart = $si * $colStep;
            $sheet->getColumnDimension($colLetter($colStart))->setWidth(16);
            $sheet->getColumnDimension($colLetter($colStart + 1))->setWidth(10);
            $sheet->getColumnDimension($colLetter($colStart + 2))->setWidth(14);
            $sheet->getColumnDimension($colLetter($colStart + 3))->setWidth(3);
        }

        $filename = 'slip-gaji-' . $payroll->periode . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ========================
    // GET KOREKSI DATA — untuk modal
    // ========================
    public function getKoreksiData(Request $request, $detailId)
    {
        $detail  = PayrollDetail::findOrFail($detailId);
        $payroll = Payroll::findOrFail($detail->payroll_id);

        $pin  = $detail->pin;
        $dari = $payroll->tanggal_dari->format('Y-m-d');
        $sampai = $payroll->tanggal_sampai->format('Y-m-d');

        // Build periode
        $periode = [];
        $cur = new \DateTime($dari);
        $end = new \DateTime($sampai);
        while ($cur <= $end) {
            $periode[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }

        // Ambil logs fingerprint
        $logs = \App\Models\AttendanceLog::where('pin', $pin)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($l) => substr((string)$l->tanggal, 0, 10));

        // Ambil koreksi yang sudah ada
        $corrections = AttendanceCorrection::where('pin', $pin)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->get()
            ->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d'));

        $rows = [];
        foreach ($periode as $tgl) {
            $isSunday = date('N', strtotime($tgl)) == 7;
            $dayLogs  = $logs[$tgl] ?? collect();

            $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
            $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

            $fpIn  = $inTimes->isNotEmpty()  ? date('H:i', $inTimes->min())  : null;
            $fpOut = $outTimes->isNotEmpty() ? date('H:i', $outTimes->max()) : null;

            $correction = $corrections[$tgl] ?? null;

            // Hitung lembur otomatis dari FP OUT jika koreksi tidak menyediakan jam_out
            $effectiveOutTs = null;
            if ($correction && !empty($correction->jam_out)) {
                $effectiveOutTs = strtotime($tgl . ' ' . $correction->jam_out);
            } else {
                $effectiveOutTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
            }

            if ($effectiveOutTs && !$isSunday) {
                $threshold = strtotime($tgl . ' 16:30:00');
                $lemburMenitAuto = $effectiveOutTs > $threshold ? floor(($effectiveOutTs - $threshold) / 60) : 0;
            } else {
                $lemburMenitAuto = 0;
            }

            $lemburMenitFinal = $correction ? ($correction->lembur_menit ?? $lemburMenitAuto) : $lemburMenitAuto;

            $rows[] = [
                'tgl'             => $tgl,
                'tgl_display'     => \Carbon\Carbon::parse($tgl)->translatedFormat('D, d M Y'),
                'is_sunday'       => $isSunday,
                'fp_in'           => $fpIn,
                'fp_out'          => $fpOut,
                'kor_in'          => $correction ? substr($correction->jam_in ?? '', 0, 5) : null,
                'kor_out'         => $correction ? substr($correction->jam_out ?? '', 0, 5) : null,
                'kor_status'      => $correction ? $correction->status : null,
                'kor_ket'         => $correction ? $correction->keterangan : null,
                'has_kor'         => (bool) $correction,
                'lembur_menit'    => $lemburMenitFinal,
                'lembur_jam'      => $this->bulatkanLemburJam($lemburMenitFinal),
                'lembur_approved' => $correction?->lembur_approved ?? false,
            ];
        }

        return response()->json([
            'detail' => [
                'id'   => $detail->id,
                'nama' => $detail->nama,
                'nip'  => $detail->nip,
            ],
            'rows' => $rows,
        ]);
    }

    // ========================
    // SAVE KOREKSI + RECALCULATE
    // ========================
    public function saveKoreksi(Request $request, $detailId)
    {
        $detail  = PayrollDetail::findOrFail($detailId);
        $payroll = Payroll::findOrFail($detail->payroll_id);

        $pin    = $detail->pin;
        $dari   = $payroll->tanggal_dari->format('Y-m-d');
        $sampai = $payroll->tanggal_sampai->format('Y-m-d');
        $userId = Auth::guard('admin')->id();

        $rows = $request->rows ?? [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $tgl    = $row['tgl'];
                $jamIn  = $row['jam_in']  ?? null;
                $jamOut = $row['jam_out'] ?? null;
                $status = $row['status']  ?? 'H';
                $ket    = $row['keterangan'] ?? null;

                // Jika jam_in dan jam_out kosong dan status H → hapus koreksi (pakai fingerprint), kecuali ada data lembur yang harus disimpan
                $hasLemburData = ($row['lembur_approved'] ?? false) || (($row['lembur_menit'] ?? 0) > 0);
                if (!$jamIn && !$jamOut && $status === 'H' && !$ket && !$hasLemburData) {
                    AttendanceCorrection::where('pin', $pin)->where('tanggal', $tgl)->delete();
                    continue;
                }

                $correctionData = [
                    'jam_in'       => $jamIn  ?: null,
                    'jam_out'      => $jamOut ?: null,
                    'status'       => $status,
                    'keterangan'   => $ket,
                    'edited_by'    => $userId,
                    'lembur_menit' => $row['lembur_menit'] ?? null,
                ];

                if (isset($row['lembur_approved'])) {
                    $correctionData['lembur_approved'] = $row['lembur_approved'] ?? false;
                }

                AttendanceCorrection::updateOrCreate(
                    ['pin' => $pin, 'tanggal' => $tgl],
                    $correctionData
                );
            }

            // RECALCULATE gaji untuk karyawan ini
            $periode = [];
            $cur = new \DateTime($dari);
            $end = new \DateTime($sampai);
            while ($cur <= $end) {
                $periode[] = $cur->format('Y-m-d');
                $cur->modify('+1 day');
            }

            $logs = \App\Models\AttendanceLog::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderBy('datetime')
                ->get()
                ->groupBy(fn($l) => $l->pin . '_' . substr((string)$l->tanggal, 0, 10));

            $absenceNotes = \App\Models\AbsenceNote::where('pin', $pin)
                ->whereBetween('date', [$dari, $sampai])
                ->get()
                ->keyBy(fn($i) => $i->date->format('Y-m-d'));

            $corrections = AttendanceCorrection::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->get()
                ->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d'));

            $hadir = $alpha = $izin = $sakit = $lemburMenit = 0;
            $setengahHari = 0;

            foreach ($periode as $tgl) {
                if (date('N', strtotime($tgl)) == 7) continue;

                $correction = $corrections[$tgl] ?? null;

                if ($correction) {
                    $status = $correction->status;
                } else {
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs[$dayKey] ?? collect();
                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $note   = $absenceNotes[$tgl] ?? null;
                    $status = $inTs ? 'H' : ($note ? $note->code : 'A');
                }

                if ($correction && $correction->lembur_approved) {
                    $lemburMenit += $correction->lembur_menit ?? 0;
                }

                switch ($status) {
                    case 'H': $hadir++; break;
                    case 'A': $alpha++; break;
                    case 'I': $izin++;  break;
                    case 'S': $sakit++; break;
                    case 'ST': $setengahHari++; break;
                }
            }

            $nominal    = $detail->nominal_harian;
            $gajiPokok  = ($hadir + $setengahHari) * $nominal;
            $potonganSt = $setengahHari * ($nominal / 2);
            $totalJamLemburDibulatkan = $this->bulatkanLemburJam($lemburMenit);
            $gajiLembur = floor(($nominal / 8) * 1.5 * $totalJamLemburDibulatkan);
            $totalGaji  = $gajiPokok + $gajiLembur + $detail->tambahan - $detail->potongan - $potonganSt;

            $detail->update([
                'hadir'        => $hadir,
                'alpha'        => $alpha,
                'izin'         => $izin,
                'sakit'        => $sakit,
                'setengah_hari'=> $setengahHari,
                'lembur_menit' => $lemburMenit,
                'gaji_pokok'   => $gajiPokok,
                'gaji_lembur'  => $gajiLembur,
                'total_gaji'   => $totalGaji,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Koreksi berhasil disimpan dan gaji direcalculate.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function bulatkanLemburJam(int $menit): float
    {
        if ($menit <= 0) return 0;
        return round($menit / 30) * 0.5;
    }

    // ========================
    // TOGGLE LEMBUR APPROVAL
    // ========================
    public function toggleLembur($id)
    {
        $detail = PayrollDetail::findOrFail($id);

        $newStatus = !$detail->lembur_approved;
        $detail->lembur_approved = $newStatus;

        // Recalculate total gaji
        $nominal    = $detail->nominal_harian;
        $gajiPokok  = $detail->hadir * $nominal;
        $gajiLembur = $newStatus
            ? floor(($nominal / 8) * 1.5 * ($detail->lembur_menit / 60))
            : 0;
        $totalGaji  = $gajiPokok + $gajiLembur + $detail->tambahan - $detail->potongan;

        $detail->gaji_lembur = $gajiLembur;
        $detail->total_gaji  = $totalGaji;
        $detail->save();

        return response()->json([
            'success'         => true,
            'lembur_approved' => $newStatus,
            'gaji_lembur'     => $gajiLembur,
            'total_gaji'      => $totalGaji,
        ]);
    }
}
