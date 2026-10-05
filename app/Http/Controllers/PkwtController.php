<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PkwtExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PkwtController extends Controller
{
    private const PIHAK_PERTAMA_NAMA = 'Kidung Alfiani Sidiq';
    private const PIHAK_PERTAMA_JABATAN = 'HRD';
    private const BASELINE_NOMOR = 149;
    public function index(Request $request)
    {
        $query = User::where('is_active', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $karyawan = $query->orderBy('nama')->get();

        // Ambil status export terakhir per user
        $exportStatus = PkwtExport::selectRaw('user_id, MAX(id) as max_id')
            ->groupBy('user_id')
            ->pluck('max_id', 'user_id');

        $lastExports = PkwtExport::whereIn('id', $exportStatus->values())
            ->get()
            ->keyBy('user_id');

        return view('pkwt.index', compact('karyawan', 'lastExports'));
    }

    /**
     * Halaman riwayat semua export PKWT.
     */
    public function riwayat(Request $request)
    {
        $query = PkwtExport::with(['user', 'pembuat']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Urutkan descending, exclude dummy row (file_path null)
        $exports = $query->whereNotNull('file_path')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pkwt.riwayat', compact('exports'));
    }

    /**
     * Tampilkan form modal (via JSON) untuk export PKWT.
     */
    public function form(User $user)
    {
        if (! $user->is_active) {
            return response()->json(['error' => 'Karyawan tidak aktif.'], 400);
        }

        return response()->json([
            'user' => [
                'id'   => $user->id,
                'nama' => $user->nama,
                'nip'  => $user->nip,
            ],
        ]);
    }

    /**
     * Generate & simpan PKWT.
     */
    public function export(Request $request, User $user)
    {
        if (! $user->is_active) {
            return back()->with('error', 'Karyawan tidak aktif.');
        }

        $request->validate([
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        // Ambil nomor urut terkecil yang belum terpakai (reuse nomor yang sudah dihapus)
        $nomorUrut = \DB::transaction(function () {
            $usedNumbers = PkwtExport::lockForUpdate()
                ->orderBy('nomor_urut')
                ->pluck('nomor_urut')
                ->toArray();

            $candidate = self::BASELINE_NOMOR + 1; // 150

            while (in_array($candidate, $usedNumbers, true)) {
                $candidate++;
            }

            return $candidate;
        });

        // Generate nomor surat
        $romawiBulan = $this->romanMonth(now()->month);
        $tahun = now()->year;
        $nomorSurat = "{$nomorUrut}/PKWT/HRGA/{$romawiBulan}/{$tahun}";

        // HRD yang login sebagai pembuat (Pihak Pertama)
        $dibuatOleh = Auth::guard('admin')->user()->id;

        // Simpan ke database
        $pkwt = PkwtExport::create([
            'user_id'        => $user->id,
            'nomor_urut'     => $nomorUrut,
            'nomor_surat'    => $nomorSurat,
            'tanggal_mulai'  => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'tanggal_dibuat' => $request->tanggal_mulai,
            'tempat_dibuat'  => 'Lamongan',
            'dibuat_oleh'    => $dibuatOleh,
        ]);

        // Generate PDF
        $pdf = $this->generatePdf($pkwt, $user);

        // Simpan file
        $filename = "pkwt/{$nomorSurat}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());
        $pkwt->update(['file_path' => $filename]);

        // Download langsung
        return response()->download(
            Storage::disk('public')->path($filename),
            str_replace('/', '_', $nomorSurat) . '.pdf'
        )->deleteFileAfterSend(false);
    }

    /**
     * Export PKWT massal untuk beberapa karyawan sekaligus.
     * Generate semua PDF dan kembalikan sebagai file ZIP.
     */
    public function exportBulk(Request $request)
    {
        $request->validate([
            'user_ids'        => 'required|array|min:1',
            'user_ids.*'      => 'exists:users,id',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $userIds = $request->user_ids;
        $users = User::whereIn('id', $userIds)->where('is_active', 1)->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Tidak ada karyawan aktif yang dipilih.');
        }

        $dibuatOleh = Auth::guard('admin')->user()->id;
        $generatedFiles = [];
        $errors = [];

        foreach ($users as $user) {
            try {
                // Ambil nomor urut terkecil yang belum terpakai
                $nomorUrut = \DB::transaction(function () {
                    $usedNumbers = PkwtExport::lockForUpdate()
                        ->orderBy('nomor_urut')
                        ->pluck('nomor_urut')
                        ->toArray();

                    $candidate = self::BASELINE_NOMOR + 1;

                    while (in_array($candidate, $usedNumbers, true)) {
                        $candidate++;
                    }

                    return $candidate;
                });

                $romawiBulan = $this->romanMonth(now()->month);
                $tahun = now()->year;
                $nomorSurat = "{$nomorUrut}/PKWT/HRGA/{$romawiBulan}/{$tahun}";

                // Simpan ke database
                $pkwt = PkwtExport::create([
                    'user_id'        => $user->id,
                    'nomor_urut'     => $nomorUrut,
                    'nomor_surat'    => $nomorSurat,
                    'tanggal_mulai'  => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                    'tanggal_dibuat' => $request->tanggal_mulai,
                    'tempat_dibuat'  => 'Lamongan',
                    'dibuat_oleh'    => $dibuatOleh,
                ]);

                // Generate PDF
                $pdf = $this->generatePdf($pkwt, $user);

                // Simpan file
                $filename = "pkwt/{$nomorSurat}.pdf";
                Storage::disk('public')->put($filename, $pdf->output());
                $pkwt->update(['file_path' => $filename]);

                $generatedFiles[] = Storage::disk('public')->path($filename);
            } catch (\Exception $e) {
                $errors[] = "{$user->nama}: {$e->getMessage()}";
            }
        }

        if (empty($generatedFiles)) {
            return back()->with('error', 'Gagal generate semua PKWT: ' . implode(', ', $errors));
        }

        // Buat ZIP
        $zipFileName = 'PKWT_Bulk_' . now()->format('Ymd_His') . '.zip';
        $zipPath = Storage::disk('public')->path('pkwt/' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($generatedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        } else {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        // Hapus file individual setelah di-zip
        foreach ($generatedFiles as $file) {
            @unlink($file);
        }

        $message = count($generatedFiles) . ' PKWT berhasil digenerate.';
        if (!empty($errors)) {
            $message .= ' Gagal: ' . implode(', ', $errors);
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download ulang PKWT dari history.
     */
    public function download(PkwtExport $pkwt)
    {
        if (! $pkwt->file_path || ! Storage::disk('public')->exists($pkwt->file_path)) {
            return back()->with('error', 'File PKWT tidak ditemukan.');
        }

        return response()->download(
            Storage::disk('public')->path($pkwt->file_path),
            str_replace('/', '_', $pkwt->nomor_surat) . '.pdf'
        );
    }

    /**
     * Generate PDF PKWT.
     */
private function generatePdf(PkwtExport $pkwt, User $user)
    {
        $data = [
            'pkwt' => $pkwt,
            'user' => $user,
            'pihakPertama' => self::PIHAK_PERTAMA_NAMA,
            'jabatanPihakPertama' => self::PIHAK_PERTAMA_JABATAN,
            'perusahaan'    => 'PT. WALET ABDILLAH JABLI',
            'alamatPerusahaan' => 'Dusun Ngingas, Desa Warukulon, Kecamatan Pucuk, Kabupaten Lamongan',
            'telpPerusahaan'   => '0857-0687-0775',
            'emailPerusahaan'  => 'waletabdillahjabli@gmail.com',
        ];

        $pdf = Pdf::loadView('pkwt.export-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Konversi angka bulan ke Romawi.
     */
    private function romanMonth(int $month): string
    {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romawi[$month] ?? '';
    }
}
