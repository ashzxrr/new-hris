<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FingerprintService;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    private FingerprintService $fp;

    public function __construct(FingerprintService $fp)
    {
        $this->fp = $fp;
    }

    public function index()
    {
        // Load semua data sekaligus (bukan paginate)
        $karyawan = User::orderBy('id')
            ->get();
        $machineUsers = $this->fp->getUsers();
        $tlMap = User::whereIn('id', $karyawan->pluck('tl_id')->filter()->unique())
            ->pluck('nama', 'id');

        return view('karyawan.index', compact('karyawan', 'machineUsers', 'tlMap'));
    }

    public function syncPreview()
    {
        $fp = $this->fp;

        // Ambil semua user dari mesin
        $machineUsers = $fp->getUsers();

        // Ambil semua PIN yang sudah ada di DB, normalize ke integer string
        $existingPins = User::pluck('pin')
            ->map(fn ($p) => (string) intval($p))
            ->toArray();

        // Filter: hanya tampilkan yang PIN-nya belum ada di DB
        $newUsers = array_filter($machineUsers, function ($nama, $pin) use ($existingPins) {
            return ! in_array((string) intval($pin), $existingPins);
        }, ARRAY_FILTER_USE_BOTH);

        // Urutkan PIN dari terkecil ke terbesar
        ksort($newUsers, SORT_NUMERIC);

        return view('karyawan.sync', compact('newUsers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pin'           => 'required|unique:users,pin',
            'nama'          => 'required|string|max:255',
            'jk'            => 'required|in:L,P',
            'job_title'     => 'nullable|string',
            'job_level'     => 'nullable|string',
            'bagian'        => 'nullable|string',
            'departemen'    => 'nullable|string',
            'kategori_gaji' => 'nullable|string',
        ]);

        User::create([
            'pin'           => $request->pin,
            'nip'           => $request->nip ?? '',
            'nama'          => $request->nama,
            'nik'           => $request->nik ?? '',
            'jk'            => $request->jk,
            'job_title'     => $request->job_title ?: 'Operator',
            'job_level'     => $request->job_level ?: 'Operator',
            'bagian'        => $request->bagian,
            'departemen'    => $request->departemen ?: 'Produksi',
            'kategori_gaji' => $request->kategori_gaji ?? '',
            'is_active'     => 1,
        ]);

        return redirect()->route('karyawan.sync')
            ->with('success', 'Karyawan berhasil ditambahkan ke database.');
    }

    public function edit($id)
    {
        $karyawan = User::findOrFail($id);
        $tlMap = User::whereIn('job_level', ['Team Leader', 'Group Team Leader', 'Supervisor'])
            ->orderBy('nama')
            ->get(['id', 'nama', 'job_title']);

        return response()->json([
            'karyawan' => $karyawan,
            'tlMap' => $tlMap
        ]);
    }

    public function update(Request $request, $id)
    {
        $karyawan = User::findOrFail($id);

        $request->validate([
            'nama'          => 'required|string|max:255',
            'nip'           => 'nullable|string|max:100',
            'nik'           => 'nullable|string|max:25',
            'jk'            => 'required|in:L,P',
            'job_title'     => 'nullable|string',
            'job_level'     => 'nullable|string',
            'bagian'        => 'nullable|string',
            'departemen'    => 'nullable|string',
            'kategori_gaji' => 'nullable|string',
            'tl_id'         => 'nullable|integer',
        ]);

        $karyawan->update([
            'nama'          => $request->nama,
            'nip'           => $request->nip ?? '',
            'nik'           => $request->nik ?? '',
            'jk'            => $request->jk,
            'job_title'     => $request->job_title,
            'job_level'     => $request->job_level,
            'bagian'        => $request->bagian,
            'departemen'    => $request->departemen,
            'kategori_gaji' => $request->kategori_gaji,
            'tl_id'         => $request->tl_id ?: null,
        ]);

        return redirect()->route('karyawan.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function editBulk(Request $request)
    {
        $ids = $request->ids ?? [];
        if (empty($ids)) return redirect()->route('karyawan.index');

        $karyawan = User::whereIn('id', $ids)
            ->orderBy('tl_id')
            ->orderBy('nama')
            ->get();

        $tlIds = $karyawan->pluck('tl_id')->filter()->unique();
        $tlMap = User::whereIn('id', $tlIds)
            ->pluck('nama', 'id')
            ->toArray();

        $groupedKaryawan = $karyawan->groupBy(fn ($item) => $item->tl_id ?: 'none');

        return view('karyawan.edit-bulk', compact('karyawan', 'groupedKaryawan', 'tlMap'));
    }

    public function updateBulk(Request $request)
    {
        $data = $request->karyawan ?? [];

        foreach ($data as $id => $fields) {
            $user = User::find($id);
            if (! $user) {
                continue;
            }

            $user->update([
                'nama'          => $fields['nama'] ?? $user->nama,
                'nip'           => $fields['nip'] ?? '',
                'nik'           => $fields['nik'] ?? '',
                'jk'            => ($fields['jk'] ?? '') !== '' ? $fields['jk'] : $user->jk,
                'job_title'     => $fields['job_title'] ?? $user->job_title,
                'job_level'     => $fields['job_level'] ?? $user->job_level,
                'bagian'        => $fields['bagian'] ?? $user->bagian,
                'departemen'    => $fields['departemen'] ?? $user->departemen,
                'kategori_gaji' => $fields['kategori_gaji'] ?? $user->kategori_gaji,
                'tl_id'         => !empty($fields['tl_id']) ? $fields['tl_id'] : $user->tl_id,
            ]);
        }

        return redirect()->route('karyawan.index')
            ->with('success', count($data) . ' karyawan berhasil diupdate.');
    }

    public function resignBulk(Request $request)
    {
        $ids = $request->ids ?? [];
        if (empty($ids)) return redirect()->route('karyawan.index');

        User::whereIn('id', $ids)->update([
            'nip' => 'resign',
            'is_active' => 0,
        ]);

        return redirect()->route('karyawan.index')
            ->with('success', count($ids) . ' karyawan berhasil ditandai RESIGN.');
    }

    public function destroyPermanent(Request $request)
    {
        $ids = $request->ids ?? [];
        if (empty($ids)) return redirect()->route('karyawan.index');

        $karyawan = User::whereIn('id', $ids)->get();

        foreach ($karyawan as $k) {
            $this->fp->deleteUser($k->pin);
            $k->delete();
        }

        return redirect()->route('karyawan.index')
            ->with('success', count($ids) . ' karyawan berhasil dihapus permanen dari database dan mesin.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = 0;
        $user->save();

        return back()->with('success', 'Karyawan berhasil dinonaktifkan.');
    }
}
