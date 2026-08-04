<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Attendance receive API — consumed by the waj-attendance external sync system.
 * Accepts attendance records and stores them with duplicate protection.
 */
class AttendanceReceiveController extends Controller
{
    public function receive(Request $request)
    {
        $validated = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.pin' => 'required|string',
            'records.*.datetime' => 'required|date_format:Y-m-d H:i:s',
            'records.*.tanggal' => 'required|date',
            'records.*.status' => 'required|in:IN,OUT',
            'records.*.verified' => 'required|boolean',
            'records.*.photo_url' => 'nullable|string|max:500',
        ]);

        $inserted = 0;
        $skippedDuplicates = 0;

        try {
            DB::beginTransaction();

            foreach ($validated['records'] as $record) {
                $exists = AttendanceLog::where('pin', $record['pin'])
                    ->where('datetime', $record['datetime'])
                    ->exists();

                if ($exists) {
                    $skippedDuplicates++;
                    continue;
                }

                AttendanceLog::create([
                    'pin' => $record['pin'],
                    'datetime' => $record['datetime'],
                    'tanggal' => $record['tanggal'],
                    'status' => $record['status'],
                    'verified' => (int) $record['verified'],
                    'machine_name' => 'Mobile App',
                    'photo_url' => $record['photo_url'] ?? null,
                ]);

                $inserted++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'inserted' => $inserted,
                'skipped_duplicates' => $skippedDuplicates,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Attendance receive failed', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to receive attendance data',
            ], 500);
        }
    }
}
