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
            'pin' => 'required|unique:users,pin',
            'nama' => 'required|string|max:255',
            'jk' => 'required|in:L,P',
            'job_title' => 'nullable|string',
            'job_level' => 'nullable|string',
            'bagian' => 'nullable|string',
            'departemen' => 'nullable|string',
            'kategori_gaji' => 'nullable|string',
            'tl_id' => 'nullable|string',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|string',
            'tanggal_masuk' => 'required|date|before_or_equal:today',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string',
        ]);

        User::create([
            'pin' => $request->pin,
            'nip' => $request->nip ?? '',
            'nama' => $request->nama,
            'nik' => $request->nik ?? '',
            'jk' => $request->jk,
            'job_title' => $request->job_title ?: 'Operator',
            'job_level' => $request->job_level ?: 'Operator',
            'bagian' => $request->bagian,
            'departemen' => $request->departemen ?: 'Produksi',
            'kategori_gaji' => $request->kategori_gaji ?? '',
            'tl_id' => $request->tl_id ?: null,
            'tempat_lahir' => $request->tempat_lahir ?? '',
            'tanggal_lahir' => $request->tanggal_lahir ?? '',
            'tanggal_masuk' => $request->tanggal_masuk,
            'alamat' => $request->alamat ?? '',
            'no_hp' => $request->no_hp ?? '',
            'is_active' => 1,
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
            'tlMap' => $tlMap,
        ]);
    }

    public function update(Request $request, $id)
    {
        $karyawan = User::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:100',
            'nik' => 'nullable|string|max:25',
            'jk' => 'required|in:L,P',
            'job_title' => 'nullable|string',
            'job_level' => 'nullable|string',
            'bagian' => 'nullable|string',
            'departemen' => 'nullable|string',
            'kategori_gaji' => 'nullable|string',
            'tl_id' => 'nullable|integer',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|string',
            'tanggal_masuk' => 'nullable|date|before_or_equal:today',
            'tanggal_resign' => 'nullable|date|before_or_equal:today',
            'alasan_resign' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string',

        ]);

        $tanggalMasuk = $request->input('tanggal_masuk', $karyawan->tanggal_masuk?->toDateString());
        $tanggalResign = $request->input('tanggal_resign', $karyawan->tanggal_resign?->toDateString());

        if ($karyawan->is_active && $tanggalResign) {
            return back()->withErrors([
                'tanggal_resign' => 'Tanggal resign hanya dapat diisi untuk karyawan nonaktif.',
            ])->withInput();
        }

        if ($tanggalMasuk && $tanggalResign && $tanggalResign < $tanggalMasuk) {
            return back()->withErrors([
                'tanggal_resign' => 'Tanggal resign tidak boleh sebelum tanggal masuk.',
            ])->withInput();
        }

        $karyawan->update([
            'nama' => $request->nama,
            'nip' => $request->nip ?? '',
            'nik' => $request->nik ?? '',
            'jk' => $request->jk,
            'job_title' => $request->job_title,
            'job_level' => $request->job_level,
            'bagian' => $request->bagian,
            'departemen' => $request->departemen,
            'kategori_gaji' => $request->kategori_gaji,
            'tl_id' => $request->tl_id ?: null,
            'tempat_lahir' => $request->tempat_lahir ?? '',
            'tanggal_lahir' => $request->tanggal_lahir ?? '',
            'tanggal_masuk' => $tanggalMasuk,
            'tanggal_resign' => $tanggalResign,
            'alasan_resign' => $request->input('alasan_resign', $karyawan->alasan_resign),
            'alamat' => $request->alamat ?? '',
            'no_hp' => $request->no_hp ?? '',

        ]);

        return redirect()->route('karyawan.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function editBulk(Request $request)
    {
        $ids = $request->ids ?? [];
        if (empty($ids)) {
            return redirect()->route('karyawan.index');
        }

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
        $validated = $request->validate([
            'karyawan' => 'nullable|array',
            'karyawan.*.id' => 'required|integer|exists:users,id',
            'karyawan.*.nama' => 'required|string|max:255',
            'karyawan.*.nip' => 'nullable|string|max:100',
            'karyawan.*.nik' => 'nullable|string|max:25',
            'karyawan.*.jk' => 'nullable|in:L,P',
            'karyawan.*.job_title' => 'nullable|string|max:100',
            'karyawan.*.job_level' => 'nullable|string|max:100',
            'karyawan.*.bagian' => 'nullable|string|max:100',
            'karyawan.*.departemen' => 'nullable|string|max:100',
            'karyawan.*.kategori_gaji' => 'nullable|string|max:100',
            'karyawan.*.tl_id' => 'nullable|integer|exists:users,id',
            'karyawan.*.tanggal_masuk' => 'nullable|date|before_or_equal:today',
            'karyawan.*.tanggal_resign' => 'nullable|date|before_or_equal:today',
            'karyawan.*.alasan_resign' => 'nullable|string|max:255',
        ]);
        $data = $validated['karyawan'] ?? [];

        foreach ($data as $id => $fields) {
            $user = User::find($id);
            if (! $user) {
                continue;
            }

            $tanggalMasuk = $fields['tanggal_masuk'] ?? $user->tanggal_masuk?->toDateString();
            $tanggalResign = $fields['tanggal_resign'] ?? $user->tanggal_resign?->toDateString();

            if ($user->is_active && $tanggalResign) {
                return back()->withErrors([
                    'karyawan' => "Tanggal resign hanya dapat diisi untuk karyawan nonaktif ({$user->nama}).",
                ])->withInput();
            }

            if ($tanggalMasuk && $tanggalResign && $tanggalResign < $tanggalMasuk) {
                return back()->withErrors([
                    'karyawan' => "Tanggal resign {$user->nama} tidak boleh sebelum tanggal masuk.",
                ])->withInput();
            }

            $user->update([
                'nama' => $fields['nama'] ?? $user->nama,
                'nip' => $fields['nip'] ?? '',
                'nik' => $fields['nik'] ?? '',
                'jk' => ($fields['jk'] ?? '') !== '' ? $fields['jk'] : $user->jk,
                'job_title' => $fields['job_title'] ?? $user->job_title,
                'job_level' => $fields['job_level'] ?? $user->job_level,
                'bagian' => $fields['bagian'] ?? $user->bagian,
                'departemen' => $fields['departemen'] ?? $user->departemen,
                'kategori_gaji' => $fields['kategori_gaji'] ?? $user->kategori_gaji,
                'tl_id' => isset($fields['tl_id']) && $fields['tl_id'] !== '' ? $fields['tl_id'] : null,
                'tanggal_masuk' => $tanggalMasuk,
                'tanggal_resign' => $tanggalResign,
                'alasan_resign' => $fields['alasan_resign'] ?? $user->alasan_resign,
            ]);
        }

        return redirect()->route('karyawan.index')
            ->with('success', count($data).' karyawan berhasil diupdate.');
    }

    public function resignBulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users,id',
            'tanggal_resign' => 'required|date|before_or_equal:today',
            'alasan_resign' => 'required|string|max:255',
        ]);

        $employees = User::whereIn('id', $validated['ids'])
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'Tidak ada karyawan aktif yang dipilih untuk resign.');
        }

        foreach ($employees as $employee) {
            if ($employee->tanggal_masuk && $validated['tanggal_resign'] < $employee->tanggal_masuk->toDateString()) {
                return back()->withErrors([
                    'tanggal_resign' => "Tanggal resign {$employee->nama} tidak boleh sebelum tanggal masuk.",
                ])->withInput();
            }
        }

        foreach ($employees as $employee) {
            $employee->update([
                'is_active' => false,
                'tanggal_resign' => $validated['tanggal_resign'],
                'alasan_resign' => $validated['alasan_resign'],
            ]);
        }

        return redirect()->route('karyawan.index')
            ->with('success', count($employees).' karyawan berhasil ditandai RESIGN.');
    }

    public function destroyPermanent(Request $request)
    {
        $ids = $request->ids ?? [];
        if (empty($ids)) {
            return redirect()->route('karyawan.index');
        }

        $karyawan = User::whereIn('id', $ids)->get();

        foreach ($karyawan as $k) {
            $this->fp->deleteUser($k->pin);
            $k->delete();
        }

        return redirect()->route('karyawan.index')
            ->with('success', count($ids).' karyawan berhasil dihapus permanen dari database dan mesin.');
    }

    public function destroy($id)
    {
        User::findOrFail($id);

        return redirect()->route('karyawan.index')
            ->with('error', 'Gunakan aksi Resign agar tanggal keluar dan alasan resign tercatat.');
    }
}
