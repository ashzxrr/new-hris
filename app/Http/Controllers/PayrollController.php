<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\AbsenceNote;
use App\Models\AttendanceCorrection;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\SalaryConfig;
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
            $gajiPokok  = $hadir * $nominal;
            $upahPerJam = $nominal / 8;
            $gajiLembur = floor($upahPerJam * 1.5 * ($lemburMenit / 60));
            $totalGaji  = $gajiPokok + $gajiLembur;

            $previewData[] = [
                'pin'          => $pin,
                'nip'          => $nip,
                'nama'         => $k->nama,
                'job_title'    => $k->job_title,
                'nominal'      => $nominal,
                'hadir'        => $hadir,
                'alpha'        => $alpha,
                'izin'         => $izin,
                'sakit'        => $sakit,
                'lembur_menit' => $lemburMenit,
                'gaji_pokok'   => $gajiPokok,
                'gaji_lembur'  => $gajiLembur,
                'total_gaji'   => $totalGaji,
                'detail_harian'=> $detailHarian,
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
                    }
                }

                $gajiPokok  = $hadir * $nominal;
                $gajiLembur = floor(($nominal / 8) * 1.5 * ($lemburMenit / 60));
                $totalGaji  = $gajiPokok + $gajiLembur;

                PayrollDetail::create([
                    'payroll_id'     => $payroll->id,
                    'pin'            => $pin,
                    'nip'            => $nip,
                    'nama'           => $k->nama,
                    'nominal_harian' => $nominal,
                    'hadir'          => $hadir,
                    'alpha'          => $alpha,
                    'izin'           => $izin,
                    'sakit'          => $sakit,
                    'lembur_menit'   => $lemburMenit,
                    'gaji_pokok'     => $gajiPokok,
                    'gaji_lembur'    => $gajiLembur,
                    'tambahan'       => 0,
                    'potongan'       => 0,
                    'total_gaji'     => $totalGaji,
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
    // SHOW — Detail payroll
    // ========================
    public function show($id)
    {
        $payroll = Payroll::findOrFail($id);
        $details = PayrollDetail::where('payroll_id', $id)->orderBy('nama')->get();
        return view('payroll.show', compact('payroll', 'details'));
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
        $potongan = $request->potongan ?? 0;
        $total    = $detail->gaji_pokok + $detail->gaji_lembur + $tambahan - $potongan;

        $detail->update([
            'tambahan'    => $tambahan,
            'potongan'    => $potongan,
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

            $rows[] = [
                'tgl'        => $tgl,
                'tgl_display'=> \Carbon\Carbon::parse($tgl)->translatedFormat('D, d M Y'),
                'is_sunday'  => $isSunday,
                'fp_in'      => $fpIn,
                'fp_out'     => $fpOut,
                'kor_in'     => $correction ? substr($correction->jam_in ?? '', 0, 5) : null,
                'kor_out'    => $correction ? substr($correction->jam_out ?? '', 0, 5) : null,
                'kor_status' => $correction ? $correction->status : null,
                'kor_ket'    => $correction ? $correction->keterangan : null,
                'has_kor'    => (bool) $correction,
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

                // Jika jam_in dan jam_out kosong dan status H → hapus koreksi (pakai fingerprint)
                if (!$jamIn && !$jamOut && $status === 'H' && !$ket) {
                    AttendanceCorrection::where('pin', $pin)->where('tanggal', $tgl)->delete();
                    continue;
                }

                AttendanceCorrection::updateOrCreate(
                    ['pin' => $pin, 'tanggal' => $tgl],
                    [
                        'jam_in'     => $jamIn  ?: null,
                        'jam_out'    => $jamOut ?: null,
                        'status'     => $status,
                        'keterangan' => $ket,
                        'edited_by'  => $userId,
                    ]
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

            foreach ($periode as $tgl) {
                if (date('N', strtotime($tgl)) == 7) continue;

                $correction = $corrections[$tgl] ?? null;

                if ($correction) {
                    $status = $correction->status;
                    $jamOut = $correction->jam_out ? substr($correction->jam_out, 0, 5) : null;
                } else {
                    $dayKey  = $pin . '_' . $tgl;
                    $dayLogs = $logs[$dayKey] ?? collect();
                    $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));
                    $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
                    $jamOut = $outTs ? date('H:i', $outTs) : null;
                    $note   = $absenceNotes[$tgl] ?? null;
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
                }
            }

            $nominal    = $detail->nominal_harian;
            $gajiPokok  = $hadir * $nominal;
            $gajiLembur = floor(($nominal / 8) * 1.5 * ($lemburMenit / 60));
            $totalGaji  = $gajiPokok + $gajiLembur + $detail->tambahan - $detail->potongan;

            $detail->update([
                'hadir'        => $hadir,
                'alpha'        => $alpha,
                'izin'         => $izin,
                'sakit'        => $sakit,
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
}
