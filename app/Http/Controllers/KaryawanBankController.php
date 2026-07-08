<?php

namespace App\Http\Controllers;

use App\Models\KaryawanBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KaryawanBankController extends Controller
{
    public function index(Request $request)
    {
        $kategoriFilter = $request->kategori ?? 'semua';
        $searchQuery = $request->search ?? '';

        $rows = DB::table('users')
            ->leftJoin('karyawan_bank', 'users.nip', '=', 'karyawan_bank.nip')
            ->whereNotNull('users.nip')
            ->where('users.nip', '!=', '')
            ->when($kategoriFilter !== 'semua', function ($query) use ($kategoriFilter) {
                $query->where('users.kategori_gaji', $kategoriFilter);
            })
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where(function ($sub) use ($searchQuery) {
                    $sub->where('users.nip', 'like', '%' . $searchQuery . '%')
                        ->orWhere('users.nama', 'like', '%' . $searchQuery . '%');
                });
            })
            ->select(
                'users.nip',
                'users.nama as nama',
                'users.kategori_gaji',
                'karyawan_bank.nama_bank',
                'karyawan_bank.no_rekening',
                'karyawan_bank.email'
            )
            ->orderBy('users.nip')
            ->get();

        return view('karyawan.bank', compact('rows', 'kategoriFilter', 'searchQuery'));
    }

    public function update(Request $request, $nip)
    {
        $data = $request->validate([
            'nama_bank' => 'nullable|string|max:50',
            'no_rekening' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
        ]);

        KaryawanBank::updateOrCreate(
            ['nip' => $nip],
            [
                'nama_bank' => $data['nama_bank'] ?? null,
                'no_rekening' => $data['no_rekening'] ?? null,
                'email' => $data['email'] ?? null,
            ]
        );

        return response()->json(['status' => 'ok']);
    }
}
