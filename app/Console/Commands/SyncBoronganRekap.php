<?php

namespace App\Console\Commands;

use App\Helpers\BoronganHelper;
use App\Models\BoronganImport;
use App\Models\Payroll;
use App\Models\PayrollGrandTotal;
use Illuminate\Console\Command;

class SyncBoronganRekap extends Command
{
    protected $signature = 'borongan:resync-rekap
                            {--import= : Specific import ID to process (optional)}
                            {--payroll= : Specific payroll ID to process (optional)}
                            {--regenerate-grand-total : Also regenerate PayrollGrandTotal after syncing rekap}
                            {--dry-run : Only show what would be changed, without making changes}';

    protected $description = 'Re-sync BoronganRekap total_gram and tambahan_gram from actual BoronganHarian data.
                              This ensures all rekap entries correctly include per-employee rows AND tambahan gram (rows without NIP).
                              Use --regenerate-grand-total to also recalculate PayrollGrandTotal afterwards.';

    public function handle(): int
    {
        $importId = $this->option('import');
        $payrollId = $this->option('payroll');
        $regenerateGT = $this->option('regenerate-grand-total');
        $dryRun = $this->option('dry-run');

        // Build query for imports
        $importsQuery = BoronganImport::query();

        if ($importId) {
            $importsQuery->where('id', $importId);
        } elseif ($payrollId) {
            $importsQuery->where('payroll_id', $payrollId);
        }

        $imports = $importsQuery->orderBy('id')->get();

        if ($imports->isEmpty()) {
            $this->warn('No borongan imports found to process.');
            return 0;
        }

        $this->info("Found {$imports->count()} import(s) to process.");
        $progressBar = $this->output->createProgressBar($imports->count());
        $progressBar->start();

        $updatedRekapCount = 0;
        $updatedImportIds = [];

        foreach ($imports as $import) {
            // Calculate current totals from actual BoronganHarian data
            $perEmployee = BoronganHelper::getPerEmployeeGram($import->id);
            $tambahanGram = BoronganHelper::getTambahanGram($import->id);
            $employeeCount = $perEmployee->count();

            if ($dryRun) {
                $currentTambahan = $import->tambahan_gram ?? 0;
                $currentRekapGram = \App\Models\BoronganRekap::where('borongan_import_id', $import->id)->sum('total_gram');

                if ($currentTambahan != $tambahanGram || $currentRekapGram != $perEmployee->sum('total_gram')) {
                    $this->line("");
                    $this->info("  Import #{$import->id} ({$import->jenis}, {$import->filename}):");
                    $this->line("    - Tambahan gram: {$currentTambahan} → {$tambahanGram}");
                    $this->line("    - Rekap total_gram: {$currentRekapGram} → {$perEmployee->sum('total_gram')}");
                    $this->line("    - Employees: {$employeeCount}");
                }
                $progressBar->advance();
                continue;
            }

            // Sync rekap using the shared helper
            BoronganHelper::syncRekapForImport($import->id);

            $updatedRekapCount += $employeeCount;
            $updatedImportIds[] = $import->id;

            // If this import belongs to a payroll, track it for grand total regeneration
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry-run completed. No changes were made.');
            return 0;
        }

        $this->info("Rekap synced for {$updatedRekapCount} employee entries across " . count($updatedImportIds) . " imports.");

        // Regenerate grand total if requested
        if ($regenerateGT && !empty($updatedImportIds)) {
            $payrollIds = BoronganImport::whereIn('id', $updatedImportIds)
                ->whereNotNull('payroll_id')
                ->pluck('payroll_id')
                ->unique()
                ->values();

            $this->info("Regenerating grand total for {$payrollIds->count()} payroll(s)...");

            foreach ($payrollIds as $pid) {
                $payroll = Payroll::find($pid);
                if (!$payroll) continue;

                $this->line("  Regenerating grand total for Payroll #{$pid}...");

                // Delete existing grand totals
                PayrollGrandTotal::where('payroll_id', $pid)->delete();

                // We simulate a request to regenerate
                // Since generateGrandTotal is complex, we use a request-like approach
                $request = new \Illuminate\Http\Request();
                $request->merge(['force' => true]);

                try {
                    $controller = app(\App\Http\Controllers\PayrollController::class);
                    $controller->generateGrandTotal($request, $pid);
                    $this->line("  ✓ Grand total regenerated for Payroll #{$pid}");
                } catch (\Exception $e) {
                    $this->warn("  ✗ Failed to regenerate grand total for Payroll #{$pid}: {$e->getMessage()}");
                }
            }
        }

        $this->info('Done! All BoronganRekap entries have been synchronized.');

        return 0;
    }
}
