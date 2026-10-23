<?php

namespace App\Http\Controllers\Concerns;

trait AttendanceShiftTrait
{
    /**
     * Determine shift timestamps for a pin and date, including overnight shifts.
     * An early OUT can be marked as belonging to the previous day's shift.
     */
    private function getInOutForDay($pin, $tgl, $logs, $karyawan, $absenceNote = null)
    {
        $dayKey = $pin.'_'.$tgl;
        $dayLogs = $logs[$dayKey] ?? collect();

        $inTimes = $dayLogs->where('status', 'IN')->map(fn ($l) => strtotime((string) $l->datetime));
        $outTimes = $dayLogs->where('status', 'OUT')->map(fn ($l) => strtotime((string) $l->datetime));

        $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
        $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;
        if ($inTs && $outTs && $outTs <= $inTs) {
            $outTs = null;
        }

        if ($absenceNote && $dayLogs->isEmpty()) {
            return [
                'in_ts' => null,
                'out_ts' => null,
                'out_belongs_to_previous_shift' => false,
            ];
        }

        $outBelongsToPreviousShift = false;

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

        if ($minJamShiftMalam !== null && $inTs && ! $outTs) {
            $jamIn = (int) date('H', $inTs);

            if ($jamIn >= $minJamShiftMalam) {
                $besok = date('Y-m-d', strtotime($tgl.' +1 day'));
                $besokKey = $pin.'_'.$besok;
                $besokLogs = $logs[$besokKey] ?? collect();

                $besokOutTimes = $besokLogs->where('status', 'OUT')->map(fn ($l) => strtotime((string) $l->datetime));

                $besokOut = $besokOutTimes->filter(function ($ts) {
                    $jam = (int) date('H', $ts);

                    return $jam >= 0 && $jam <= 11;
                });

                if ($besokOut->isNotEmpty()) {
                    $outTs = $besokOut->min();
                }
            }
        }

        if ($minJamShiftMalam !== null && ! $inTs && $outTs) {
            $jamOut = (int) date('H', $outTs);
            if ($jamOut >= 0 && $jamOut <= 11) {
                if ($absenceNote) {
                    return [
                        'in_ts' => null,
                        'out_ts' => null,
                        'out_belongs_to_previous_shift' => false,
                    ];
                }

                $kemarin = date('Y-m-d', strtotime($tgl.' -1 day'));
                $kemarinKey = $pin.'_'.$kemarin;
                $kemarinLogs = $logs[$kemarinKey] ?? collect();

                $kemarinInTimes = $kemarinLogs->where('status', 'IN')->map(fn ($l) => strtotime((string) $l->datetime));
                $kemarinOutTimes = $kemarinLogs->where('status', 'OUT')->map(fn ($l) => strtotime((string) $l->datetime));

                $kemarinShiftMalam = $kemarinInTimes->filter(function ($ts) use ($minJamShiftMalam) {
                    $jam = (int) date('H', $ts);

                    return $jam >= $minJamShiftMalam;
                });

                $kemarinShiftMalamIn = $kemarinShiftMalam->max();
                $kemarinShiftOuts = $kemarinOutTimes->filter(fn ($ts) => $ts > $kemarinShiftMalamIn);

                if ($kemarinShiftMalamIn && $kemarinShiftOuts->isEmpty()) {
                    $outBelongsToPreviousShift = true;
                }
            }
        }

        return [
            'in_ts' => $inTs,
            'out_ts' => $outBelongsToPreviousShift ? null : $outTs,
            'out_belongs_to_previous_shift' => $outBelongsToPreviousShift,
        ];
    }
}
