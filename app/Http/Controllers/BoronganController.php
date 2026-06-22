<?php
namespace App\Http\Controllers;

use App\Models\BoronganHarian;
use App\Models\BoronganImport;
use App\Models\BoronganRate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BoronganController extends Controller
{
    public function index()
    {
        $imports = BoronganImport::orderByDesc('created_at')->get();
        return view('borongan.index', compact('imports'));
    }

    public function create()
    {
        return view('borongan.create');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'jenis'         => 'required|in:cetak',
            'tanggal'       => 'required|date',
            'tanggal_dari'  => 'required|date',
            'tanggal_sampai'=> 'required|date|after_or_equal:tanggal_dari',
            'file'          => 'required|file|mimes:xlsx,xls',
        ]);

        $tanggal      = $request->tanggal;
        $tanggalDari  = $request->tanggal_dari;
        $tanggalSampai= $request->tanggal_sampai;
        $file    = $request->file('file');
        $path    = $file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0); // ambil sheet pertama

        $usersByNip = User::whereNotNull('nip')->get()->keyBy(fn($u) => trim($u->nip));

        $rates = BoronganRate::where('jenis', $request->jenis)
            ->orderByDesc('berlaku_dari')
            ->get();

        // Ambil rate default untuk jenis cetak (HCR Indomie = 125/gram)
        // Karena per karyawan kita tidak tahu sub-kategori, pakai rate terendah (125) sebagai baseline
        // Atau kalau hanya ada 1 rate dominan, pakai itu
        $defaultRate = $rates->first()?->rate_per_gram ?? 125;

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        // === ROBUST HEADER / COLUMN DETECTION ===
        $totalUpahCol = null;
        $totalGramCol = null;
        $upahColumns  = [];
        $namaBarangColumns = [];
        $nipCol = null;
        $namaCol = null;
        $headerRowFound = null;

        // Scan the first few rows for header labels (prefer rows 1..4)
        $scanHeaderRows = min(4, $highestRow);
        for ($r = 1; $r <= $scanHeaderRows; $r++) {
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = trim((string) $sheet->getCell($colLetter . $r)->getValue());
                $low = strtolower($val);

                if ($nipCol === null && stripos($low, 'nip') !== false) {
                    $nipCol = $c;
                    $headerRowFound = $r;
                }
                if ($namaCol === null && stripos($low, 'nama') !== false) {
                    $namaCol = $c;
                    $headerRowFound = $headerRowFound ?? $r;
                }
                if ($totalUpahCol === null && stripos($low, 'total upah') !== false) {
                    $totalUpahCol = $c;
                    $headerRowFound = $headerRowFound ?? $r;
                }
                if ($totalGramCol === null && trim(strtolower($val)) === 'total') {
                    $totalGramCol = $c;
                    $headerRowFound = $headerRowFound ?? $r;
                }
                if (stripos($low, 'upah') !== false && stripos($low, 'total') === false) {
                    $upahColumns[] = $c;
                    $headerRowFound = $headerRowFound ?? $r;
                }
                if (stripos($low, 'nama barang') !== false) {
                    $namaBarangColumns[] = $c;
                    $headerRowFound = $headerRowFound ?? $r;
                }
            }
        }

        // Fallbacks
        $nipCol = $nipCol ?? 2;
        $namaCol = $namaCol ?? 3;
        $dataStart = $headerRowFound ? $headerRowFound + 2 : 4;

        if ($tanggal < $tanggalDari || $tanggal > $tanggalSampai) {
            return back()->with('error', 'Tanggal upload harus berada di dalam periode yang dipilih.');
        }

        if (!$totalUpahCol) {
            // Try to find "Total Upah" in the entire header area more loosely
            for ($c = 1; $c <= $highestColIndex; $c++) {
                for ($r = 1; $r <= $scanHeaderRows; $r++) {
                    $val = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r)->getValue());
                    if (stripos($val, 'total') !== false && stripos($val, 'upah') !== false) {
                        $totalUpahCol = $c;
                        break 2;
                    }
                }
            }
        }

        $parsedData   = [];
        $totalBaris   = 0;
        $totalFlagged = 0;

        // Diagnostic output when ?debug=1 is present in the request
        $diagnostics = [
            'highestRow' => $highestRow,
            'highestCol' => $highestCol,
            'highestColIndex' => $highestColIndex,
            'nipCol' => $nipCol,
            'namaCol' => $namaCol,
            'totalUpahCol' => $totalUpahCol,
            'totalGramCol' => $totalGramCol,
            'upahColumns' => $upahColumns,
            'namaBarangColumns' => $namaBarangColumns,
            'headerRowFound' => $headerRowFound,
            'dataStart' => $dataStart,
        ];

        // sample first 5 data rows for quick inspection
        $sample = [];
        for ($i = 0; $i < 5; $i++) {
            $r = $dataStart + $i;
            if ($r > $highestRow) break;
            $rowVals = [];
            $maxCols = min(12, $highestColIndex);
            for ($c = 1; $c <= $maxCols; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $rowVals[$colLetter] = (string) $sheet->getCell($colLetter . $r)->getValue();
            }
            $sample[$r] = $rowVals;
        }
        $diagnostics['sample_rows'] = $sample;

        if ($request->boolean('debug')) {
            return response()->json($diagnostics);
        }

        for ($row = $dataStart; $row <= $highestRow; $row++) {
            $nip = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nipCol) . $row)->getValue());
            if (empty($nip)) continue;
            if (strtolower($nip) === 'total') continue;

            $nama = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($namaCol) . $row)->getValue());

            // Total Upah
            $totalUpah = 0;
            if ($totalUpahCol) {
                $totalUpah = (float) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalUpahCol) . $row)->getCalculatedValue();
            }

            // Total Gram
            $totalGram = 0;
            if ($totalGramCol) {
                $totalGram = (float) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalGramCol) . $row)->getCalculatedValue();
            } else {
                $startCol = 4;
                $endCol = $totalUpahCol ? $totalUpahCol - 1 : $highestColIndex;
                for ($c = $startCol; $c <= $endCol; $c++) {
                    if (in_array($c, $upahColumns) || in_array($c, $namaBarangColumns)) continue;
                    $val = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $row)->getCalculatedValue();
                    if (is_numeric($val)) {
                        $totalGram += (float) $val;
                    }
                }
            }

            $user = $usersByNip[$nip] ?? null;
            $isFlagged = false;
            $flagReason = null;
            if (!$user) {
                $isFlagged = true;
                $flagReason = 'NIP tidak ditemukan di master karyawan';
            }

            $selisih = (int) ($totalUpah - ($totalGram * $defaultRate));
            if (!$isFlagged && abs($selisih) > 1000) {
                $isFlagged = true;
                $flagReason = 'Selisih upah: sistem Rp ' . number_format($totalGram * $defaultRate) . ' vs file Rp ' . number_format($totalUpah);
            }

            $parsedData[] = [
                'pin'         => $user->pin ?? null,
                'nip'         => $nip,
                'nama'        => $user->nama ?? $nama,
                'tanggal'     => $tanggal,
                'kategori'    => $request->jenis,
                'berat_gram'  => (int) $totalGram,
                'upah_sistem' => (int) ($totalGram * $defaultRate),
                'upah_file'   => (int) $totalUpah,
                'selisih'     => $selisih,
                'is_flagged'  => $isFlagged,
                'flag_reason' => $flagReason,
            ];

            $totalBaris++;
            if ($isFlagged) $totalFlagged++;
        }

        if ($totalBaris === 0) {
            return back()->with('error', 'Tidak ada data yang berhasil diparse. Cek kembali format file.');
        }

        DB::beginTransaction();
        try {
            $existing = BoronganImport::where('jenis', $request->jenis)
                ->where('tanggal_dari', $tanggalDari)
                ->where('tanggal_sampai', $tanggalSampai)
                ->first();

            if ($existing) {
                $oldRows = BoronganHarian::where('borongan_import_id', $existing->id)
                    ->where('tanggal', $tanggal);
                $oldCount = $oldRows->count();
                $oldFlagged = (clone $oldRows)->where('is_flagged', true)->count();
                $oldRows->delete();

                $existing->total_baris = max(0, $existing->total_baris - $oldCount + $totalBaris);
                $existing->total_flagged = max(0, $existing->total_flagged - $oldFlagged + $totalFlagged);
                $existing->filename = $file->getClientOriginalName();
                $existing->status = 'pending';
                $existing->save();
                $import = $existing;
            } else {
                $import = BoronganImport::create([
                    'jenis'          => $request->jenis,
                    'filename'       => $file->getClientOriginalName(),
                    'tanggal_dari'   => $tanggalDari,
                    'tanggal_sampai' => $tanggalSampai,
                    'total_baris'    => $totalBaris,
                    'total_flagged'  => $totalFlagged,
                    'status'         => 'pending',
                    'uploaded_by'    => Auth::guard('admin')->id(),
                ]);
            }

            foreach ($parsedData as $row) {
                $row['borongan_import_id'] = $import->id;
                $row['status'] = 'pending';
                BoronganHarian::create($row);
            }

            DB::commit();
            $msg = $existing
                ? "Data tanggal {$tanggal} berhasil di-replace. {$totalBaris} baris diproses, {$totalFlagged} perlu direview."
                : "Berhasil parse {$totalBaris} baris untuk tanggal {$tanggal}, {$totalFlagged} perlu direview.";

            return redirect()->route('borongan.review', $import->id)->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal parse file: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        $import = BoronganImport::findOrFail($id);
        $items  = BoronganHarian::where('borongan_import_id', $id)
            ->orderBy('nama')
            ->orderByDesc('is_flagged')
            ->orderBy('tanggal')
            ->get();

        return view('borongan.review', compact('import', 'items'));
    }

    public function approve($id)
    {
        $import = BoronganImport::findOrFail($id);

        BoronganHarian::where('borongan_import_id', $id)->update(['status' => 'approved']);
        $import->update(['status' => 'approved']);

        return redirect()->route('borongan.index')
            ->with('success', 'Data borongan berhasil diapprove dan siap digabung ke payroll.');
    }

    public function destroy($id)
    {
        $import = BoronganImport::findOrFail($id);
        if ($import->status === 'approved') {
            return back()->with('error', 'Import yang sudah approved tidak bisa dihapus.');
        }
        $import->delete();
        return redirect()->route('borongan.index')->with('success', 'Import berhasil dihapus.');
    }
}
