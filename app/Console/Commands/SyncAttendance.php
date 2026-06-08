<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FingerprintService;
use App\Models\AttendanceLog;

class SyncAttendance extends Command
{
    protected $signature = 'attendance:sync';
    protected $description = 'Sync attendance logs from fingerprint machines to database';

    public function handle(FingerprintService $fp): void
    {
        $this->info('Syncing attendance from machines...');

        $machines = config('fingerprint.machines');
        $totalInserted = 0;

        foreach ($machines as $machine) {
            if (!$machine['active']) continue;

            $connection = @fsockopen($machine['ip'], $machine['port'], $errno, $errstr, 5);
            if (!$connection) {
                $this->warn("Cannot connect to {$machine['name']}");
                continue;
            }
            fclose($connection);

            $soap = "<GetAttLog>
                <ArgComKey xsi:type=\"xsd:integer\">{$machine['key']}</ArgComKey>
                <Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg>
            </GetAttLog>";

            // Use reflection or make sendSoap public temporarily
            $response = $fp->sendSoapPublic($machine['ip'], $machine['port'], $soap);
            if (!$response || strpos($response, '<Row>') === false) {
                $this->warn("No data from {$machine['name']}");
                continue;
            }

            preg_match_all('/<Row>(.*?)<\/Row>/s', $response, $matches);
            $batch = [];

            foreach ($matches[1] as $row) {
                preg_match('/<PIN>(.*?)<\/PIN>/', $row, $pin);
                preg_match('/<DateTime>(.*?)<\/DateTime>/', $row, $datetime);
                preg_match('/<Verified>(.*?)<\/Verified>/', $row, $verified);
                preg_match('/<Status>(.*?)<\/Status>/', $row, $status);

                $waktu = trim($datetime[1] ?? '');
                if (!$waktu) continue;

                $pinVal = $fp->normalizePin($pin[1] ?? '');
                if (!$pinVal) continue;

                $batch[] = [
                    'pin'          => $pinVal,
                    'datetime'     => $waktu,
                    'tanggal'      => substr($waktu, 0, 10),
                    'status'       => ($status[1] ?? '') === '0' ? 'IN' : 'OUT',
                    'verified'     => $verified[1] ?? null,
                    'machine_name' => $machine['name'],
                ];

                if (count($batch) >= 500) {
                    AttendanceLog::upsert($batch, ['pin', 'datetime', 'machine_name'], ['status', 'verified']);
                    $totalInserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                AttendanceLog::upsert($batch, ['pin', 'datetime', 'machine_name'], ['status', 'verified']);
                $totalInserted += count($batch);
            }

            $this->info("Done: {$machine['name']}");
        }

        $this->info("Total synced: {$totalInserted} records.");
    }
}
