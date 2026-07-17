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

        $eligibleUsers = DB::table('users')
            ->leftJoin('karyawan_bank', 'users.nip', '=', 'karyawan_bank.nip')
            ->whereNotNull('users.nip')
            ->where('users.nip', '!=', '')
            ->whereNull('karyawan_bank.nip')
            ->select('users.nip', 'users.nama', 'users.kategori_gaji')
            ->orderBy('users.nip')
            ->get();

        return view('karyawan.bank', compact('rows', 'kategoriFilter', 'searchQuery', 'eligibleUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nip' => 'required|string|exists:users,nip',
            'items.*.kategori_gaji' => 'nullable|in:harian,borongan,bulanan',
            'items.*.nama_bank' => 'nullable|string|max:50',
            'items.*.no_rekening' => 'nullable|string|max:50',
            'items.*.email' => 'nullable|email|max:100',
        ]);

        foreach ($data['items'] as $item) {
            if (!empty($item['kategori_gaji'])) {
                DB::table('users')->where('nip', $item['nip'])->update([
                    'kategori_gaji' => $item['kategori_gaji'],
                ]);
            }

            KaryawanBank::updateOrCreate(
                ['nip' => $item['nip']],
                [
                    'nama_bank' => $item['nama_bank'] ?? null,
                    'no_rekening' => $item['no_rekening'] ?? null,
                    'email' => $item['email'] ?? null,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
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

    public function destroy($nip)
    {
        KaryawanBank::where('nip', $nip)->delete();

        return response()->json(['status' => 'ok']);
    }
}
