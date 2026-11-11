<?php

namespace App\Http\Controllers;

use App\Models\SalaryConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryConfigController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $bagian = (string) $request->query('bagian', '');
        $sort = $request->query('sort') === 'terbaru' ? 'terbaru' : 'nama';

        $employeesQuery = User::query()
            ->where('is_active', true)
            ->where('kategori_gaji', 'harian')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('pin', 'like', "%{$search}%");
                });
            })
            ->when($bagian !== '', fn ($query) => $query->where('bagian', $bagian))
            ->when(
                $sort === 'terbaru',
                fn ($query) => $query
                    ->orderByRaw('CASE WHEN tanggal_masuk IS NULL THEN 1 ELSE 0 END')
                    ->orderByDesc('tanggal_masuk')
                    ->orderBy('nama'),
                fn ($query) => $query->orderBy('nama')
            );

        $employees = $employeesQuery->paginate(25)->withQueryString();
        $currentRates = SalaryConfig::query()
            ->whereIn('nip', $employees->pluck('nip')->filter()->unique())
            ->where('kategori_gaji', 'harian')
            ->whereDate('berlaku_dari', '<=', now()->toDateString())
            ->orderByDesc('berlaku_dari')
            ->orderByDesc('id')
            ->get()
            ->unique('nip')
            ->keyBy('nip');

        $bagianList = User::query()
            ->where('is_active', true)
            ->where('kategori_gaji', 'harian')
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '')
            ->distinct()
            ->orderBy('bagian')
            ->pluck('bagian');

        return view('payroll.salary-config', compact('employees', 'currentRates', 'bagianList', 'search', 'bagian', 'sort'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $employeeIds = array_map('intval', $validated['employee_ids']);
        $employees = User::query()
            ->whereIn('id', $employeeIds)
            ->where('is_active', true)
            ->where('kategori_gaji', 'harian')
            ->whereNotNull('pin')
            ->where('pin', '!=', '')
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->get();

        if ($employees->count() !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Pilih hanya karyawan aktif bergaji harian yang memiliki PIN dan NIP.',
            ]);
        }

        $rules = [];
        foreach ($employeeIds as $employeeId) {
            $rules["entries.{$employeeId}.nominal"] = ['required', 'integer', 'min:1'];
            $rules["entries.{$employeeId}.berlaku_dari"] = ['required', 'date'];
            $rules["entries.{$employeeId}.keterangan"] = ['nullable', 'string', 'max:255'];
        }

        $validatedEntries = $request->validate($rules)['entries'];
        $createdBy = Auth::guard('admin')->id();

        DB::transaction(function () use ($employees, $validatedEntries, $createdBy): void {
            foreach ($employees as $employee) {
                $entry = $validatedEntries[$employee->id];
                $config = SalaryConfig::create([
                    'pin' => $employee->pin,
                    'nip' => $employee->nip,
                    'nama' => $employee->nama,
                    'kategori_gaji' => 'harian',
                    'nominal' => $entry['nominal'],
                    'berlaku_dari' => $entry['berlaku_dari'],
                    'keterangan' => $entry['keterangan'] ?? null,
                    'created_by' => $createdBy,
                ]);

                $employee->salary_config_id = $config->id;
                $employee->save();
            }
        });

        return redirect()
            ->route('payroll.salary-config.index', $request->query())
            ->with('success', 'Tarif gaji harian berhasil disimpan dan riwayat sebelumnya tetap tersimpan.');
    }
}
