<?php
// Usage: php scripts/check_grand_total.php [payroll_id] [nip_search]
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\PayrollGrandTotal;
use App\Models\PayrollDetail;
use App\Models\BoronganImport;
use App\Models\BoronganRekap;
use App\Models\BoronganHarian;
use App\Models\User;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceLog;
use App\Models\BpjsMaster;

$argv0 = $argv;
array_shift($argv0);
$payrollId = $argv0[0] ?? null;
$nipSearch = $argv0[1] ?? null;

if (!$payrollId) {
    $payroll = Payroll::orderByDesc('id')->first();
    if (!$payroll) {
        echo "No payroll found.\n";
        exit(1);
    }
    $payrollId = $payroll->id;
    echo "No payroll_id given — using latest payroll id={$payrollId} (per: {$payroll->periode})\n";
} else {
    $payroll = Payroll::find($payrollId);
    if (!$payroll) { echo "Payroll id {$payrollId} not found\n"; exit(1); }
}

echo "Checking payroll {$payrollId}: {$payroll->periode} ({$payroll->tanggal_dari} -> {$payroll->tanggal_sampai})\n\n";

$grandTotals = PayrollGrandTotal::where('payroll_id', $payrollId)->get();
if ($grandTotals->isEmpty()) { echo "No grand totals for this payroll.\n"; exit(0); }

$bpjsMasterByNip = BpjsMaster::all()->keyBy(fn($b)=>trim(strtoupper($b->nip)));

function compute_detail_for_nip($payroll, $nip, $bpjsMasterByNip) {
    $cabutImports = BoronganImport::where('payroll_id', $payroll->id)->where('jenis','cabut')->get();
    $hcrImports = BoronganImport::where('payroll_id', $payroll->id)->where('jenis','hcr')->get();
    $mouldingImports = BoronganImport::where('payroll_id', $payroll->id)->where('jenis','moulding')->get();

    $cabutHcrImportIds = $cabutImports->pluck('id')->merge($hcrImports->pluck('id'))->filter()->values()->all();
    $mouldingImportIds = $mouldingImports->pluck('id')->filter()->values()->all();
    $boronganImportIds = array_merge($cabutHcrImportIds, $mouldingImportIds);

    $dateFrom = new DateTime($payroll->tanggal_dari);
    $dateTo = new DateTime($payroll->tanggal_sampai);

    $detailHarianGram = [];
    $totalLembur = 0;

    $user = User::whereRaw('TRIM(UPPER(nip)) = ?', [$nip])->first();

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
        $payrollDetail = PayrollDetail::where('payroll_id', $payroll->id)
            ->whereRaw('TRIM(UPPER(nip)) = ?', [$nip])
            ->first();
        $pin = $user?->pin;

        $corrections = AttendanceCorrection::where('pin', $pin)
            ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
            ->get()
            ->keyBy(fn($c)=>\Carbon\Carbon::parse($c->tanggal)->format('Y-m-d'));

        $logs = AttendanceLog::where('pin', $pin)
            ->whereBetween('tanggal', [$payroll->tanggal_dari, $payroll->tanggal_sampai])
            ->orderBy('datetime')
            ->get()
            ->groupBy(fn($l)=>substr((string)$l->tanggal,0,10));

        $hariHadirList = [];
        $currentDate = clone $dateFrom;
        while ($currentDate <= $dateTo) {
            $tanggal = $currentDate->format('Y-m-d');
            $correction = $corrections[$tanggal] ?? null;
            $isHadir = false;
            if ($correction) {
                $isHadir = in_array($correction->status, ['H','ST']) || $correction->lembur_approved;
            } else {
                $dayLogs = $logs[$tanggal] ?? collect();
                if ($dayLogs->isNotEmpty()) $isHadir = true;
            }
            if ($isHadir) $hariHadirList[] = $tanggal;
            $currentDate->modify('+1 day');
        }

        $hadirCount = count($hariHadirList);
        $gajiPerHari = ($hadirCount>0 && $payrollDetail)? intdiv($payrollDetail->gaji_pokok, $hadirCount) : 0;

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

    return [$detailHarianGram, $totalLembur];
}

$issues = [];
foreach ($grandTotals as $g) {
    $nip = trim(strtoupper($g->nip));
    if ($nipSearch && stripos($nip, strtoupper($nipSearch)) === false && stripos(strtoupper($g->nama), strtoupper($nipSearch)) === false) continue;

    [$computedDetail, $computedLembur] = compute_detail_for_nip($payroll, $nip, $bpjsMasterByNip);
    $storedDetail = json_decode($g->detail_harian, true) ?? [];

    $dateFrom = new DateTime($payroll->tanggal_dari);
    $dateTo = new DateTime($payroll->tanggal_sampai);
    $cur = clone $dateFrom;

    $mismatches = [];
    while ($cur <= $dateTo) {
        $d = $cur->format('Y-m-d');
        $a = intval($storedDetail[$d] ?? 0);
        $b = intval($computedDetail[$d] ?? 0);
        if ($a !== $b) $mismatches[$d] = ['stored'=>$a,'computed'=>$b];
        $cur->modify('+1 day');
    }

    $storedSum = array_sum($storedDetail);
    $computedSum = array_sum($computedDetail);

    if (!empty($mismatches) || $storedSum !== $computedSum) {
        $issues[] = ['nip'=>$g->nip,'nama'=>$g->nama,'stored_sum'=>$storedSum,'computed_sum'=>$computedSum,'mismatches'=>$mismatches,'stored_total_akhir'=>$g->total_akhir,'computed_lembur'=>$computedLembur,'stored_lembur'=>$g->total_lembur];
    }
}

if (empty($issues)) {
    echo "All grand totals match recomputed per-day details.\n";
    exit(0);
}

foreach ($issues as $it) {
    echo "NIP: {$it['nip']} - {$it['nama']}\n";
    echo "  stored detail sum: ".number_format($it['stored_sum'])."  recomputed sum: ".number_format($it['computed_sum'])."\n";
    echo "  stored total_lembur: ".number_format($it['stored_lembur'])."  recomputed lembur: ".number_format($it['computed_lembur'])."\n";
    echo "  stored total_akhir: ".number_format($it['stored_total_akhir'])."\n";
    if (!empty($it['mismatches'])) {
        echo "  Mismatched dates:\n";
        foreach ($it['mismatches'] as $d=>$vals) {
            echo "    $d -> stored: ".number_format($vals['stored'])."  computed: ".number_format($vals['computed'])."\n";
        }
    }
    echo "\n";
}

echo "Done.\n";
