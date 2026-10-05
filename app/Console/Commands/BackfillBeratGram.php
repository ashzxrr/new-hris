<?php

namespace App\Console\Commands;

use App\Models\BoronganHarian;
use App\Models\BoronganRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBeratGram extends Command
{
    protected $signature = 'borongan:backfill-berat
                            {--dry-run : Show changes without applying}
                            {--limit= : Max rows to process (for testing)}'
;

    protected $description = 'Backfill berat_gram using upah_sistem / rate_per_gram for known categories (BS_A, BS_B, VIP).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // Load rates from borongan_rates table
        $rates = BoronganRate::all()->keyBy(fn($r) => strtoupper(trim((string) $r->kode_kategori)))->map(fn($r) => (int) $r->rate_per_gram)->all();

        // Default fallback if rates missing
        $fallback = [
            'BS_A' => 693,
            'BS_B' => 980,
            'VIP'  => 693,
        ];

        $categories = array_keys($fallback);

        $query = BoronganHarian::query()
            ->whereNotNull('kategori')
            ->where(function ($q) use ($categories) {
                foreach ($categories as $cat) {
                    $q->orWhereRaw("UPPER(TRIM(REPLACE(REPLACE(kategori, ' ', '_'), '-', '_'))) = ?", [$cat]);
                }
            })
            ->where('upah_sistem', '>', 0);

        if ($limit) $query->limit($limit);

        $total = $query->count();
        if ($total === 0) {
            $this->info('No rows found to process.');
            return 0;
        }

        $this->info("Found {$total} rows to evaluate.");

        $updated = 0;
        $changedSamples = [];

        $query->chunkById(500, function ($rows) use (&$updated, &$changedSamples, $rates, $fallback, $dryRun) {
            foreach ($rows as $row) {
                $rawCat = strtoupper(trim((string) $row->kategori));
                $normCat = str_replace([' ', '-'], '_', $rawCat);
                $kode = $normCat;

                $rate = $rates[$kode] ?? ($fallback[$kode] ?? null);
                if (!$rate || $rate == 0) continue;

                $currentGram = (float) $row->berat_gram;
                // Compute new gram with 2 decimals
                if (function_exists('bcdiv')) {
                    $newGramStr = bcdiv((string) $row->upah_sistem, (string) $rate, 2);
                    $newGram = (float) $newGramStr;
                } else {
                    $newGram = round($row->upah_sistem / $rate, 2);
                }

                if (abs($currentGram - $newGram) > 0.001) {
                    $changedSamples[] = [
                        'id' => $row->id,
                        'nip' => $row->nip,
                        'kategori' => $row->kategori,
                        'old_berat' => $currentGram,
                        'new_berat' => $newGram,
                        'upah_sistem' => $row->upah_sistem,
                        'rate' => $rate,
                    ];

                    if (! $dryRun) {
                        BoronganHarian::where('id', $row->id)->update(['berat_gram' => $newGram]);
                    }
                    $updated++;
                }
            }
        });

        $this->info("Rows updated: {$updated}");

        if (!empty($changedSamples)) {
            $this->line('Sample changes:');
            foreach (array_slice($changedSamples, 0, 10) as $s) {
                $this->line("  #{$s['id']} NIP={$s['nip']} Kategori={$s['kategori']} : {$s['old_berat']} → {$s['new_berat']} (upah={$s['upah_sistem']}, rate={$s['rate']})");
            }
        }

        if ($dryRun) {
            $this->info('Dry-run complete. No DB changes were made.');
        } else {
            $this->info('Backfill complete.');
        }

        return 0;
    }
}
