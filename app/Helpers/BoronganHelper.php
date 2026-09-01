<?php

namespace App\Helpers;

use App\Models\BoronganHarian;
use App\Models\BoronganImport;
use App\Models\BoronganRekap;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class BoronganHelper
{
    /**
     * Get list of visible NIPs (active users with tl_id).
     * Same logic as BoronganController::getVisibleBoronganNips().
     */
    public static function getVisibleNips(): array
    {
        $query = User::query()->whereNotNull('nip');

        if (Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', 1);
        }

        if (Schema::hasColumn('users', 'tl_id')) {
            $query->whereNotNull('tl_id');
        }

        return $query->pluck('nip')
            ->map(fn ($nip) => strtoupper(trim((string) $nip)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get per-employee gram totals from borongan_harian for a given import.
     * Only includes rows with visible NIPs (active employees).
     * Returns a collection keyed by NIP with total_gram & total_upah.
     */
    public static function getPerEmployeeGram(int $importId): \Illuminate\Support\Collection
    {
        $visibleNips = self::getVisibleNips();

        if (empty($visibleNips)) {
            return collect();
        }

        return BoronganHarian::where('borongan_import_id', $importId)
            ->whereIn('nip', $visibleNips)
            ->whereNotNull('nip')
            ->where('nip', '<>', '')
            ->get()
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->nip)))
            ->map(function ($rows, $nip) {
                $first = $rows->first();
                return [
                    'nip'         => $nip,
                    'nama'        => $first->nama,
                    'pin'         => $first->pin,
                    'total_gram'  => $rows->sum('berat_gram'),
                    'total_upah'  => $rows->sum('upah_sistem'),
                ];
            });
    }

    /**
     * Get per-employee gram totals from ALL borongan_harian rows for a given import,
     * regardless of employee active status or tl_id.
     * Used for rekap sync so mutasi/moved employees are still included.
     * Returns a collection keyed by NIP with total_gram & total_upah.
     */
    public static function getPerEmployeeGramAll(int $importId): \Illuminate\Support\Collection
    {
        return BoronganHarian::where('borongan_import_id', $importId)
            ->whereNotNull('nip')
            ->where('nip', '<>', '')
            ->get()
            ->groupBy(fn ($item) => strtoupper(trim((string) $item->nip)))
            ->map(function ($rows, $nip) {
                $first = $rows->first();
                return [
                    'nip'         => $nip,
                    'nama'        => $first->nama,
                    'pin'         => $first->pin,
                    'total_gram'  => $rows->sum('berat_gram'),
                    'total_upah'  => $rows->sum('upah_sistem'),
                ];
            });
    }

    /**
     * Get tambahan gram (rows with null/empty NIP) for a given import.
     * These are "extra" gram rows not linked to any specific employee.
     */
    public static function getTambahanGram(int $importId): float
    {
        return BoronganHarian::where('borongan_import_id', $importId)
            ->where(function ($q) {
                $q->whereNull('nip')
                  ->orWhere('nip', '');
            })
            ->sum('berat_gram');
    }

    /**
     * Get aggregated notes from tambahan gram rows (null/empty NIP) for an import.
     * Returns an array of unique non-empty notes.
     */
    public static function getTambahanGramNotes(int $importId): array
    {
        return BoronganHarian::where('borongan_import_id', $importId)
            ->where(function ($q) {
                $q->whereNull('nip')
                  ->orWhere('nip', '');
            })
            ->whereNotNull('gram_note')
            ->where('gram_note', '<>', '')
            ->pluck('gram_note')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get total gram for an import including both per-employee and tambahan rows.
     */
    public static function getTotalGramForImport(int $importId): float
    {
        $perEmployee = self::getPerEmployeeGramAll($importId);
        $tambahan = self::getTambahanGram($importId);
        return $perEmployee->sum('total_gram') + $tambahan;
    }

    public static function formatGram(mixed $gram): string
    {
        if ($gram === null || $gram === '') {
            return '0';
        }

        $value = is_string($gram) ? trim($gram) : (string) $gram;

        // Apply "half-up" rounding to whole grams, because 0.5 and above should round up.
        if (function_exists('bcadd') && function_exists('bccomp') && function_exists('bcmul')) {
            $rounded = bcadd($value, '0', 2);
            $rounded = self::roundHalfUpToWhole($rounded);
            return number_format((int) $rounded, 0, ',', '.');
        }

        $rounded = round((float) $value, 0, PHP_ROUND_HALF_UP);
        return number_format((float) $rounded, 0, ',', '.');
    }

    private static function roundHalfUpToWhole(string $value): string
    {
        if (strpos($value, '.') === false) {
            return $value;
        }

        [$intPart, $decimalPart] = explode('.', $value, 2);
        $decimalPart = substr($decimalPart . '00', 0, 2);

        if ($decimalPart === '') {
            return $intPart;
        }

        $fraction = (int) $decimalPart;
        if ($fraction >= 50) {
            if (function_exists('bcadd')) {
                return bcadd($intPart, '1', 0);
            }

            return (string) ((int) $intPart + 1);
        }

        return $intPart;
    }

    /**
     * Sync/update BoronganRekap entries for an import based on actual BoronganHarian data.
     * This is the single source of truth for rekap creation — used by approve() AND the re-sync command.
     *
     * - Stores tambahan_gram on the import record
     * - Stores aggregated tambahan_gram_notes on the import record
     * - Creates/updates BoronganRekap per employee
     * - Does NOT touch potongan/tambahan/komplain fields that were manually edited
     */
    public static function syncRekapForImport(int $importId): void
    {
        $import = BoronganImport::findOrFail($importId);

        // 1. Store tambahan gram and notes on the import
        $tambahanGram = self::getTambahanGram($importId);
        $tambahanNotes = self::getTambahanGramNotes($importId);
        $import->tambahan_gram = $tambahanGram;
        $import->tambahan_gram_notes = !empty($tambahanNotes) ? implode('; ', $tambahanNotes) : null;
        $import->saveQuietly();

        // 2. Per-employee data — include ALL employees from borongan_harian,
        //    regardless of active status or tl_id (so mutasi employees are included)
        $perEmployee = self::getPerEmployeeGramAll($importId);
        $currentNips = $perEmployee->keys()->all();

        // 3. Delete rekap entries for employees no longer in the data
        BoronganRekap::where('borongan_import_id', $importId)
            ->whereNotIn('nip', $currentNips)
            ->delete();

        // 4. Create or update rekap for each employee
        foreach ($perEmployee as $nip => $data) {
            $existing = BoronganRekap::where('borongan_import_id', $importId)
                ->where('nip', $nip)
                ->first();

            if ($existing) {
                // Preserve manually-edited potongan/tambahan/komplain fields
                $existing->total_gram = $data['total_gram'];
                $existing->total_upah = $data['total_upah'];
                $existing->total_akhir = $data['total_upah']
                    + $existing->tambahan
                    - $existing->potongan_bpjs
                    - $existing->potongan_lain;
                $existing->saveQuietly();
            } else {
                BoronganRekap::create([
                    'borongan_import_id' => $importId,
                    'pin'                => $data['pin'],
                    'nip'                => $nip,
                    'nama'               => $data['nama'],
                    'periode_dari'       => $import->tanggal_dari,
                    'periode_sampai'     => $import->tanggal_sampai,
                    'total_gram'         => $data['total_gram'],
                    'total_upah'         => $data['total_upah'],
                    'potongan_bpjs'      => 0,
                    'potongan_lain'      => 0,
                    'tambahan'           => 0,
                    'total_akhir'        => $data['total_upah'],
                    'status'             => 'draft',
                ]);
            }
        }
    }

    /**
     * Get payroll-level total gram (rekap + tambahan) for a set of import IDs.
     */
    public static function getTotalGramForImports(array $importIds): float
    {
        // Compute total gram directly from borongan_harian (raw berat_gram decimals)
        // This ensures displayed totals use the original decimal grams (same as upah calculation)
        return (float) BoronganHarian::whereIn('borongan_import_id', $importIds)
            ->sum('berat_gram');
    }
}
