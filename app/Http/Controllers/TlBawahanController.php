<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TlBawahanController extends Controller
{
    public function index()
    {
        $allTls = User::query()
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
            ->get()
            ->keyBy('id');

        // Definisi grup: [nama_grup => [tl_id => checker_name]]
        $groupDefs = [
            'Cabut' => [
                8   => 'Agung Hermansyah',
                3   => 'Agus Kurniawan',
                2   => 'Emanual Arvit Afriza',
                25  => 'Nita Nurul Aini',
                22  => 'Umariyah',
                119 => 'Yuni Indarwati',
                34  => 'Suwanto',
                30  => 'Khalawatul Imah',
                109 => 'Asmaiyah',
            ],
            'Cetak / Moulding' => [
                57  => null,
                113 => null,
                7   => null,
                71  => null,
                27  => null,
                48  => null,
                99  => null,
                75  => null,
                69  => null,
                74  => null,
                134 => null,   // M. Jamaluddin Saputra (TL, bukan checker)
            ],
            'Bahan Baku & Lainnya' => [
                1   => null,   // Anik - Pre Wash
                98  => null,   // M Gaung Sidiq - Pre Cleaning
                40  => null,   // Cankiswan - Bahan Baku
                865 => null,   // TL CCP 1
                63  => null,   // Puput Indarwati
                118 => null,   // Kerinna
                871 => null,   // Sanitasi
                872 => null,   // Checker
            ],
        ];

        $groupedTls = [];
        foreach ($groupDefs as $groupName => $tlMap) {
            $tlUserList = collect();
            $checkerMap = [];
            foreach ($tlMap as $tlId => $checkerName) {
                if (isset($allTls[$tlId])) {
                    $tlUserList->push($allTls[$tlId]);
                    if ($checkerName) {
                        $checkerMap[$tlId] = $checkerName;
                    }
                }
            }
            $groupedTls[] = [
                'name'       => $groupName,
                'tls'        => $tlUserList,
                'checkers'   => $checkerMap,
            ];
        }

        return view('tl-bawahan.index', [
            'title'      => 'Daftar TL & Bawahan',
            'groupedTls' => $groupedTls,
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

    public function searchUsers(Request $request)
    {
        $search = $request->q;
        $excludeTlId = $request->exclude_tl_id;

        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $query = User::query()
            ->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->limit(20);

        // Exclude users already under this TL
        if ($excludeTlId) {
            $query->where(function ($q) use ($excludeTlId) {
                $q->whereNull('tl_id')
                  ->orWhere('tl_id', '!=', $excludeTlId);
            });
        }

        $users = $query->get(['id', 'nama', 'nip', 'tl_id']);

        $tlIds = $users->pluck('tl_id')->filter()->unique();
        $tlMap = User::whereIn('id', $tlIds)->pluck('nama', 'id');

        $results = $users->map(function ($u) use ($tlMap) {
            return [
                'id'         => $u->id,
                'nama'       => $u->nama,
                'nip'        => $u->nip,
                'current_tl' => $u->tl_id ? ($tlMap[$u->tl_id] ?? 'TL #' . $u->tl_id) : null,
            ];
        });

        return response()->json($results);
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
