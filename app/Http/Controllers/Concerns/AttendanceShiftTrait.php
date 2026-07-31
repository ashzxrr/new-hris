<?php

namespace App\Http\Controllers\Concerns;

trait AttendanceShiftTrait
{
    /**
     * Determine in/out timestamps for a given pin and date with security cross-day rules.
     * Returns ['in_ts'=>..., 'out_ts'=>..., 'skip'=>bool]
     */
    private function getInOutForDay($pin, $tgl, $logs, $karyawan)
    {
        $dayKey = $pin . '_' . $tgl;
        $dayLogs = $logs[$dayKey] ?? collect();

        $inTimes  = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string) $l->datetime));
        $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string) $l->datetime));

        $inTs  = $inTimes->isNotEmpty()  ? $inTimes->min()  : null;
        $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

        $skipRow = false;

        $jobTitle = strtolower($karyawan->job_title ?? '');
        $nip = trim($karyawan->nip ?? '');
        $isPakSuhar = $nip === 'LMG-2024-1039';
        $isSecurity = $jobTitle === 'security';

        $minJamShiftMalam = null;
        if ($isPakSuhar) {
            $minJamShiftMalam = 16;
        } elseif ($isSecurity) {
            $minJamShiftMalam = 18;
        }

        if ($minJamShiftMalam !== null && $inTs && !$outTs) {
            $jamIn = (int) date('H', $inTs);

            if ($jamIn >= $minJamShiftMalam) {
                $besok = date('Y-m-d', strtotime($tgl . ' +1 day'));
                $besokKey = $pin . '_' . $besok;
                $besokLogs = $logs[$besokKey] ?? collect();

                $besokOutTimes = $besokLogs->where('status', 'OUT')->map(fn($l) => strtotime((string) $l->datetime));

                $besokOut = $besokOutTimes->filter(function($ts) {
                    $jam = (int) date('H', $ts);
                    return $jam >= 0 && $jam <= 11;
                });

                if ($besokOut->isNotEmpty()) {
                    $outTs = $besokOut->min();
                }
            }
        }

        if ($minJamShiftMalam !== null && !$inTs && $outTs) {
            $jamOut = (int) date('H', $outTs);
            if ($jamOut >= 0 && $jamOut <= 11) {
                $kemarin = date('Y-m-d', strtotime($tgl . ' -1 day'));
                $kemarinKey = $pin . '_' . $kemarin;
                $kemarinLogs = $logs[$kemarinKey] ?? collect();

                $kemarinInTimes  = $kemarinLogs->where('status', 'IN')->map(fn($l) => strtotime((string) $l->datetime));
                $kemarinOutTimes = $kemarinLogs->where('status', 'OUT')->map(fn($l) => strtotime((string) $l->datetime));

                $kemarinShiftMalam = $kemarinInTimes->filter(function($ts) use ($minJamShiftMalam) {
                    $jam = (int) date('H', $ts);
                    return $jam >= $minJamShiftMalam;
                });

                if ($kemarinShiftMalam->isNotEmpty() && $kemarinOutTimes->isEmpty()) {
                    $skipRow = true;
                }
            }
        }

        return [
            'in_ts'  => $inTs,
            'out_ts' => $outTs,
            'skip'   => $skipRow,
        ];
    }
}
