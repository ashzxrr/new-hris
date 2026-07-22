<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TlBawahanController extends Controller
{
    public function index()
    {
        $tlUsers = User::query()
            ->with('anggota')
            ->where(function ($query) {
                $query->whereHas('anggota');

                if (Schema::hasColumn('users', 'role')) {
                    $query->orWhereIn('role', ['tl', 'leader', 'manager', 'supervisor']);
                }

                if (Schema::hasColumn('users', 'job_title')) {
                    $query->orWhere(function ($jobQuery) {
                        $jobQuery->whereNotNull('job_title')
                            ->where(function ($inner) {
                                $inner->orWhereRaw('LOWER(job_title) LIKE ?', ['%tl%'])
                                    ->orWhereRaw('LOWER(job_title) LIKE ?', ['%leader%'])
                                    ->orWhereRaw('LOWER(job_title) LIKE ?', ['%manager%'])
                                    ->orWhereRaw('LOWER(job_title) LIKE ?', ['%supervisor%']);
                            });
                    });
                }

                if (Schema::hasColumn('users', 'jabatan')) {
                    $query->orWhere(function ($jabatanQuery) {
                        $jabatanQuery->whereNotNull('jabatan')
                            ->where(function ($inner) {
                                $inner->orWhereRaw('LOWER(jabatan) LIKE ?', ['%tl%'])
                                    ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%leader%'])
                                    ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%manager%'])
                                    ->orWhereRaw('LOWER(jabatan) LIKE ?', ['%supervisor%']);
                            });
                    });
                }
            })
            ->orderBy('nama')
            ->get();

        return view('tl-bawahan.index', [
            'title' => 'Daftar TL & Bawahan',
            'tls' => $tlUsers,
        ]);
    }

    public function updateTl(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'tl_id' => ['nullable', 'exists:users,id'],
        ]);

        $user = User::find($validated['user_id']);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ((int) $validated['tl_id'] === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak bisa menjadi TL untuk dirinya sendiri.',
            ], 422);
        }

        if (! empty($validated['tl_id'])) {
            $targetTl = User::find($validated['tl_id']);

            if ($targetTl && $this->wouldCreateCircularReference($user, $targetTl)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perubahan ini akan membuat hubungan TL yang berulang.',
                ], 422);
            }
        }

        $user->tl_id = $validated['tl_id'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil dipindahkan',
        ]);
    }

    private function wouldCreateCircularReference(User $user, User $targetTl): bool
    {
        $current = $targetTl;

        while ($current) {
            if ((int) $current->id === (int) $user->id) {
                return true;
            }

            $current = $current->tl()->first();
        }

        return false;
    }
}
