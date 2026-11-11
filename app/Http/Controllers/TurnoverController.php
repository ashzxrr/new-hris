<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;

class TurnoverController extends Controller
{
    private const RESIGN_REASONS = [
        'personal' => 'Mengundurkan diri (keinginan pribadi)',
        'contract' => 'Kontrak kerja berakhir',
        'retirement' => 'Pensiun',
        'health' => 'Kesehatan',
        'family' => 'Alasan keluarga',
        'education' => 'Melanjutkan pendidikan',
        'relocation' => 'Pindah domisili',
        'company' => 'Efisiensi/perampingan perusahaan',
        'performance' => 'Kinerja/ketidaksesuaian pekerjaan',
        'discipline' => 'Pelanggaran peraturan',
    ];

    public function index(Request $request): View
    {
        $availableYears = User::query()
            ->get(['tanggal_masuk', 'tanggal_resign'])
            ->flatMap(fn (User $employee): array => [
                $employee->tanggal_masuk?->year,
                $employee->tanggal_resign?->year,
            ])
            ->filter()
            ->map(fn (int|string $year): int => (int) $year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $latestResignDate = User::query()
            ->whereNotNull('tanggal_resign')
            ->max('tanggal_resign');
        $defaultYear = $latestResignDate
            ? CarbonImmutable::parse($latestResignDate)->year
            : $availableYears->first();

        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,'.now()->year],
            'search' => ['nullable', 'string', 'max:100'],
            'bagian' => ['nullable', 'string', 'max:100'],
        ]);
        $year = (int) ($validated['tahun'] ?? $defaultYear);
        $search = trim($validated['search'] ?? '');
        $selectedBagian = $validated['bagian'] ?? '';
        $resignReasons = self::RESIGN_REASONS;
        $startDate = CarbonImmutable::create($year, 1, 1)->toDateString();
        $endDate = CarbonImmutable::create($year, 12, 31)->toDateString();

        $employeeStartDates = User::query()
            ->whereBetween('tanggal_masuk', [$startDate, $endDate])
            ->pluck('tanggal_masuk');
        $employeeResignDates = User::query()
            ->whereBetween('tanggal_resign', [$startDate, $endDate])
            ->pluck('tanggal_resign');

        $monthlyStartCounts = $employeeStartDates
            ->map(fn ($date): int => CarbonImmutable::parse($date)->month)
            ->countBy();
        $monthlyResignCounts = $employeeResignDates
            ->map(fn ($date): int => CarbonImmutable::parse($date)->month)
            ->countBy();

        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];
        $monthlyTrend = collect(range(1, 12))
            ->map(fn (int $month): array => [
                'label' => $monthNames[$month],
                'masuk' => $monthlyStartCounts->get($month, 0),
                'resign' => $monthlyResignCounts->get($month, 0),
            ]);
        $employeeExitQuery = User::query()
            ->where('is_active', false)
            ->where(function (Builder $query) use ($startDate, $endDate): void {
                $query->whereBetween('tanggal_resign', [$startDate, $endDate])
                    ->orWhereNull('tanggal_resign');
            });

        if (filled($validated['search'] ?? null)) {
            $search = trim($validated['search']);
            $employeeExitQuery->where(function (Builder $query) use ($search): void {
                $query->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('nip', 'like', '%'.$search.'%')
                    ->orWhere('pin', 'like', '%'.$search.'%');
            });
        }

        if (filled($validated['bagian'] ?? null)) {
            $employeeExitQuery->where('bagian', $validated['bagian']);
        }

        $employeeExits = $employeeExitQuery
            ->orderByDesc('tanggal_resign')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();
        $bagianList = User::query()
            ->where('is_active', false)
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '')
            ->where('bagian', '!=', '-')
            ->distinct()
            ->orderBy('bagian')
            ->pluck('bagian');

        $reasonBreakdown = $employeeResignDates->isEmpty()
            ? collect()
            : User::query()
                ->whereBetween('tanggal_resign', [$startDate, $endDate])
                ->pluck('alasan_resign')
                ->map(fn (?string $reason): string => filled(trim((string) $reason)) ? trim($reason) : 'Belum dicatat')
                ->countBy()
                ->sortDesc();

        $activeEmployeeCount = User::query()->where('is_active', true)->count();
        $employeeStartCount = count($employeeStartDates);
        $employeeResignCount = count($employeeResignDates);
        $undatedResignCount = User::query()
            ->where('is_active', false)
            ->whereNull('tanggal_resign')
            ->count();

        return view('turnover.index', compact(
            'activeEmployeeCount',
            'availableYears',
            'bagianList',
            'employeeExits',
            'employeeResignCount',
            'employeeStartCount',
            'monthlyTrend',
            'reasonBreakdown',
            'resignReasons',
            'search',
            'selectedBagian',
            'undatedResignCount',
            'year',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', false)),
            ],
            'tahun' => ['nullable', 'integer', 'between:2000,'.now()->year],
            'search' => ['nullable', 'string', 'max:100'],
            'bagian' => ['nullable', 'string', 'max:100'],
        ]);

        $employeeRules = [];
        foreach ($validated['ids'] as $id) {
            $employeeRules["employees.{$id}.tanggal_resign"] = [
                'required',
                'date',
                'before_or_equal:today',
            ];
            $employeeRules["employees.{$id}.alasan_resign"] = [
                'required',
                Rule::in([...array_keys(self::RESIGN_REASONS), 'other']),
            ];
            $employeeRules["employees.{$id}.alasan_lainnya"] = [
                'nullable',
                'required_if:employees.'.$id.'.alasan_resign,other',
                'string',
                'max:255',
            ];
        }

        DB::transaction(function () use ($request, $validated, $employeeRules): void {
            $employees = User::query()
                ->whereKey($validated['ids'])
                ->where('is_active', false)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($employees->count() !== count($validated['ids'])) {
                throw ValidationException::withMessages([
                    'ids' => 'Sebagian karyawan tidak lagi berstatus resign. Muat ulang halaman.',
                ]);
            }

            $validator = Validator::make($request->all(), $employeeRules);
            $validator->after(function (ValidationValidator $validator) use ($employees, $validated): void {
                foreach ($validated['ids'] as $id) {
                    $dateKey = "employees.{$id}.tanggal_resign";
                    $tanggalResign = $validator->getData()['employees'][$id]['tanggal_resign'] ?? null;
                    $tanggalMasuk = $employees[$id]->tanggal_masuk?->toDateString();

                    if (
                        ! $validator->errors()->has($dateKey)
                        && $tanggalMasuk
                        && $tanggalResign < $tanggalMasuk
                    ) {
                        $validator->errors()->add($dateKey, "Tanggal resign {$employees[$id]->nama} tidak boleh sebelum tanggal masuk.");
                    }
                }
            });
            $employeeData = $validator->validate()['employees'];

            foreach ($validated['ids'] as $id) {
                $fields = $employeeData[$id];
                $reason = $fields['alasan_resign'] === 'other'
                    ? trim($fields['alasan_lainnya'])
                    : self::RESIGN_REASONS[$fields['alasan_resign']];

                $employees[$id]->update([
                    'tanggal_resign' => $fields['tanggal_resign'],
                    'alasan_resign' => $reason,
                ]);
            }
        });

        return redirect()->route('turnover.index', array_filter([
            'tahun' => $validated['tahun'] ?? null,
            'search' => $validated['search'] ?? null,
            'bagian' => $validated['bagian'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== ''))
            ->with('success', count($validated['ids']).' data riwayat resign berhasil diperbarui.');
    }
}
