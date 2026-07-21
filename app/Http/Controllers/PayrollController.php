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
use App\Models\BpjsMaster;
use App\Models\KaryawanBank;
use App\Models\OvertimeRequest;
use App\Services\FingerprintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PayrollController extends Controller
{
    private FingerprintService $fp;

    public function __construct(FingerprintService $fp)
    {
        $this->fp = $fp;
    }

    // ========================
    // INDEX — List Payroll
    // ========================
    public function index()
    {
        $payrolls = Payroll::withCount(['details', 'boronganRekaps', 'grandTotals', 'pengajuans'])
            ->withSum('details', 'total_gaji')
            ->withSum('boronganRekaps', 'total_akhir')
            ->withSum('grandTotals', 'total_akhir')
            ->withSum('pengajuans', 'total_akhir')
            ->orderByDesc('tanggal_dari')
            ->get();

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
            ->map(fn($n) => $n->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d')));

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
                $pinCorrections = $corrections->get($pin);
                $correction = $pinCorrections ? ($pinCorrections->get($tgl) ?? null) : null;

                if ($correction) {
                    // Pakai data koreksi
                    $status  = $correction->status;
                    $jamIn   = $correction->jam_in;
                    $jamOut  = $correction->jam_out;
                } else {
                    // Pakai data fingerprint
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs->get($dayKey) ?? collect();

                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

                    $jamIn  = $inTs  ? date('H:i', $inTs)  : null;
                    $jamOut = $outTs ? date('H:i', $outTs) : null;

                    // Status dari absence notes
                    $noteCollection = $absenceNotes->get($pin, collect());
                    $note   = $noteCollection ? ($noteCollection->get($tgl) ?? null) : null;
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
            'tanggal_dari' => 'required|date',
            'tanggal_sampai' => 'required|date|after_or_equal:tanggal_dari',
        ]);

        $dari = $request->tanggal_dari;
        $sampai = $request->tanggal_sampai;

        $periode = \Carbon\Carbon::parse($dari)->format('Y-m') . '-' . (\Carbon\Carbon::parse($dari)->day <= 15 ? '1' : '2');

        $exists = Payroll::where('tanggal_dari', $dari)->where('tanggal_sampai', $sampai)->first();
        if ($exists) {
            return redirect()->route('payroll.show', $exists->id)->with('info', 'Payroll untuk periode ini sudah ada.');
        }

        $payroll = Payroll::create([
            'periode' => $periode,
            'tanggal_dari' => $dari,
            'tanggal_sampai' => $sampai,
            'status' => 'draft',
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('payroll.index')->with('success', 'Payroll berhasil dibuat. Kembali ke daftar payroll.');
    }

    // ========================
    // SHOW — Overview payroll (dashboard dengan jenis status)
    // ========================
    public function show($id)
    {
        $payroll = Payroll::findOrFail($id);

        $periodeTanggal = [];
        $cur = new \DateTime($payroll->tanggal_dari);
        $end = new \DateTime($payroll->tanggal_sampai);
        while ($cur <= $end) {
            $periodeTanggal[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }
        
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

        return view('payroll.show', compact('payroll', 'cabutImport', 'hcrImport', 'mouldingImport', 'harianDetailCount', 'bisaGenerateGrandTotal', 'grandTotals', 'sudahAdaPengajuan', 'periodeTanggal'));
    }

    // ========================
    // EXPORT GRAND TOTAL — Excel export for Grand Total
    // ========================
    public function exportGrandTotal($id)
    {
        $payroll = Payroll::findOrFail($id);
        $grandTotals = PayrollGrandTotal::where('payroll_id', $id)->orderBy('nama')->get();

        $periodeTanggal = [];
        $cur = new \DateTime($payroll->tanggal_dari);
        $end = new \DateTime($payroll->tanggal_sampai);
        while ($cur <= $end) {
            $periodeTanggal[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }

        $sections = [
            'cabut' => 'REKAPITULASI BAGIAN CABUT',
            'moulding' => 'REKAPITULASI BAGIAN MOULDING',
            'harian' => 'REKAPITULASI BAGIAN HARIAN',
        ];

        $sectionGroups = $grandTotals->groupBy(fn($g) => in_array($g->section, ['cabut', 'hcr']) ? 'cabut' : $g->section);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Grand Total');

        $titleStyle = [
            'font' => ['bold' => true],
        ];

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ];

        $dataStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ];

        $row = 1;
        $freezeSet = false;
        foreach ($sections as $sectionKey => $label) {
            $sectionRows = $sectionGroups[$sectionKey] ?? collect();

            $columnCount = 4 + count($periodeTanggal) + 5;
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", $label);
            $sheet->getStyle("A{$row}")->applyFromArray($titleStyle);
            $row++;

            $headers = ['NO', 'NIP', 'NAMA', 'JOB'];
            foreach ($periodeTanggal as $tanggal) {
                $headers[] = \Carbon\Carbon::parse($tanggal)->format('d/m');
            }
            $headers = array_merge($headers, ['KOMPLAIN', 'INSENTIF', 'POT. LAIN', 'POT. BPJS KES', 'TTL PAYROLL']);

            foreach ($headers as $colIndex => $header) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue("{$column}{$row}", $header);
                $sheet->getStyle("{$column}{$row}")->applyFromArray($headerStyle);
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            if (!$freezeSet) {
                $sheet->freezePane('A' . ($row + 1));
                $freezeSet = true;
            }

            $row++;

            foreach ($sectionRows as $index => $g) {
                $detail = json_decode($g->detail_harian, true) ?: [];
                $sheet->setCellValue("A{$row}", $index + 1);
                $sheet->setCellValue("B{$row}", $g->nip);
                $sheet->setCellValue("C{$row}", $g->nama);
                $sheet->setCellValue("D{$row}", $g->job_label);

                $colIndex = 5;
                foreach ($periodeTanggal as $tanggal) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue("{$column}{$row}", $detail[$tanggal] ?? 0);
                    $colIndex++;
                }

                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $g->komplain ?? 0);
                $colIndex++;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $g->insentif ?? 0);
                $colIndex++;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $g->potongan_lain ?? 0);
                $colIndex++;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $g->potongan_bpjs ?? 0);
                $colIndex++;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $row, $g->total_akhir ?? 0);

                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray($dataStyle);
                $row++;
            }

            $row++;
        }

        $bpjsMaster = BpjsMaster::all()->keyBy(fn($b) => trim(strtoupper($b->nip)));
        $boronganImportIds = BoronganImport::where('payroll_id', $id)->pluck('id')->filter()->values()->all();
        $boronganBpjs = BoronganRekap::whereIn('borongan_import_id', $boronganImportIds)
            ->where('potongan_bpjs', '>', 0)
            ->get()
            ->groupBy(fn($r) => trim(strtoupper($r->nip)))
            ->map(fn($group) => $group->sum('potongan_bpjs'));

        $allBpjsNips = $bpjsMaster->keys()->merge($boronganBpjs->keys())->unique();
        $users = User::all()->keyBy(fn($u) => trim(strtoupper($u->nip)));

        $bpjsRows = [];
        foreach ($allBpjsNips as $nipKey) {
            $master = $bpjsMaster[$nipKey] ?? null;
            $masterNominal = $master?->nominal ?? 0;
            $rekapNominal = $boronganBpjs[$nipKey] ?? 0;
            $totalNominal = $masterNominal + $rekapNominal;

            $bpjsRows[] = [
                'nip' => $master?->nip ?? $nipKey,
                'nama' => $users[$nipKey]->nama ?? null,
                'potongan_pbjskes' => $totalNominal,
            ];
        }

        usort($bpjsRows, fn($a, $b) => strcmp($a['nip'], $b['nip']));

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'POTONGAN BPJS KESEHATAN KARYAWAN HARIAN DAN BORONGAN');
        $sheet->getStyle("A{$row}")->applyFromArray($titleStyle);
        $row++;

        $sheet->setCellValue("A{$row}", 'NO');
        $sheet->setCellValue("B{$row}", 'NIP');
        $sheet->setCellValue("C{$row}", 'NAMA');
        $sheet->setCellValue("D{$row}", 'POT. BPJS KES');
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($headerStyle);
        $row++;

        foreach ($bpjsRows as $index => $bpjsRow) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $bpjsRow['nip']);
            $sheet->setCellValue("C{$row}", $bpjsRow['nama']);
            $sheet->setCellValue("D{$row}", $bpjsRow['potongan_pbjskes']);
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($dataStyle);
            $row++;
        }

        foreach (range(1, 30) as $colIndex) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        $filename = 'Grand_Total_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $payroll->periode) . '.xlsx';
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
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

            // Determine gaji_real according to business rule:
            // - For harian section, use PayrollDetail.gaji_pokok (authoritative base pay)
            // - For borongan (cabut/hcr/moulding), use array_sum(detail_harian)
            $section = strtolower((string) ($row->section ?? $row->job_label ?? $row->jenis ?? ''));
            $gajiReal = 0;

            if (stripos($section, 'harian') !== false) {
                $detailModel = PayrollDetail::where('payroll_id', $id)
                    ->where('nip', $row->nip)
                    ->first();
                if ($detailModel) {
                    $gajiReal = (int) $detailModel->gaji_pokok;
                } else {
                    // Fallback: do not include lembur here — keep base as 0 if missing detail
                    $gajiReal = 0;
                }
            } else {
                $detail = json_decode($row->detail_harian, true);
                $gajiReal = is_array($detail) ? array_sum($detail) : 0;
            }

            PayrollPengajuan::create([
                'payroll_id' => $id,
                'nip' => $row->nip,
                'nama' => $row->nama,
                'section' => $row->section ?? null,
                'jenis' => $row->job_label ?? $row->jenis ?? 'harian',
                'gaji_real' => $gajiReal,
                'komplain' => $row->komplain ?? 0,
                'insentif' => $row->insentif ?? 0,
                'potongan_lain' => $row->potongan_lain ?? 0,
                'potongan_bpjs' => $row->potongan_bpjs ?? 0,
                // keep total_lembur separate (used only for harian export)
                'total_lembur' => $row->total_lembur ?? 0,
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
        $pengajuan = PayrollPengajuan::where('payroll_id', $id)
            ->select(['payroll_pengajuan.*', 'total_lembur'])
            ->get()
            ->groupBy('jenis');

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

        // Grouping — skip rows that lack a section and warn instead of guessing
        $cabutRows = [];
        $hcrRows = [];
        $mouldingRows = [];
        $harianRows = [];
        $invalidRows = [];

        foreach ($pengajuan as $row) {
            $section = strtolower((string) ($row->section ?? ''));
            if ($section === '') {
                Log::warning('ExportPengajuan: missing section', ['payroll_id' => $payroll->id, 'nip' => $row->nip, 'jenis' => $row->jenis]);
                $invalidRows[] = $row;
                continue;
            }

            if ($section === 'cabut') {
                $cabutRows[] = $row;
            } elseif ($section === 'hcr') {
                $hcrRows[] = $row;
            } elseif ($section === 'moulding') {
                $mouldingRows[] = $row;
            } elseif ($section === 'harian') {
                $harianRows[] = $row;
            } else {
                // unknown section value — warn and skip
                Log::warning('ExportPengajuan: unknown section value', ['payroll_id' => $payroll->id, 'nip' => $row->nip, 'section' => $section]);
                $invalidRows[] = $row;
            }
        }

        // valid pengajuan set used for subsequent sheets/calculations
        $validPengajuan = collect($cabutRows)->merge($hcrRows)->merge($mouldingRows)->merge($harianRows);

        // Prepare import IDs for borongan rekap sheets
        $cabutImportIds = BoronganImport::where('payroll_id', $id)->where('jenis', 'cabut')->pluck('id')->filter()->values()->all();
        $hcrImportIds   = BoronganImport::where('payroll_id', $id)->where('jenis', 'hcr')->pluck('id')->filter()->values()->all();
        $mouldingImportIds = BoronganImport::where('payroll_id', $id)->where('jenis', 'moulding')->pluck('id')->filter()->values()->all();

        // Date range for per-day breakdown
        $periodDates = [];
        $currentDate = new \DateTime($payroll->tanggal_dari);
        $endDate = new \DateTime($payroll->tanggal_sampai);
        while ($currentDate <= $endDate) {
            $periodDates[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // Cache attendance and corrections for harian breakdown
        $harianNips = PayrollDetail::where('payroll_id', $id)->pluck('pin')->filter()->unique()->values()->all();
        $harianCorrections = AttendanceCorrection::whereIn('pin', $harianNips)
            ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
            ->get()
            ->groupBy(function ($item) {
                return $item->pin . '_' . $item->tanggal->format('Y-m-d');
            });
        $harianLogs = AttendanceLog::whereIn('pin', $harianNips)
            ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
            ->get()
            ->groupBy(function ($item) {
                return $item->pin . '_' . $item->tanggal;
            });
        $absenceNotes = AbsenceNote::whereIn('pin', $harianNips)
            ->whereBetween('date', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
            ->get()
            ->groupBy('pin')
            ->map(function($group) {
                return $group->keyBy(function($it) { return $it->date->format('Y-m-d'); });
            });

        $allNips = $validPengajuan->pluck('nip')->filter()->unique()->values()->all();
        $userInfo = User::whereIn('nip', $allNips)
            ->get(['nip', 'departemen', 'bagian'])
            ->keyBy('nip');

        $groups = [
            'CABUT' => $cabutRows,
            'TITIL HCR' => $hcrRows,
            'MOULDING' => $mouldingRows,
            'HARIAN' => $harianRows,
        ];

        $workingDaysCount = 0;
        $tempDate = new \DateTime($payroll->tanggal_dari);
        while ($tempDate <= $endDate) {
            if ((int) $tempDate->format('N') !== 7) {
                $workingDaysCount++;
            }
            $tempDate->modify('+1 day');
        }

        foreach ($groups as $name => $rows) {
            if (empty($rows)) continue;

            // Create rekap sheet for each section first
            $rekapName = 'Rekap ' . $name;
            $rekapSheet = $spreadsheet->createSheet();
            $rekapSheet->setTitle(substr($rekapName, 0, 31));

            $rekapSheet->mergeCells('A1:Q1');
            $rekapSheet->setCellValue('A1', 'PT WALET ABDILLAH JABLI');
            $rekapSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $rekapSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rekapSheet->mergeCells('A2:Q2');
            $rekapSheet->setCellValue('A2', $rekapName . ' PERIODE ' . $payroll->periode);
            $rekapSheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $rekapSheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rekapSheet->mergeCells('A3:Q3');
            $rekapSheet->setCellValue('A3', 'Periode ' . \Carbon\Carbon::parse($payroll->tanggal_dari)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($payroll->tanggal_sampai)->translatedFormat('d F Y'));
            $rekapSheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            $rekapSheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($name === 'HARIAN') {
                $headers = array_merge(['No', 'NIP', 'Nama', 'Bagian'], array_map(fn($date) => (int) \Carbon\Carbon::parse($date)->format('d'), $periodDates), ['Hadir', 'Alpha', 'Izin', 'Sakit', 'ST']);
            } else {
                $headers = array_merge(['No', 'NIP', 'Nama', 'Jenis'], array_map(fn($date) => (int) \Carbon\Carbon::parse($date)->format('d'), $periodDates), ['Total']);
            }

            $lastHeadCol = $this->colLetter(count($headers));
            $rekapSheet->fromArray($headers, null, 'A5');
            $rekapSheet->getStyle("A5:{$lastHeadCol}5")->getFont()->setBold(true);
            $rekapSheet->getStyle("A5:{$lastHeadCol}5")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
            $rekapSheet->getStyle("A5:{$lastHeadCol}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rekapSheet->getStyle("A5:{$lastHeadCol}5")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rekapSheet->freezePane('A6');

            $dataStartRow = $name === 'HARIAN' ? 6 : 7;
            $rowNum = $dataStartRow;
            $no = 1;
            if ($name === 'HARIAN') {
                $detailRows = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();
                $bagianMap = User::whereIn('nip', $detailRows->pluck('nip'))->pluck('bagian', 'nip');
                foreach ($detailRows as $detail) {
                    $rekapSheet->setCellValue('A' . $rowNum, $no);
                    $rekapSheet->setCellValue('B' . $rowNum, $detail->nip);
                    $rekapSheet->setCellValue('C' . $rowNum, $detail->nama);
                    $rekapSheet->setCellValue('D' . $rowNum, $bagianMap[$detail->nip] ?? '-');

                    $colIndex = 5;
                    $hadirCount = 0;
                    $alphaCount = 0;
                    $izinCount = 0;
                    $sakitCount = 0;
                    $stCount = 0;

                    foreach ($periodDates as $date) {
                        $key = $detail->pin . '_' . $date;
                        $correction = $harianCorrections->get($key) ? $harianCorrections->get($key)->first() : null;
                        $dayLogs = $harianLogs->get($key) ?? collect();
                        $absenceForPin = $absenceNotes->get($detail->pin, collect());
                        $resolved = $this->resolveAttendanceDay($correction, $dayLogs, $absenceForPin, $detail->pin, $date);
                        $status = $resolved['status'] ?? '-';
                        $rekapSheet->setCellValue($this->colLetter($colIndex) . $rowNum, $status);
                        if ($status === 'H') $hadirCount++;
                        if ($status === 'A') $alphaCount++;
                        if ($status === 'I') $izinCount++;
                        if ($status === 'S') $sakitCount++;
                        if ($status === 'ST') $stCount++;
                        $colIndex++;
                    }

                    $rekapSheet->setCellValue($this->colLetter($colIndex++) . $rowNum, $hadirCount);
                    $rekapSheet->setCellValue($this->colLetter($colIndex++) . $rowNum, $alphaCount);
                    $rekapSheet->setCellValue($this->colLetter($colIndex++) . $rowNum, $izinCount);
                    $rekapSheet->setCellValue($this->colLetter($colIndex++) . $rowNum, $sakitCount);
                    $rekapSheet->setCellValue($this->colLetter($colIndex) . $rowNum, $stCount);

                    $rekapSheet->getStyle("A{$rowNum}:{$lastHeadCol}{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $rowNum++;
                    $no++;
                }
            } else {
                $importIds = match ($name) {
                    'CABUT' => $cabutImportIds,
                    'TITIL HCR' => $hcrImportIds,
                    'MOULDING' => $mouldingImportIds,
                    default => [],
                };
                $grouped = collect($rows)->groupBy(fn($item) => trim($item->nip) . '|' . trim($item->jenis));
                foreach ($grouped as $key => $group) {
                    [$nip, $jenis] = explode('|', $key) + ['', ''];
                    $nama = $group->first()->nama ?? '';
                    $rekapSheet->setCellValue('A' . $rowNum, $no);
                    $rekapSheet->setCellValue('B' . $rowNum, $nip);
                    $rekapSheet->setCellValue('C' . $rowNum, $nama);
                    $rekapSheet->setCellValue('D' . $rowNum, $jenis);

                    $dateStartCol = 5;
                    $colIndex = $dateStartCol;
                    foreach ($periodDates as $date) {
                        $dailyValue = BoronganHarian::whereIn('borongan_import_id', $importIds)
                            ->whereRaw('TRIM(UPPER(nip)) = ?', [trim(strtoupper($nip))])
                            ->where('tanggal', $date)
                            ->sum('upah_sistem');
                        $rekapSheet->setCellValue($this->colLetter($colIndex) . $rowNum, $dailyValue);
                        $colIndex++;
                    }
                    $dateEndCol = $colIndex - 1;
                    $dateStartLetter = $this->colLetter($dateStartCol);
                    $dateEndLetter = $this->colLetter($dateEndCol);
                    $komplainCol = $this->colLetter($colIndex++);
                    $insentifCol = $this->colLetter($colIndex++);
                    $potLainCol = $this->colLetter($colIndex++);
                    $potBpjsCol = $this->colLetter($colIndex++);
                    $ttlCol = $this->colLetter($colIndex);

                    $rekapSheet->setCellValue($komplainCol . $rowNum, $group->sum('komplain'));
                    $rekapSheet->setCellValue($insentifCol . $rowNum, $group->sum('insentif'));
                    $rekapSheet->setCellValue($potLainCol . $rowNum, $group->sum('potongan_lain'));
                    $rekapSheet->setCellValue($potBpjsCol . $rowNum, $group->sum('potongan_bpjs'));
                    $rekapSheet->setCellValue($ttlCol . $rowNum, "=SUM({$dateStartLetter}{$rowNum}:{$dateEndLetter}{$rowNum})+{$komplainCol}{$rowNum}+{$insentifCol}{$rowNum}-{$potLainCol}{$rowNum}-{$potBpjsCol}{$rowNum}");
                    $rekapSheet->getStyle("{$dateStartLetter}{$rowNum}:{$ttlCol}{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                    $rekapSheet->getStyle("A{$rowNum}:{$lastHeadCol}{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $rowNum++;
                    $no++;
                }
            }

            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastHeadCol);
            for ($i = 2; $i <= $maxColIndex; $i++) {
                $rekapSheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
            }
            $rekapSheet->getColumnDimension('A')->setAutoSize(false);
            $rekapSheet->getColumnDimension('A')->setWidth(5);

            // Create standard detail sheet after rekap
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($name, 0, 31));

            $isHarian = strtoupper($name) === 'HARIAN';
            $lastCol = 'P';

            $sheet->mergeCells('A1:P1');
            $sheet->setCellValue('A1', 'PT WALET ABDILLAH JABLI');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:P2');
            $sheet->setCellValue('A2', $isHarian ? 'REKAP GAJI HARIAN PERIODE ' . $payroll->periode : 'REKAP GAJI BORONGAN ' . strtoupper($name) . ' PERIODE ' . $payroll->periode);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A3:P3');
            $sheet->setCellValue('A3', 'Periode ' . \Carbon\Carbon::parse($payroll->tanggal_dari)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($payroll->tanggal_sampai)->translatedFormat('d F Y'));
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($isHarian) {
                $headers = ['No', 'NIP', 'NAMA', 'Departemen', 'Workstation', 'Posisi', 'Gaji Real', 'Gaji Lembur', 'Komplain', 'Insentif', 'Potongan Lain', 'Potongan BPJS', 'TF PAYROL', 'No Rekening', 'Bank', 'Email'];
            } else {
                $headers = ['No', 'NIP', 'NAMA', 'Departemen', 'Workstation', 'Posisi', 'Gaji Real', 'Komplain', 'Tamb. Training', 'Insentif', 'Potongan Lain', 'Potongan BPJS', 'TF PAYROL', 'No Rekening', 'Bank', 'Email'];
            }

            $headerRange = 'A5:P5';
            $sheet->fromArray($headers, null, 'A5');
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->freezePane('A6');
            $sheet->setShowGridlines(false);

            $sectionLabel = match ($name) {
                'CABUT' => 'Cabut',
                'TITIL HCR' => 'Titil Hcr',
                'MOULDING' => 'Moulding',
                default => ucfirst(strtolower($name)),
            };
            $isHarian = strtoupper($name) === 'HARIAN';
            $dataStartRow = $isHarian ? 6 : 7;
            if (!$isHarian) {
                $sheet->setCellValue('A' . ($dataStartRow - 1), "Workstation : {$sectionLabel}, " . count($rows) . " Karyawan");
                $sheet->getStyle('A' . ($dataStartRow - 1))->getFont()->setBold(true);
            }

            $rowNum = $dataStartRow;
            $no = 1;
            foreach ($rows as $r) {
                $nipTrim = trim($r->nip);
                $userRow = $userInfo->get($nipTrim);
                $departemen = $userRow->departemen ?? 'PRODUKSI';
                $posisi = $userRow->bagian ?? '-';
                $workstation = strtoupper($name === 'TITIL HCR' ? 'HCR' : $name);

                $sheet->setCellValue('A' . $rowNum, $no);
                $sheet->setCellValue('B' . $rowNum, $r->nip);
                $sheet->setCellValue('C' . $rowNum, $r->nama);
                $sheet->setCellValue('D' . $rowNum, $departemen);
                $sheet->setCellValue('E' . $rowNum, $workstation);
                $sheet->setCellValue('F' . $rowNum, $posisi);

                if ($isHarian) {
                    $sheet->setCellValue('G' . $rowNum, $r->gaji_real ?? 0);
                    $sheet->setCellValue('H' . $rowNum, $r->total_lembur ?? 0);
                    $sheet->setCellValue('I' . $rowNum, $r->komplain ?? 0);
                    $sheet->setCellValue('J' . $rowNum, $r->insentif ?? 0);
                    $sheet->setCellValue('K' . $rowNum, $r->potongan_lain ?? 0);
                    $sheet->setCellValue('L' . $rowNum, $r->potongan_bpjs ?? 0);
                    $sheet->setCellValue('M' . $rowNum, $r->total_akhir ?? 0);
                    $acct = $r->no_rekening ?? '';
                    $sheet->setCellValue('N' . $rowNum, $acct !== '' ? '\'' . $acct : '');
                    $sheet->setCellValue('O' . $rowNum, $r->nama_bank ?? '');
                    $sheet->setCellValue('P' . $rowNum, $r->email ?? '');
                    $sheet->getStyle("G{$rowNum}:M{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                } else {
                    $rawParsed = BoronganHarian::whereIn('borongan_import_id', $importIds)
                        ->whereRaw('TRIM(UPPER(nip)) = ?', [trim(strtoupper($nipTrim))])
                        ->sum('upah_sistem');
                    $storedGajiReal = $r->gaji_real ?? 0;
                    $tambTraining = max(0, $storedGajiReal - $rawParsed);
                    $gajiRealDisplay = $tambTraining > 0 ? $rawParsed : $storedGajiReal;

                    $sheet->setCellValue('G' . $rowNum, $gajiRealDisplay);
                    $sheet->setCellValue('H' . $rowNum, $r->komplain ?? 0);
                    $sheet->setCellValue('I' . $rowNum, $tambTraining);
                    $sheet->setCellValue('J' . $rowNum, $r->insentif ?? 0);
                    $sheet->setCellValue('K' . $rowNum, $r->potongan_lain ?? 0);
                    $sheet->setCellValue('L' . $rowNum, $r->potongan_bpjs ?? 0);
                    $sheet->setCellValue('M' . $rowNum, $r->total_akhir ?? 0);
                    $acct = $r->no_rekening ?? '';
                    $sheet->setCellValue('N' . $rowNum, $acct !== '' ? '\'' . $acct : '');
                    $sheet->setCellValue('O' . $rowNum, $r->nama_bank ?? '');
                    $sheet->setCellValue('P' . $rowNum, $r->email ?? '');
                    $sheet->getStyle("G{$rowNum}:M{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                }

                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $rowNum++;
                $no++;
            }

            $lastDataRow = $rowNum - 1;
            $totalRow = $lastDataRow + 1;
            $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true);

            $colsToSum = ['G', 'H', 'I', 'J', 'K', 'L', 'M'];
            foreach ($colsToSum as $col) {
                $sheet->setCellValue($col . $totalRow, "=SUM({$col}{$dataStartRow}:{$col}{$lastDataRow})");
                $sheet->getStyle($col . $totalRow)->getFont()->setBold(true);
                $sheet->getStyle($col . $totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
                $sheet->getStyle($col . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
            }

            $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->setAutoFilter("A5:{$lastCol}{$lastDataRow}");
            for ($i = 2; $i <= 16; $i++) {
                $sheet->getColumnDimension($this->colLetter($i))->setAutoSize(true);
            }
            $sheet->getColumnDimension('A')->setAutoSize(false);
            $sheet->getColumnDimension('A')->setWidth(5);

            $signRow = $totalRow + 3;
            $sheet->setCellValue('A' . $signRow, 'Dibuat Oleh');
            $sheet->setCellValue('D' . $signRow, 'Di Periksa Oleh');
            $sheet->setCellValue('G' . $signRow, 'Di Ketahui Oleh');
            $sheet->setCellValue('J' . $signRow, 'Di Setujui Oleh');
            $signRow += 3;
            $sheet->setCellValue('A' . $signRow, 'Khusnul Fatimah');
            $sheet->setCellValue('D' . $signRow, 'Ratna Suminar');
            $sheet->setCellValue('G' . $signRow, 'Patrick Justin');
            $sheet->setCellValue('J' . $signRow, 'Djunita');
            $signRow++;
            $sheet->setCellValue('A' . $signRow, 'Adm Payroll');
            $sheet->setCellValue('D' . $signRow, 'Finance Accounting');
            $sheet->setCellValue('G' . $signRow, 'General Manager');
            $sheet->setCellValue('J' . $signRow, 'Direktur');
        }

        // BELUM ADA REKENING sheet (only include valid pengajuan rows)
        $noBank = $validPengajuan->filter(function($p) { return empty($p->no_rekening); })->values();
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

        // ========================
        // RESUM Sheet — summary / recap
        // ========================
        // Build RESUM from valid pengajuan and BoronganRekap totals
        $resumSheet = $spreadsheet->createSheet();
        $resumSheet->setTitle('RESUM');

        // Title
        $resumSheet->mergeCells('A1:N1');
        $resumSheet->setCellValue('A1', 'REKAPITULASI PENGAJUAN GAJI');
        $resumSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $resumSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;

        // SECTION 1 - REKAPITALISASI GAJI (two side-by-side tables)
        $resumSheet->setCellValue('A' . $row, 'REKAPITALISASI GAJI');
        $resumSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 1;

        // Group pengajuan by jenis (job_label)
        $groupsByJenis = $validPengajuan->groupBy(fn($p) => trim((string) ($p->jenis ?? '')))->map(function($g) {
            return $g->values();
        });

        // Prepare ordered list: put harian groups first, then borongan
        $groupList = [];
        foreach ($groupsByJenis as $jobLabel => $group) {
            $section = strtolower((string) ($group->first()->section ?? ''));
            $groupList[] = [
                'label' => $jobLabel,
                'group' => $group,
                'section' => $section,
            ];
        }
        usort($groupList, function($a, $b) {
            if ($a['section'] === 'harian' && $b['section'] !== 'harian') return -1;
            if ($b['section'] === 'harian' && $a['section'] !== 'harian') return 1;
            return strcmp($a['label'], $b['label']);
        });

        $totalGroups = count($groupList);
        $half = (int) ceil($totalGroups / 2);
        $leftGroups = array_slice($groupList, 0, $half);
        $rightGroups = array_slice($groupList, $half);

        // table headers
        $leftCol = 'A'; $rightCol = 'H';
        $headers = ['REKAPITALISASI GAJI', 'QTY MP', 'STATUS', 'QTY', 'NOMINAL'];
        $leftBaseIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($leftCol);
        $rightBaseIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($rightCol);
        $leftHeaderEndCol = $this->colLetter($leftBaseIndex + count($headers) - 1);
        $rightHeaderEndCol = $this->colLetter($rightBaseIndex + count($headers) - 1);
        $resumSheet->fromArray($headers, null, $leftCol . $row);
        $resumSheet->fromArray($headers, null, $rightCol . $row);
        $resumSheet->getStyle($leftCol . $row . ':' . $leftHeaderEndCol . $row)->getFont()->setBold(true);
        $resumSheet->getStyle($rightCol . $row . ':' . $rightHeaderEndCol . $row)->getFont()->setBold(true);
        $row++;

        $leftRow = $row; $rightRow = $row;
        foreach ($leftGroups as $g) {
            $label = $g['label'];
            $members = $g['group'];
            $qtyMp = $members->count();
            $status = $g['section'] === 'harian' ? 'Harian' : 'Borongan';
            $qtyText = '-';
            if ($status === 'Borongan') {
                $nips = $members->pluck('nip')->filter()->unique()->values()->all();
                $totalGram = BoronganRekap::join('borongan_imports', 'borongan_rekap.borongan_import_id', '=', 'borongan_imports.id')
                    ->where('borongan_imports.payroll_id', $payroll->id)
                    ->where('borongan_imports.jenis', $g['section'])
                    ->whereIn('borongan_rekap.nip', $nips)
                    ->sum('borongan_rekap.total_gram');
                $qtyText = 'QTY ' . strtoupper($label) . ' ' . number_format($totalGram, 0, ',', '.');
            }
            $nominal = $members->sum('total_akhir');

            $resumSheet->setCellValue($leftCol . $leftRow, $label);
            $resumSheet->setCellValue($this->colLetter($leftBaseIndex + 1) . $leftRow, $qtyMp);
            $resumSheet->setCellValue($this->colLetter($leftBaseIndex + 2) . $leftRow, $status);
            $resumSheet->setCellValue($this->colLetter($leftBaseIndex + 3) . $leftRow, $qtyText);
            $resumSheet->setCellValue($this->colLetter($leftBaseIndex + 4) . $leftRow, $nominal);

            $leftRow++;
        }

        foreach ($rightGroups as $g) {
            $label = $g['label'];
            $members = $g['group'];
            $qtyMp = $members->count();
            $status = $g['section'] === 'harian' ? 'Harian' : 'Borongan';
            $qtyText = '-';
            if ($status === 'Borongan') {
                $nips = $members->pluck('nip')->filter()->unique()->values()->all();
                $totalGram = BoronganRekap::join('borongan_imports', 'borongan_rekap.borongan_import_id', '=', 'borongan_imports.id')
                    ->where('borongan_imports.payroll_id', $payroll->id)
                    ->where('borongan_imports.jenis', $g['section'])
                    ->whereIn('borongan_rekap.nip', $nips)
                    ->sum('borongan_rekap.total_gram');
                $qtyText = 'QTY ' . strtoupper($label) . ' ' . number_format($totalGram, 0, ',', '.');
            }
            $nominal = $members->sum('total_akhir');

            $resumSheet->setCellValue($rightCol . $rightRow, $label);
            $resumSheet->setCellValue($this->colLetter($rightBaseIndex + 1) . $rightRow, $qtyMp);
            $resumSheet->setCellValue($this->colLetter($rightBaseIndex + 2) . $rightRow, $status);
            $resumSheet->setCellValue($this->colLetter($rightBaseIndex + 3) . $rightRow, $qtyText);
            $resumSheet->setCellValue($this->colLetter($rightBaseIndex + 4) . $rightRow, $nominal);

            $rightRow++;
        }

        $endRow = max($leftRow, $rightRow);
        // TOTAL row below both tables
        $totalRow = $endRow + 1;
        $resumSheet->mergeCells($leftCol . $totalRow . ':' . $this->colLetter($leftBaseIndex + 2) . $totalRow);
        $resumSheet->setCellValue($leftCol . $totalRow, 'TOTAL');
        $leftNominalCol = $this->colLetter($leftBaseIndex + 4);
        $rightNominalCol = $this->colLetter($rightBaseIndex + 4);
        $resumSheet->setCellValue($leftNominalCol . $totalRow, "=SUM({$leftNominalCol}{$row}:{$leftNominalCol}" . ($endRow - 1) . ") + SUM({$rightNominalCol}{$row}:{$rightNominalCol}" . ($endRow - 1) . ")");
        $resumSheet->getStyle($leftCol . $totalRow . ':' . $rightNominalCol . $totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');

        $currentRow = $totalRow + 2;

        // SECTION 2 - TOTAL PENGAJUAN GAJI by bank
        $resumSheet->setCellValue('A' . $currentRow, 'TOTAL PENGAJUAN GAJI');
        $resumSheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow++;

        $bankGroups = $pengajuan->groupBy(fn($p) => trim((string) ($p->nama_bank ?: 'TUNAI')));
        $resumSheet->fromArray(['Bank', 'Total'], null, 'A' . $currentRow);
        $resumSheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFont()->setBold(true);
        $currentRow++;
        $startBankRow = $currentRow;
        foreach ($bankGroups as $bankName => $group) {
            $resumSheet->setCellValue('A' . $currentRow, $bankName ?: 'TUNAI');
            $resumSheet->setCellValue('B' . $currentRow, $group->sum('total_akhir'));
            $currentRow++;
        }
        $resumSheet->setCellValue('A' . $currentRow, 'TOTAL');
        $resumSheet->setCellValue('B' . $currentRow, "=SUM(B{$startBankRow}:B" . ($currentRow-1) . ")");
        $resumSheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');

        $currentRow += 2;

        // SECTION 3 - Rata-rata per hari
        $dari = \Carbon\Carbon::parse($payroll->tanggal_dari);
        $sampai = \Carbon\Carbon::parse($payroll->tanggal_sampai);
        $masaAktifHariKerja = $dari->diffInDays($sampai) + 1; // inclusive calendar days — adjust if needs business days
        $resumSheet->setCellValue('A' . $currentRow, 'Rata-rata per hari');
        $resumSheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow++;
        $sumCabut = $validPengajuan->where('section', 'cabut')->sum('total_akhir');
        $sumHcr = $validPengajuan->where('section', 'hcr')->sum('total_akhir');
        $sumMoulding = $validPengajuan->where('section', 'moulding')->sum('total_akhir');

        $resumSheet->setCellValue('A' . $currentRow, 'Rata Cabut Per Hari');
        $resumSheet->setCellValue('B' . $currentRow, $sumCabut / max(1, $masaAktifHariKerja));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Rata HCR Per Hari');
        $resumSheet->setCellValue('B' . $currentRow, $sumHcr / max(1, $masaAktifHariKerja));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Rata Cetak & GPU Per Hari');
        $resumSheet->setCellValue('B' . $currentRow, $sumMoulding / max(1, $masaAktifHariKerja));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, "Noted: Masa Aktif {$masaAktifHariKerja} hari Kerja");
        $currentRow += 2;

        // SECTION 4 - TOTAL PENGAJUAN GAJI by section
        $resumSheet->setCellValue('A' . $currentRow, 'TOTAL PENGAJUAN GAJI (by section)');
        $resumSheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Harian');
        $resumSheet->setCellValue('B' . $currentRow, $validPengajuan->where('section', 'harian')->sum('total_akhir'));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Borongan Titil HCR');
        $resumSheet->setCellValue('B' . $currentRow, $validPengajuan->where('section', 'hcr')->sum('total_akhir'));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Borongan Cabut');
        $resumSheet->setCellValue('B' . $currentRow, $validPengajuan->where('section', 'cabut')->sum('total_akhir'));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Borongan Cetak');
        $resumSheet->setCellValue('B' . $currentRow, $validPengajuan->where('section', 'moulding')->sum('total_akhir'));
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Total');
        $resumSheet->setCellValue('B' . $currentRow, "=SUM(B" . ($currentRow-4) . ":B" . ($currentRow-1) . ")");
        $resumSheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
        $currentRow += 3;

        // SECTION 5 - Signature block
        $resumSheet->setCellValue('A' . $currentRow, 'Lamongan, ' . now()->translatedFormat('j F Y'));
        $currentRow += 2;
        $resumSheet->setCellValue('A' . $currentRow, 'Dibuat Oleh');
        $resumSheet->setCellValue('D' . $currentRow, 'Di Periksa Oleh');
        $resumSheet->setCellValue('G' . $currentRow, 'Di Ketahui Oleh');
        $resumSheet->setCellValue('J' . $currentRow, 'Di Setujui Oleh');
        $currentRow += 4;
        $resumSheet->setCellValue('A' . $currentRow, 'Khusnul Fatimah');
        $resumSheet->setCellValue('D' . $currentRow, 'Ratna Suminar');
        $resumSheet->setCellValue('G' . $currentRow, 'Patrick Justin');
        $resumSheet->setCellValue('J' . $currentRow, 'Djunita');
        $currentRow++;
        $resumSheet->setCellValue('A' . $currentRow, 'Adm Payroll');
        $resumSheet->setCellValue('D' . $currentRow, 'Finance Accounting');
        $resumSheet->setCellValue('G' . $currentRow, 'General Manager');
        $resumSheet->setCellValue('J' . $currentRow, 'Direktur');

        // Auto-size some columns
        foreach (range('A', 'N') as $c) {
            $resumSheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Save to temporary file
        $fileNameSafe = preg_replace('/[\/\\\s]+/', '_', $payroll->periode);
        $fileName = "Pengajuan_Gaji_{$fileNameSafe}.xlsx";
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return response()->download($tmpFile, $fileName)->deleteFileAfterSend(true);
    }

    private function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    private function resolveAttendanceDay($correction, $dayLogs, $absenceNotes, $pin, $tgl)
    {
        $jamOut = null;
        $status = null;

        $lemburMenit = 0;

        if ($correction) {
            $jamIn = $correction->jam_in;
            $jamOut = $correction->jam_out;
            $status = $correction->status;
            $lemburMenit = $correction->lembur_approved ? intval($correction->lembur_menit ?? 0) : 0;

            $hasLemburData = ($lemburMenit > 0) || (!empty($correction->lembur_approved)) || (($correction->lembur_menit ?? 0) > 0);
            $isBlankHCorrection = !$jamIn && !$jamOut && $status === 'H' && empty($correction->keterangan) && !$hasLemburData;
            if ($isBlankHCorrection) {
                $correction = null;
                $lemburMenit = 0;
            }
        }

        if (!$correction) {
            $inTimes = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string) $l->datetime));
            $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string) $l->datetime));
            $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
            $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
            $jamOut = $outTs ? date('H:i', $outTs) : null;

            if (is_array($absenceNotes)) {
                $note = $absenceNotes[$tgl] ?? null;
            } else {
                $note = $absenceNotes->get($tgl) ?? null;
            }
            $status = ($inTs || $outTs) ? 'H' : ($note ? $note->code : 'A');
        }

        return ['status' => $status, 'jam_out' => $jamOut, 'lembur_menit' => $lemburMenit];
    }

    public function tarikAbsensi($id)
    {
        $payroll = Payroll::findOrFail($id);
        $dari = $payroll->tanggal_dari->format('Y-m-d');
        $sampai = $payroll->tanggal_sampai->format('Y-m-d');

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
            ->map(fn($n) => $n->keyBy(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d')));

        $periode = [];
        $cur = new \DateTime($dari);
        $end = new \DateTime($sampai);
        while ($cur <= $end) {
            $periode[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }

        $updated = 0;

        foreach ($karyawan as $k) {
            $pin = (string) intval($k->pin);
            $nip = $k->nip;
            $config = $salaryConfigs[$nip] ?? null;
            $nominal = $config ? $config->nominal : 0;

            $hadir = $alpha = $izin = $sakit = $lemburMenit = $setengahHari = 0;
            $existingDetail = PayrollDetail::where('payroll_id', $payroll->id)
                ->where('pin', $pin)
                ->first();
            $tambahan = intval($existingDetail->tambahan ?? 0);
            $potongan = intval($existingDetail->potongan ?? 0);

            foreach ($periode as $tgl) {
                if (date('N', strtotime($tgl)) == 7) continue;

                $pinCorrections = $corrections->get($pin);
                $correction = $pinCorrections ? ($pinCorrections->get($tgl) ?? null) : null;
                $dayKey = $pin . '_' . $tgl;
                $dayLogs = $logs->get($dayKey) ?? collect();
                $absenceForPin = $absenceNotes->get($pin, collect());
                $resolved = $this->resolveAttendanceDay($correction, $dayLogs, $absenceForPin, $pin, $tgl);
                $status = $resolved['status'];
                $jamOut = $resolved['jam_out'];
                $resolvedLemburMenit = intval($resolved['lembur_menit'] ?? 0);

                if ($resolvedLemburMenit > 0) {
                    $lemburMenit += $resolvedLemburMenit;
                } elseif ($jamOut) {
                    $outTs = strtotime($tgl . ' ' . $jamOut);
                    $threshold = strtotime($tgl . ' 16:30:00');
                    if ($outTs > $threshold) {
                        $lemburMenit += floor(($outTs - $threshold) / 60);
                    }
                }

                switch ($status) {
                    case 'H': $hadir++; break;
                    case 'A': $alpha++; break;
                    case 'I': $izin++; break;
                    case 'S': $sakit++; break;
                    case 'ST': $setengahHari++; break;
                }
            }

            $gajiPokok = ($hadir + $setengahHari) * $nominal;
            $potonganSt = $setengahHari * ($nominal / 2);
            // For payroll pay, use approved overtime minutes from OvertimeRequest
            $approvedMinutes = (int) OvertimeRequest::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->sum('lembur_menit');
            $totalJamLemburDibulatkan = $this->bulatkanLemburJam($approvedMinutes);
            $gajiLembur = $approvedMinutes > 0 ? floor(($nominal / 8) * 1.5 * $totalJamLemburDibulatkan) : 0;
            $totalGaji = $gajiPokok + $gajiLembur + $tambahan - $potongan - $potonganSt;

            PayrollDetail::updateOrCreate(
                ['payroll_id' => $payroll->id, 'pin' => $pin],
                [
                    'nip' => $nip,
                    'nama' => $k->nama,
                    'nominal_harian' => $nominal,
                    'hadir' => $hadir,
                    'alpha' => $alpha,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'setengah_hari' => $setengahHari,
                    'lembur_menit' => $lemburMenit,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_lembur' => $gajiLembur,
                    'tambahan' => $tambahan,
                    'potongan' => $potongan,
                    'total_gaji' => $totalGaji,
                ]
            );

            $updated++;
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // ========================
    // SHOW HARIAN — Detail payroll (tabel detail harian)
    // ========================
    public function showHarian($id)
    {
        $payroll = Payroll::findOrFail($id);
        $details = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();

        $bagianMap = User::whereIn('nip', $details->pluck('nip'))->pluck('bagian', 'nip');
        
        foreach ($details as $d) {
            $upahPerJam = $d->nominal_harian / 8;
            $d->potensi_lembur = floor($upahPerJam * 1.5 * ($d->lembur_menit / 60));
            $d->bagian = $bagianMap[$d->nip] ?? '-';
        }
        
        return view('payroll.harian.show', compact('payroll', 'details'));
    }

    public function generateGrandTotal(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        $force = $request->boolean('force');

        $cabutImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'cabut')
            ->get();
        $hcrImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'hcr')
            ->get();
        $mouldingImports = BoronganImport::where('payroll_id', $id)
            ->where('jenis', 'moulding')
            ->get();

        $warnings = [];
        foreach (['cabut' => $cabutImports, 'hcr' => $hcrImports, 'moulding' => $mouldingImports] as $type => $imports) {
            $belumApproved = $imports->where('status', '!=', 'approved')->count();
            if ($belumApproved > 0) {
                $warnings[] = "{$belumApproved} import jenis {$type} belum di-approve";
            }
        }
        if ($payroll->status !== 'final') {
            $warnings[] = 'Periode belum difinalisasi (masih status ' . $payroll->status . ')';
        }

        if (!empty($warnings) && !$force) {
            $msg = 'Perhatian: ' . implode('; ', $warnings) . '. Yakin tetap generate?';
            if ($request->expectsJson()) {
                return response()->json(['need_confirmation' => true, 'warnings' => $warnings, 'message' => $msg], 409);
            }
            return back()->with('warning_grand_total', $warnings);
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

        $bpjsMasterByNip = BpjsMaster::all()
            ->keyBy(fn($b) => trim(strtoupper($b->nip)));

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
                ->where('total_upah', '>', 0)
                ->exists();

            if ($adaCabutHcr) {
                $rekapByJenis = BoronganRekap::whereIn('borongan_rekap.borongan_import_id', $cabutHcrImportIds)
                    ->whereRaw('TRIM(UPPER(borongan_rekap.nip)) = ?', [$nip])
                    ->join('borongan_imports', 'borongan_rekap.borongan_import_id', '=', 'borongan_imports.id')
                    ->selectRaw('borongan_imports.jenis, SUM(borongan_rekap.total_gram) as total_gram_jenis')
                    ->groupBy('borongan_imports.jenis')
                    ->orderByDesc('total_gram_jenis')
                    ->first();

                if ($rekapByJenis?->jenis === 'hcr') {
                    $section = 'hcr';
                    $jobLabel = 'Titil Hcr';
                } else {
                    $section = 'cabut';
                    $jobLabel = 'Cabut';
                }
            } else {
                $adaMoulding = BoronganRekap::whereIn('borongan_import_id', $mouldingImportIds)
                    ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
                    ->where('total_upah', '>', 0)
                    ->exists();

                if ($adaMoulding) {
                    $section = 'moulding';
                    $jobLabel = 'Moulding';
                }
            }

            $sectionImportIds = match ($section) {
                'cabut', 'hcr' => $cabutHcrImportIds,
                'moulding' => $mouldingImportIds,
                default => [],
            };

            $rekapQuery = BoronganRekap::whereIn('borongan_import_id', $sectionImportIds)
                ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip]);

            $gajiPokokTotal = 0;

            if ($rekapQuery->exists()) {
                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $tanggal = $currentDate->format('Y-m-d');

                    $dailyBorongan = BoronganHarian::whereIn('borongan_import_id', $sectionImportIds)
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
                    $correction = $corrections->get($tanggal) ?? null;
                    $isHadir = false;

                    if ($correction) {
                        $isHadir = in_array($correction->status, ['H', 'ST']) || $correction->lembur_approved;
                    } else {
                        $dayLogs = $logs->get($tanggal) ?? collect();
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
                $gajiPokokTotal = $payrollDetail->gaji_pokok ?? 0;
                $totalLembur = $payrollDetail->gaji_lembur ?? 0;
                $tambahanHarian = $payrollDetail->tambahan ?? 0;
                $potonganHarian = $payrollDetail->potongan ?? 0;
                $potonganStHarian = ($payrollDetail->setengah_hari ?? 0) * (($payrollDetail->nominal_harian ?? 0) / 2);

                $currentDate = clone $dateFrom;
                while ($currentDate <= $dateTo) {
                    $tanggal = $currentDate->format('Y-m-d');

                    if (in_array($tanggal, $hariHadirList)) {
                        $detailHarianGram[$tanggal] = $gajiPerHari;
                    } else {
                        $detailHarianGram[$tanggal] = 0;
                    }

                    $currentDate->modify('+1 day');
                }
            }

            $insentif = $rekapQuery->sum('tambahan');
            $komplain = $rekapQuery->sum('komplain');
            $potonganLain = $rekapQuery->sum('potongan_lain');
            if (!($rekapQuery->exists())) {
                $insentif += $tambahanHarian ?? 0;
                $potonganLain += ($potonganHarian ?? 0) + ($potonganStHarian ?? 0);
            }
            $potonganBpjsRekap = $rekapQuery->sum('potongan_bpjs');
            $potonganBpjsMaster = $bpjsMasterByNip[$nip]->nominal ?? 0;
            $potonganBpjs = $potonganBpjsRekap + $potonganBpjsMaster;

            $totalAkhir = $rekapQuery->exists()
                ? array_sum($detailHarianGram) + $totalLembur + $insentif + $komplain - $potonganLain - $potonganBpjs
                : $gajiPokokTotal + $totalLembur + $insentif + $komplain - $potonganLain - $potonganBpjs;

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
    public function syncAllDetails($id)
    {
        $payroll = Payroll::findOrFail($id);

        $details = PayrollDetail::where('payroll_id', $id)->get();
        $updated = 0;

        foreach ($details as $detail) {
            $pin = $detail->pin;
            $payrollDetail = PayrollDetail::find($detail->id);
            if (!$payrollDetail) {
                continue;
            }

            $dari = $payroll->tanggal_dari->format('Y-m-d');
            $sampai = $payroll->tanggal_sampai->format('Y-m-d');
            $periode = [];
            $cur = new \DateTime($dari);
            $end = new \DateTime($sampai);
            while ($cur <= $end) {
                $periode[] = $cur->format('Y-m-d');
                $cur->modify('+1 day');
            }

            $logs = AttendanceLog::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->orderBy('datetime')
                ->get()
                ->groupBy(fn($l) => $l->pin . '_' . substr((string) $l->tanggal, 0, 10));

            $absenceNotes = AbsenceNote::where('pin', $pin)
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

                $correction = $corrections->get($tgl) ?? null;
                $dayKey = $pin . '_' . $tgl;
                $dayLogs = $logs->get($dayKey) ?? collect();
                $resolved = $this->resolveAttendanceDay($correction, $dayLogs, $absenceNotes, $pin, $tgl);
                $status = $resolved['status'];
                $jamOut = $resolved['jam_out'];
                $resolvedLemburMenit = intval($resolved['lembur_menit'] ?? 0);

                if ($resolvedLemburMenit > 0) {
                    $lemburMenit += $resolvedLemburMenit;
                } elseif ($jamOut) {
                    $outTs = strtotime($tgl . ' ' . $jamOut);
                    $threshold = strtotime($tgl . ' 16:30:00');
                    if ($outTs > $threshold) {
                        $lemburMenit += floor(($outTs - $threshold) / 60);
                    }
                }

                switch ($status) {
                    case 'H': $hadir++; break;
                    case 'A': $alpha++; break;
                    case 'I': $izin++; break;
                    case 'S': $sakit++; break;
                    case 'ST': $setengahHari++; break;
                }
            }

            // For payroll pay, use approved overtime minutes from OvertimeRequest as single source of truth
            $lemburMenit = (int) \App\Models\OvertimeRequest::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->sum('lembur_menit');

            $nominal = $payrollDetail->nominal_harian;
            $gajiPokok = ($hadir + $setengahHari) * $nominal;
            $potonganSt = $setengahHari * ($nominal / 2);
            $totalJamLemburDibulatkan = $this->bulatkanLemburJam($lemburMenit);
            $gajiLembur = floor(($nominal / 8) * 1.5 * $totalJamLemburDibulatkan);
            $totalGaji = $gajiPokok + $gajiLembur + $payrollDetail->tambahan - $payrollDetail->potongan - $potonganSt;

            $payrollDetail->update([
                'hadir' => $hadir,
                'alpha' => $alpha,
                'izin' => $izin,
                'sakit' => $sakit,
                'setengah_hari' => $setengahHari,
                'lembur_menit' => $lemburMenit,
                'gaji_pokok' => $gajiPokok,
                'gaji_lembur' => $gajiLembur,
                'total_gaji' => $totalGaji,
            ]);

            $updated++;
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }

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
        $grandTotals = PayrollGrandTotal::where('payroll_id', $id)->orderBy('nama')->get();

        if ($grandTotals->isEmpty()) {
            return back()->with('error', 'Grand Total belum di-generate. Slip Gaji memerlukan detail per tanggal.');
        }

        $dari = \Carbon\Carbon::parse($payroll->tanggal_dari);
        $sampai = \Carbon\Carbon::parse($payroll->tanggal_sampai);

        $dates = [];
        $current = $dari->copy();
        while ($current->lte($sampai)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $bulanIndo = [
            'January' => 'JANUARI','February' => 'FEBRUARI','March' => 'MARET','April' => 'APRIL',
            'May' => 'MEI','June' => 'JUNI','July' => 'JULI','August' => 'AGUSTUS',
            'September' => 'SEPTEMBER','October' => 'OKTOBER','November' => 'NOVEMBER','December' => 'DESEMBER',
        ];
        $bulanNama = $bulanIndo[$dari->format('F')] ?? strtoupper($dari->format('F'));
        $periodeLabel = 'TGL ' . $dari->format('d') . '-' . $sampai->format('d') . ' ' . $bulanNama . ' ' . $dari->format('Y');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setName('Calibri');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Slip Gaji');

        $slipPerRow = 5;
        $colsPerSlip = 5;
        $colSeparator = 1;
        $colStep = $colsPerSlip + $colSeparator;

        $colLetter = function (int $colIndex): string {
            $letters = '';
            $colIndex++;
            while ($colIndex > 0) {
                $mod = ($colIndex - 1) % 26;
                $letters = chr(65 + $mod) . $letters;
                $colIndex = (int)(($colIndex - $mod) / 26);
            }
            return $letters;
        };

        $slips = [];
        foreach ($grandTotals as $grand) {
            $user = User::whereRaw('TRIM(UPPER(nip)) = ?', [trim(strtoupper($grand->nip))])->first();
            $detailHarian = is_array($grand->detail_harian) ? $grand->detail_harian : (json_decode($grand->detail_harian, true) ?: []);

            $rows = [];
            foreach ($dates as $date) {
                $amount = intval($detailHarian[$date] ?? 0);
                $rows[] = [
                    'tgl' => \Carbon\Carbon::parse($date)->format('j-M-Y'),
                    'lembur' => '-',
                    'gaji' => $amount > 0 ? $amount : '-',
                ];
            }

            $slips[] = [
                'nama' => $grand->nama ? ucwords(strtolower($grand->nama)) : $grand->nama,
                'nip' => $grand->nip,
                'divisi' => $user?->bagian ?? '-',
                'jabatan' => $user?->job_title ?? ucfirst($grand->section),
                'periode' => $periodeLabel,
                'total_gaji' => array_sum(array_map('intval', $detailHarian)),
                'komplain' => intval($grand->komplain ?? 0),
                'insentif' => intval($grand->insentif ?? 0),
                'potongan_lain' => intval($grand->potongan_lain ?? 0),
                'potongan_bpjs' => intval($grand->potongan_bpjs ?? 0),
                'total_akhir' => intval($grand->total_akhir ?? 0),
                'rows' => $rows,
            ];
        }

        $rowStart = 1;
        foreach (array_chunk($slips, $slipPerRow) as $chunk) {
            $maxRows = 0;
            foreach ($chunk as $slip) {
                $slipRows = 3 + 5 + 1 + count($slip['rows']) + 6;
                $maxRows = max($maxRows, $slipRows);
            }

            foreach ($chunk as $index => $slip) {
                $colStart = $index * $colStep;
                $cA = $colLetter($colStart);
                $cB = $colLetter($colStart + 1);
                $cC = $colLetter($colStart + 2);
                $cD = $colLetter($colStart + 3);
                $r = $rowStart;

                $cE = $colLetter($colStart + 4);

                $sheet->mergeCells("{$cA}{$r}:{$cA}" . ($r + 1));
                $sheet->setCellValue("{$cA}{$r}", 'W.A.J');
                $sheet->getStyle("{$cA}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFC000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("{$cA}{$r}:{$cA}" . ($r + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                $sheet->getStyle("{$cA}{$r}:{$cA}" . ($r + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->mergeCells("{$cB}{$r}:{$cE}{$r}");
                $sheet->setCellValue("{$cB}{$r}", 'PT WALET ABDILLAH JABLI');
                $sheet->getStyle("{$cB}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("{$cB}{$r}:{$cE}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
                $sheet->getStyle("{$cB}{$r}:{$cE}{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $r++;

                $sheet->mergeCells("{$cB}{$r}:{$cE}{$r}");
                $sheet->setCellValue("{$cB}{$r}", 'SLIP GAJI KARYAWAN');
                $sheet->getStyle("{$cB}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("{$cB}{$r}:{$cE}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
                $sheet->getStyle("{$cB}{$r}:{$cE}{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $r++;

                $infoRows = [
                    ['NAMA', $slip['nama']],
                    ['NIP', $slip['nip']],
                    ['DIVISI', $slip['divisi']],
                    ['JABATAN', $slip['jabatan']],
                    ['PERIODE', $slip['periode']],
                ];
                foreach ($infoRows as $info) {
                    $sheet->setCellValue("{$cA}{$r}", $info[0]);
                    $sheet->mergeCells("{$cB}{$r}:{$cE}{$r}");
                    $sheet->setCellValue("{$cB}{$r}", $info[1]);
                    $sheet->getStyle("{$cA}{$r}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                    $sheet->getStyle("{$cA}{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("{$cB}{$r}:{$cE}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("{$cE}{$r}")->applyFromArray(['borders' => ['right' => ['borderStyle' => Border::BORDER_THIN]]]);
                    $r++;
                }

                $sheet->setCellValue("{$cA}{$r}", 'Tanggal');
                $sheet->setCellValue("{$cB}{$r}", 'Lembur');
                $sheet->mergeCells("{$cC}{$r}:{$cE}{$r}");
                $sheet->setCellValue("{$cC}{$r}", 'Gaji');
                $sheet->getStyle("{$cA}{$r}:{$cE}{$r}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $r++;

                $summaryRows = [
                    ['KOMPLIAN', $slip['komplain']],
                    ['INSENTIF', $slip['insentif']],
                    ['POT. LAIN', $slip['potongan_lain']],
                    ['POT. BPJS KES', $slip['potongan_bpjs']],
                ];

                foreach ($slip['rows'] as $rowIndex => $row) {
                    $sheet->setCellValue("{$cA}{$r}", $row['tgl']);
                    $sheet->setCellValue("{$cB}{$r}", $row['lembur']);
                    $sheet->setCellValue("{$cC}{$r}", $row['gaji']);

                    if (isset($summaryRows[$rowIndex])) {
                        $label = $summaryRows[$rowIndex][0];
                        $val = $summaryRows[$rowIndex][1];
                        $display = (intval($val) === 0) ? '-' : $val;
                        $sheet->setCellValue("{$cD}{$r}", $label);
                        $sheet->setCellValue("{$cE}{$r}", $display);
                        $sheet->getStyle("{$cD}{$r}:{$cE}{$r}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                        if (is_numeric($display)) {
                            $sheet->getStyle("{$cE}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                            $sheet->getStyle("{$cE}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        } else {
                            $sheet->getStyle("{$cE}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    } else {
                        $sheet->setCellValue("{$cD}{$r}", '');
                        $sheet->setCellValue("{$cE}{$r}", '');
                    }

                    if (is_numeric($row['gaji'])) {
                        $sheet->getStyle("{$cC}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                    }
                    $sheet->getStyle("{$cA}{$r}:{$cE}{$r}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                    $sheet->getStyle("{$cA}{$r}:{$cC}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("{$cA}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $r++;
                }

                $sheet->setCellValue("{$cA}{$r}", 'Total Gaji');
                $sheet->setCellValue("{$cB}{$r}", '');
                $sheet->setCellValue("{$cC}{$r}", $slip['total_gaji']);
                $sheet->setCellValue("{$cD}{$r}", 'Total Akhir');
                $sheet->setCellValue("{$cE}{$r}", $slip['total_akhir']);
                $sheet->getStyle("{$cA}{$r}:{$cE}{$r}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                $sheet->getStyle("{$cA}{$r}:{$cE}{$r}")->getFont()->setBold(true);
                $sheet->getStyle("{$cC}{$r}:{$cE}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("{$cC}{$r}:{$cE}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $r++;
            }

            $rowStart += $maxRows + 1;
        }

        for ($si = 0; $si < $slipPerRow; $si++) {
            $colStart = $si * $colStep;
            $sheet->getColumnDimension($colLetter($colStart))->setWidth(11.86);
            $sheet->getColumnDimension($colLetter($colStart + 1))->setWidth(9.5);
            $sheet->getColumnDimension($colLetter($colStart + 2))->setWidth(11.86);
            $sheet->getColumnDimension($colLetter($colStart + 3))->setWidth(14.5);
            $sheet->getColumnDimension($colLetter($colStart + 4))->setWidth(11);
        }

        $receiptSheet = $spreadsheet->createSheet();
        $receiptSheet->setTitle('Tanda Terima Slip');
        $receiptSheet->setCellValue('A1', 'TANDA TERIMA SLIP GAJI');
        $receiptSheet->setCellValue('A2', 'Periode: ' . $periodeLabel);
        $receiptSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $receiptSheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $receiptSheet->fromArray(['No', 'Nama', 'Posisi', 'TTD'], null, 'A4');
        $receiptSheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);
        $rowNum = 5;
        foreach ($grandTotals as $index => $row) {
            $user = User::whereRaw('TRIM(UPPER(nip)) = ?', [trim(strtoupper($row->nip))])->first();
            $receiptSheet->setCellValue('A' . $rowNum, $index + 1);
            $receiptSheet->setCellValue('B' . $rowNum, $user?->nama ?? $row->nama);
            $receiptSheet->setCellValue('C' . $rowNum, $user?->bagian ?? $row->job_label ?? '-');
            $receiptSheet->setCellValue('D' . $rowNum, '');
            $receiptSheet->getStyle("A{$rowNum}:D{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rowNum++;
        }
        foreach (['A', 'B', 'C', 'D'] as $column) {
            $receiptSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'slip-gaji-' . $payroll->periode . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ========================
    // EXPORT HARIAN — Excel Payroll Harian
    // ========================
    public function exportHarian($id)
    {
        $payroll = Payroll::findOrFail($id);
        $details = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();

        $bagianMap = User::whereIn('nip', $details->pluck('nip'))->pluck('bagian', 'nip');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payroll Harian');

        $headers = ['NIP', 'Nama', 'Bagian', 'Hadir', 'Alpha', 'Izin', 'Sakit', 'ST', 'Gaji Pokok', 'Lembur', 'Tambahan', 'Potongan', 'Total'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        $r = 2;
        foreach ($details as $d) {
            $sheet->fromArray([
                $d->nip,
                $d->nama,
                $bagianMap[$d->nip] ?? '-',
                $d->hadir,
                $d->alpha,
                $d->izin,
                $d->sakit,
                $d->setengah_hari,
                $d->gaji_pokok,
                $d->gaji_lembur ?? 0,
                $d->tambahan ?? 0,
                $d->potongan ?? 0,
                $d->total_gaji,
            ], null, 'A' . $r);
            $r++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Payroll_Harian_' . $payroll->periode . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
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
            $dayLogs  = $logs->get($tgl) ?? collect();

            $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
            $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

            $fpIn  = $inTimes->isNotEmpty()  ? date('H:i', $inTimes->min())  : null;
            $fpOut = $outTimes->isNotEmpty() ? date('H:i', $outTimes->max()) : null;

            $correction = $corrections->get($tgl) ?? null;

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
                'id'            => $detail->id,
                'nama'          => $detail->nama,
                'nip'           => $detail->nip,
                'nominal_harian'=> $detail->nominal_harian,
                'gaji_pokok'    => $detail->gaji_pokok,
                'gaji_lembur'   => $detail->gaji_lembur,
                'tambahan'      => $detail->tambahan,
                'potongan'      => $detail->potongan,
                'total_gaji'    => $detail->total_gaji,
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
                    // remove any overtime request for this day as well
                    OvertimeRequest::where('pin', $pin)->where('tanggal', $tgl)->delete();
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

                // Maintain overtime_requests as source of truth for approved lembur
                $approved = $correctionData['lembur_approved'] ?? false;
                $lm = intval($correctionData['lembur_menit'] ?? 0);
                if ($approved && $lm > 0) {
                    OvertimeRequest::updateOrCreate(
                        ['pin' => $pin, 'tanggal' => $tgl],
                        [
                            'nip' => $detail->nip,
                            'nama' => $detail->nama,
                            'jam_out' => $jamOut ?: null,
                            'lembur_menit' => $lm,
                            'status' => 'approved',
                            'keterangan' => $ket ?: null,
                            'approved_by' => $userId,
                            'approved_at' => now(),
                            'created_by' => $userId,
                        ]
                    );
                } else {
                    // remove any existing overtime request if approval removed
                    OvertimeRequest::where('pin', $pin)->where('tanggal', $tgl)->delete();
                }
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

                $correction = $corrections->get($tgl) ?? null;

                if ($correction) {
                    $status = $correction->status;
                } else {
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs->get($dayKey) ?? collect();
                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));
                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
                    $note = is_array($absenceNotes) ? ($absenceNotes[$tgl] ?? null) : ($absenceNotes->get($tgl) ?? null);
                    $status = ($inTs || $outTs) ? 'H' : ($note ? $note->code : 'A');
                }

                // lembur minutes for pay are taken from approved OvertimeRequest table (single source of truth)
                // we'll compute total approved minutes after iterating days

                switch ($status) {
                    case 'H': $hadir++; break;
                    case 'A': $alpha++; break;
                    case 'I': $izin++;  break;
                    case 'S': $sakit++; break;
                    case 'ST': $setengahHari++; break;
                }
            }

            // Sum approved overtime minutes from OvertimeRequest table for this period
            $lemburMenit = (int) OvertimeRequest::where('pin', $pin)
                ->whereBetween('tanggal', [$dari, $sampai])
                ->sum('lembur_menit');

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

}
