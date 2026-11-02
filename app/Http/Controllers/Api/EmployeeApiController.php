<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Employee API — consumed by the waj-attendance external sync system.
 * Read-only endpoint: returns employee list for attendance data sync.
 */
class EmployeeApiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->with('tl:id,pin')
            ->select('id', 'pin', 'nama', 'nik', 'is_active', 'tl_id', 'updated_at');

        // Optional: filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Optional: incremental sync — only users updated since given datetime
        if ($request->filled('updated_since')) {
            $updatedSince = Carbon::parse($request->query('updated_since'))->toDateTimeString();
            $query->where('updated_at', '>=', $updatedSince);
        }

        return $query->paginate(500)->through(fn (User $user): array => [
            'pin' => $user->pin,
            'nama' => $user->nama,
            'nik' => $user->nik,
            'is_active' => $user->is_active,
            'tl_pin' => $user->tl?->pin,
        ]);
    }

    public function profile(string $pin): JsonResponse
    {
        $user = User::query()
            ->where('pin', $pin)
            ->where('is_active', true)
            ->with('tl:id,nama,pin')
            ->first([
                'id',
                'pin',
                'nip',
                'nama',
                'nik',
                'alamat',
                'no_hp',
                'tempat_lahir',
                'tanggal_lahir',
                'jk',
                'job_title',
                'job_level',
                'tl_id',
                'bagian',
                'departemen',
                'kategori_gaji',
            ]);

        if (! $user) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        return response()->json([
            'pin' => $user->pin,
            'nip' => $user->nip,
            'nama' => $user->nama,
            'nik' => $user->nik,
            'alamat' => $user->alamat,
            'no_hp' => $user->no_hp,
            'tempat_lahir' => $user->tempat_lahir,
            'tanggal_lahir' => $user->getRawOriginal('tanggal_lahir'),
            'jk' => $user->jk,
            'job_title' => $user->job_title,
            'job_level' => $user->job_level,
            'bagian' => $user->bagian,
            'departemen' => $user->departemen,
            'kategori_gaji' => $user->kategori_gaji,
            'tl_nama' => $user->tl?->nama,
        ]);
    }
}
