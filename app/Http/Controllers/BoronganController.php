<?php
namespace App\Http\Controllers;

use App\Models\BoronganHarian;
use App\Models\BoronganImport;
use App\Models\BoronganRate;
use App\Models\BoronganRekap;
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
            'jenis'         => 'required|in:cetak,cabut,moulding',
            'payroll_id'    => 'nullable|exists:payrolls,id',
            'tanggal'       => 'required|date',
            'tanggal_dari'  => 'required|date',
            'tanggal_sampai'=> 'required|date|after_or_equal:tanggal_dari',
            'file'          => 'required_if:jenis,cetak,cabut|file|mimes:xlsx,xls',
            'file_kategori' => 'required_if:jenis,moulding|file|mimes:xlsx,xls',
            'file_crosscheck' => 'required_if:jenis,moulding|file|mimes:xlsx,xls',
        ]);

        $tanggal      = $request->tanggal;
        $tanggalDari  = $request->tanggal_dari;
        $tanggalSampai= $request->tanggal_sampai;

        // Load single file hanya untuk cetak/cabut; moulding akan load 2 file terpisah di branch-nya
        if ($request->jenis === 'moulding') {
            $file    = null;
            $path    = null;
            $spreadsheet = null;
            $sheet   = null;
        } else {
            $file    = $request->file('file');
            $path    = $file->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheet(0); // ambil sheet pertama
        }

        $usersByNip = User::whereNotNull('nip')->get()->keyBy(fn($u) => trim($u->nip));
        $rates = BoronganRate::where('jenis', $request->jenis)
            ->orderByDesc('berlaku_dari')
            ->get();

        $highestRow = $sheet?->getHighestRow() ?? 0;
        $highestCol = $sheet?->getHighestColumn() ?? 'A';
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $parsedData   = [];
        $totalBaris   = 0;
        $totalFlagged = 0;

        if ($tanggal < $tanggalDari || $tanggal > $tanggalSampai) {
            return back()->with('error', 'Tanggal upload harus berada di dalam periode yang dipilih.');
        }

        if ($request->jenis === 'cabut') {
            // Scan header untuk marker: "Upah Beri" dan "Bulu" (sisi kanan, uncolored)
            // Prioritas: scan dari right-to-left untuk pastikan ambil kolom yang benar
            $columns = [
                'nip'         => null,
                'nama'        => null,
                'nojob'       => null,
                'gram'        => null,
                'upah'        => null,  // Upah Beri
                'bulu'        => null,  // Kategori
                'keterangan'  => null,
            ];
            $headerRowFound = null;

            // Cari header row dulu
            for ($r = 1; $r <= min(8, $highestRow); $r++) {
                $upahBeriFound = false;
                $buluFound = false;
                
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $val = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r)->getValue());
                    $low = strtolower($val);

                    if (stripos($low, 'upah') !== false && stripos($low, 'beri') !== false) {
                        $upahBeriFound = true;
                    }
                    if (trim($low) === 'bulu') {
                        $buluFound = true;
                    }
                }

                // Jika ketemu marker utama, ini header row
                if ($upahBeriFound || $buluFound) {
                    $headerRowFound = $r;
                    break;
                }
            }

            if (!$headerRowFound) {
                return back()->with('error', 'Header row tidak ditemukan. Pastikan file memiliki kolom "Upah Beri" dan "Bulu".');
            }

            $dataStart = $headerRowFound + 1;

            for ($c = $highestColIndex; $c >= 1; $c--) {
                $val = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $headerRowFound)->getValue());
                $low = strtolower($val);

                // Sisi kanan (uncolored) - prioritas tinggi
                if ($columns['bulu'] === null && trim($low) === 'bulu') {
                    $columns['bulu'] = $c;
                }
                // Cari kolom upah yang paling kanan dengan header "Upah Beri" atau "Upah" (tapi bukan di kolom E yang berisi dash)
                if ($columns['upah'] === null && stripos($low, 'upah') !== false && $c > 10) {  // Only search rightside
                    $columns['upah'] = $c;
                }
                if ($columns['keterangan'] === null && (stripos($low, 'keterangan') !== false || stripos($low, 'ket') !== false)) {
                    $columns['keterangan'] = $c;
                }
                if ($columns['nip'] === null && (stripos($low, 'nip') !== false && stripos($low, 'kolom') === false)) {
                    $columns['nip'] = $c;
                }
                if ($columns['nama'] === null && (stripos($low, 'nmbrg') !== false || (stripos($low, 'nama') !== false && stripos($low, 'karyawan') === false))) {
                    $columns['nama'] = $c;
                }
                if ($columns['gram'] === null && (stripos($low, 'gri') !== false || (stripos($low, 'gram') !== false && stripos($low, 'gram') === 0))) {
                    $columns['gram'] = $c;
                }
                if ($columns['nojob'] === null && (stripos($low, 'nojob') !== false || stripos($low, 'noj') !== false)) {
                    $columns['nojob'] = $c;
                }
            }

            // Fallback jika ada yang tidak ketemu
            if ($columns['nip'] === null) $columns['nip'] = 1;
            if ($columns['nama'] === null) $columns['nama'] = 2;
            if ($columns['gram'] === null) $columns['gram'] = 3;
            if ($columns['upah'] === null) {
                // Cari kolom upah dengan scan kolom numerik di row pertama setelah grammar
                for ($c = $highestColIndex; $c > ($columns['gram'] ?? 3); $c--) {
                    $testVal = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $dataStart)->getCalculatedValue();
                    if (is_numeric($testVal) && $testVal > 0) {
                        $columns['upah'] = $c;
                        break;
                    }
                }
                if ($columns['upah'] === null) $columns['upah'] = 5;
            }

            // DEBUG: Log detected columns
            if ($request->boolean('debug')) {
                return response()->json([
                    'header_row' => $headerRowFound,
                    'columns' => [
                        'nip' => $columns['nip'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nip']) : null,
                        'nama' => $columns['nama'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nama']) : null,
                        'nojob' => $columns['nojob'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nojob']) : null,
                        'gram' => $columns['gram'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['gram']) : null,
                        'upah' => $columns['upah'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['upah']) : null,
                        'bulu' => $columns['bulu'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['bulu']) : null,
                        'keterangan' => $columns['keterangan'] ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['keterangan']) : null,
                    ],
                    'column_indices' => $columns,
                    'data_start_row' => $dataStart,
                    'sample_row_1' => [
                        'nip' => trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nip'] ?? 1) . ($dataStart))->getValue()),
                        'gram' => $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['gram'] ?? 3) . ($dataStart))->getCalculatedValue(),
                        'upah' => $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['upah'] ?? 5) . ($dataStart))->getCalculatedValue(),
                        'bulu_value' => $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['bulu'] ?? 6) . ($dataStart))->getCalculatedValue(),
                        'bulu_formula' => trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['bulu'] ?? 6) . ($dataStart))->getValue()),
                    ],
                ]);
            }

            for ($row = $dataStart; $row <= $highestRow; $row++) {
                $nip = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nip'] ?? 1) . $row)->getValue());
                if (empty($nip)) continue;
                if (strtolower($nip) === 'total') continue;
                if (strtolower($nip) === 'nip') continue;

                $nama = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nama'] ?? 2) . $row)->getValue());
                $noJob = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nojob'] ?? 4) . $row)->getValue());
                
                // Bulu: use getCalculatedValue untuk evaluate formula
                $buluCell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['bulu'] ?? 6) . $row);
                $bulu = trim((string) $buluCell->getCalculatedValue());
                
                $keterangan = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['keterangan'] ?? 7) . $row)->getValue());
                
                $gramRaw = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['gram'] ?? 3) . $row)->getCalculatedValue();
                $upahRaw = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['upah'] ?? 5) . $row)->getCalculatedValue();

                // Parse gram: convert to int, handle dash/empty
                $gramStr = trim((string) $gramRaw);
                if ($gramStr === '-' || $gramStr === '' || strtolower($gramStr) === 'null') {
                    $totalGram = 0;
                } else {
                    $totalGram = is_numeric($gramRaw) ? (int) $gramRaw : (int) preg_replace('/[^0-9.-]/', '', $gramStr);
                }

                // Parse upah: convert to int, handle dash/empty/formula
                $upahStr = trim((string) $upahRaw);
                if ($upahStr === '-' || $upahStr === '' || strtolower($upahStr) === 'null' || stripos($upahStr, '=') === 0) {
                    $totalUpah = 0;
                } else {
                    $totalUpah = is_numeric($upahRaw) ? (int) $upahRaw : (int) preg_replace('/[^0-9.-]/', '', $upahStr);
                }

                // Skip jika gram atau upah negatif atau 0
                if ($totalGram <= 0 && $totalUpah <= 0) {
                    continue;
                }

                // Flag jika ada nilai negatif (error parsing)
                if ($totalGram < 0 || $totalUpah < 0) {
                    $parsedData[] = [
                        'pin'         => $user->pin ?? null,
                        'nip'         => $nip,
                        'nama'        => $user->nama ?? $nama,
                        'tanggal'     => $tanggal,
                        'kategori'    => 'UNKNOWN',
                        'berat_gram'  => max(0, $totalGram),
                        'upah_sistem' => 0,
                        'upah_file'   => max(0, $totalUpah),
                        'selisih'     => 0,
                        'is_flagged'  => true,
                        'flag_reason' => "Nilai negatif terdeteksi - gram: {$totalGram}, upah: {$totalUpah}. Cek kolom Gram dan Upah Beri.",
                    ];
                    $totalBaris++;
                    $totalFlagged++;
                    continue;
                }

                $category = $this->normalizeBuluCategory($bulu);
                $rate = $this->findRateForCategory($rates, $category);
                $user = $usersByNip[$nip] ?? null;
                $isFlagged = false;
                $flagReason = null;

                if (!$user) {
                    $isFlagged = true;
                    $flagReason = 'NIP tidak ditemukan di master karyawan';
                }
                if ($category === 'UNKNOWN') {
                    $isFlagged = true;
                    $flagReason = 'Kategori Bulu tidak dikenali: ' . $bulu;
                }

                $upahSistem = (int) ($totalGram * $rate);
                $selisih = $totalUpah - $upahSistem;
                if (!$isFlagged && abs($selisih) > 1000) {
                    $isFlagged = true;
                    $flagReason = 'Selisih upah: sistem Rp ' . number_format($upahSistem) . ' vs file Rp ' . number_format($totalUpah);
                }

                $parsedData[] = [
                    'pin'         => $user->pin ?? null,
                    'nip'         => $nip,
                    'nama'        => $user->nama ?? $nama,
                    'tanggal'     => $tanggal,
                    'kategori'    => $category,
                    'berat_gram'  => $totalGram,
                    'upah_sistem' => $upahSistem,
                    'upah_file'   => $totalUpah,
                    'selisih'     => $selisih,
                    'is_flagged'  => $isFlagged,
                    'flag_reason' => $flagReason,
                ];

                $totalBaris++;
                if ($isFlagged) $totalFlagged++;
            }
        } elseif ($request->jenis === 'cetak') {
            $totalUpahCol = null;
            $totalGramCol = null;
            $upahColumns  = [];
            $namaBarangColumns = [];
            $nipCol = null;
            $namaCol = null;
            $headerRowFound = null;

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

            $nipCol = $nipCol ?? 2;
            $namaCol = $namaCol ?? 3;
            $dataStart = $headerRowFound ? $headerRowFound + 2 : 4;

            if (!$totalUpahCol) {
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

            $defaultRate = $rates->first()?->rate_per_gram ?? 125;

            for ($row = $dataStart; $row <= $highestRow; $row++) {
                $nip = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nipCol) . $row)->getValue());
                if (empty($nip)) continue;
                if (strtolower($nip) === 'total') continue;

                $nama = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($namaCol) . $row)->getValue());

                $totalUpah = 0;
                if ($totalUpahCol) {
                    $totalUpah = (float) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalUpahCol) . $row)->getCalculatedValue();
                }

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
        } elseif ($request->jenis === 'moulding') {
            // === PARSE FILE 1: file_kategori ===
            $file1 = $request->file('file_kategori');
            $spreadsheet1 = IOFactory::load($file1->getRealPath());
            $sheet1 = $spreadsheet1->getSheet(0);
            $highestRow1 = $sheet1->getHighestRow();
            $highestColIndex1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet1->getHighestColumn());
            
            // Cari header row (baris 1-3) dengan NIP di B dan NAMA di C
            $headerRow3 = null;
            for ($r = 1; $r <= min(3, $highestRow1); $r++) {
                $nipVal = trim(strtolower((string) $sheet1->getCell('B' . $r)->getValue()));
                $namaVal = trim(strtolower((string) $sheet1->getCell('C' . $r)->getValue()));
                if ($nipVal === 'nip' && $namaVal === 'nama') {
                    $headerRow3 = $r;
                    break;
                }
            }
            
            if (!$headerRow3) {
                return back()->with('error', 'Moulding File 1: Header row tidak ditemukan (cari NIP di B dan NAMA di C).');
            }
            
            // Update validCategories dengan 9 kategori final
            $validCategories = ['nat sbg', 'sbg', 'sbg waj', 'nat waj', 'vip waj', 'pt', 'gpu normal', 'gpu rendaman', 'mk dj'];
            $categoryColumns = [];  // array: header_lower => column_index
            $totalGramColFile1 = null;
            
            // Cari kategori dan "Σ Berat" dengan logika baru: kategori harus match validCategories DAN baris rate ($headerRow3-1) harus numeric > 0
            $rateRowNum = $headerRow3 - 1;
            
            for ($c = 1; $c <= $highestColIndex1; $c++) {
                $headerVal = trim((string) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $headerRow3)->getValue());
                $headerLower = trim(strtolower(preg_replace('/\s+/', ' ', $headerVal)));
                
                // Skip kolom dengan 'hcr' atau 'indomie' di header
                if (stripos($headerLower, 'hcr') !== false || stripos($headerLower, 'indomie') !== false) {
                    continue;
                }
                
                // Cek jika header ini match salah satu validCategories DAN rate row berisi numeric > 0
                $rateRowVal = (float) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $rateRowNum)->getCalculatedValue();
                
                foreach ($validCategories as $cat) {
                    if ($headerLower === strtolower($cat) && is_numeric($rateRowVal) && $rateRowVal > 0) {
                        $categoryColumns[$headerLower] = $c;
                        break;
                    }
                }
                
                // Cek jika ini "Σ Berat" - catat sebagai kolom paling kanan (terakhir yang ditemukan)
                if (trim(strtolower($headerVal)) === 'σ berat') {
                    $totalGramColFile1 = $c;
                }
            }
            
            // Tentukan data start row (cari baris pertama dengan NIP valid seperti LMG-)
            $dataStartRow = $headerRow3 + 1;
            for ($r = $headerRow3 + 1; $r <= min($headerRow3 + 5, $highestRow1); $r++) {
                $nipCell = trim((string) $sheet1->getCell('B' . $r)->getValue());
                if (preg_match('/^LMG-/', $nipCell) || !empty($nipCell)) {
                    $dataStartRow = $r;
                    break;
                }
            }
            
            // Parse file 1 data
            $file1Data = [];  // $file1Data[$nip] = ['nama' => ..., 'categories_gram' => [...]]
            for ($row = $dataStartRow; $row <= $highestRow1; $row++) {
                $nip = trim((string) $sheet1->getCell('B' . $row)->getValue());
                if (empty($nip) || strtolower($nip) === 'total') continue;
                
                $nama = trim((string) $sheet1->getCell('C' . $row)->getValue());
                $categoriesGram = [];
                $totalGramRow = 0;
                
                // Ambil gram per kategori
                foreach ($categoryColumns as $catName => $colIdx) {
                    $gramVal = (int) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row)->getCalculatedValue();
                    if ($gramVal > 0) {
                        $categoriesGram[$catName] = $gramVal;
                        $totalGramRow += $gramVal;
                    }
                }
                
                // Skip jika semua kategori = 0
                if ($totalGramRow === 0) continue;
                
                $file1Data[$nip] = [
                    'nama' => $nama,
                    'categories_gram' => $categoriesGram,
                    'total_gram' => $totalGramRow,
                ];
            }
            
            // === PARSE FILE 2: file_crosscheck ===
            $file2 = $request->file('file_crosscheck');
            $spreadsheet2 = IOFactory::load($file2->getRealPath());
            $sheet2 = $spreadsheet2->getSheet(0);
            $highestRow2 = $sheet2->getHighestRow();
            $highestColIndex2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet2->getHighestColumn());
            
            // Cari kolom di baris 1: EMP NO, RECEIVED QTY, Gaji Karyawan, EMP NAME
            $empNoCol = null;
            $receivedQtyCol = null;
            $gajiCol = null;
            $empNameCol = null;
            
            for ($c = 1; $c <= $highestColIndex2; $c++) {
                $headerVal = trim(strtolower((string) $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . '1')->getValue()));
                if (stripos($headerVal, 'emp no') !== false) $empNoCol = $c;
                if (stripos($headerVal, 'received qty') !== false) $receivedQtyCol = $c;
                if (stripos($headerVal, 'gaji') !== false && stripos($headerVal, 'karyawan') !== false) $gajiCol = $c;
                if (stripos($headerVal, 'emp name') !== false) $empNameCol = $c;
            }
            
            // Parse file 2 - aggregate per NIP
            $crosscheckByNip = [];  // $crosscheckByNip[$nip] = ['total_qty' => ..., 'total_gaji' => ...]
            for ($row = 2; $row <= $highestRow2; $row++) {
                $empNo = trim((string) ($empNoCol ? $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empNoCol) . $row)->getValue() : ''));
                if (empty($empNo)) continue;
                
                $qty = (int) ($receivedQtyCol ? $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($receivedQtyCol) . $row)->getCalculatedValue() : 0);
                $gaji = (int) ($gajiCol ? $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($gajiCol) . $row)->getCalculatedValue() : 0);
                
                if (!isset($crosscheckByNip[$empNo])) {
                    $crosscheckByNip[$empNo] = ['total_qty' => 0, 'total_gaji' => 0];
                }
                $crosscheckByNip[$empNo]['total_qty'] += $qty;
                $crosscheckByNip[$empNo]['total_gaji'] += $gaji;
            }
            
            // === COMBINE FILE 1 + FILE 2 DATA ===
            // Build rate mapping: kode_kategori => rate
            $rateMap = [];
            foreach ($rates as $rate) {
                $kodeKey = strtoupper(str_replace(' ', '_', $rate->kode_kategori));
                $rateMap[$kodeKey] = $rate->rate_per_gram;
            }
            
            foreach ($file1Data as $nip => $data) {
                $categoriesGram = $data['categories_gram'];
                $totalGramFile1 = $data['total_gram'];
                
                // Hitung upah_sistem
                $upahSistem = 0;
                $categoryMissing = [];
                foreach ($categoriesGram as $catName => $gram) {
                    $kodeKey = strtoupper(str_replace(' ', '_', $catName));
                    if (isset($rateMap[$kodeKey])) {
                        $upahSistem += $gram * $rateMap[$kodeKey];
                    } else {
                        $categoryMissing[] = $catName;
                    }
                }
                
                // Kategori utama (gram terbesar)
                $mainCategory = key($categoriesGram);  // Get first key (bisa diperbaiki jika perlu yang gram terbesar)
                if (count($categoriesGram) > 1) {
                    arsort($categoriesGram);
                    $mainCategory = key($categoriesGram);
                }
                
                // Ambil data dari crosscheck
                $crosscheckData = $crosscheckByNip[$nip] ?? null;
                $upahFile = $crosscheckData['total_gaji'] ?? 0;
                $totalQtyFile2 = $crosscheckData['total_qty'] ?? null;
                
                // Set flags
                $isFlagged = false;
                $flagReason = null;
                
                if ($totalQtyFile2 === null) {
                    $isFlagged = true;
                    $flagReason = 'NIP tidak ditemukan di file cross-check';
                } elseif ($totalGramFile1 !== $totalQtyFile2) {
                    $isFlagged = true;
                    $flagReason = 'Selisih gram: file kategori ' . $totalGramFile1 . ' vs file cross-check ' . $totalQtyFile2;
                }
                
                if (!empty($categoryMissing)) {
                    $isFlagged = true;
                    $flagReason = ($flagReason ? $flagReason . '; ' : '') . 'Rate untuk kategori ' . implode(', ', $categoryMissing) . ' tidak ditemukan';
                }
                
                $user = $usersByNip[$nip] ?? null;
                if (!$user) {
                    $isFlagged = true;
                    $flagReason = ($flagReason ? $flagReason . '; ' : '') . 'NIP tidak ditemukan di master karyawan';
                }
                
                $selisih = $upahSistem - $upahFile;
                
                $parsedData[] = [
                    'pin'         => $user->pin ?? null,
                    'nip'         => $nip,
                    'nama'        => $user->nama ?? ($data['nama'] ?? $nip),
                    'tanggal'     => $tanggal,
                    'kategori'    => $mainCategory ?? 'UNKNOWN',
                    'berat_gram'  => $totalGramFile1,
                    'upah_sistem' => $upahSistem,
                    'upah_file'   => $upahFile,
                    'selisih'     => $selisih,
                    'is_flagged'  => $isFlagged,
                    'flag_reason' => $flagReason,
                ];
                
                $totalBaris++;
                if ($isFlagged) $totalFlagged++;
            }
        }

        if ($totalBaris === 0) {
            return back()->with('error', 'Tidak ada data yang berhasil diparse. Cek kembali format file.');
        }

        DB::beginTransaction();
        try {
            // Tentukan filename: untuk moulding pakai file_kategori, untuk lainnya pakai file
            if ($request->jenis === 'moulding') {
                $fileForName = $request->file('file_kategori');
            } else {
                $fileForName = $file;
            }
            
            // Selalu buat import baru, jangan reuse/replace existing
            $import = BoronganImport::create([
                'jenis'          => $request->jenis,
                'payroll_id'     => $request->payroll_id,
                'filename'       => $fileForName->getClientOriginalName(),
                'tanggal_dari'   => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
                'total_baris'    => $totalBaris,
                'total_flagged'  => $totalFlagged,
                'status'         => 'pending',
                'uploaded_by'    => Auth::guard('admin')->id(),
            ]);

            foreach ($parsedData as $row) {
                $row['borongan_import_id'] = $import->id;
                $row['status'] = 'pending';
                BoronganHarian::create($row);
            }

            DB::commit();
            $msg = "Berhasil parse {$totalBaris} baris untuk tanggal {$tanggal}, {$totalFlagged} perlu direview.";

            return redirect()->route('borongan.review', $import->id)->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal parse file: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        $import = BoronganImport::findOrFail($id);

        // Group by NIP, aggregate total gram & upah, flag jika ada yg flagged
        $items = BoronganHarian::where('borongan_import_id', $id)
            ->get()
            ->groupBy('nip')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'nip'         => $first->nip,
                    'nama'        => $first->nama,
                    'total_gram'  => $rows->sum('berat_gram'),
                    'total_upah'  => $rows->sum('upah_file'),
                    'is_flagged'  => $rows->contains('is_flagged', true),
                    'flag_count'  => $rows->where('is_flagged', true)->count(),
                ];
            })
            ->sortBy('nama')
            ->values();

        return view('borongan.review', compact('import', 'items'));
    }

    public function getReviewDetail(Request $request, $id, $nip)
    {
        $import = BoronganImport::findOrFail($id);

        $rows = BoronganHarian::where('borongan_import_id', $id)
            ->where('nip', $nip)
            ->orderBy('tanggal')
            ->orderBy('kategori')
            ->get();

        // Group by tanggal
        $byTanggal = $rows->groupBy(fn($r) => \Carbon\Carbon::parse($r->tanggal)->format('Y-m-d'));

        $detail = [];
        foreach ($byTanggal as $tgl => $jobs) {
            $detail[] = [
                'tanggal'    => $tgl,
                'jobs'       => $jobs->map(fn($j) => [
                    'kategori'   => $j->kategori,
                    'gram'       => $j->berat_gram,
                    'upah_file'  => $j->upah_file,
                    'upah_sistem'=> $j->upah_sistem,
                    'selisih'    => $j->selisih,
                    'is_flagged' => $j->is_flagged,
                    'flag_reason'=> $j->flag_reason,
                ])->values(),
                'total_gram' => $jobs->sum('berat_gram'),
                'total_upah' => $jobs->sum('upah_file'),
            ];
        }

        return response()->json([
            'nip'    => $nip,
            'nama'   => $rows->first()?->nama ?? $nip,
            'detail' => $detail,
        ]);
    }

    public function approve($id)
    {
        $import = BoronganImport::findOrFail($id);

        // Group borongan_harian by NIP → akumulasi gram & upah
        $grouped = BoronganHarian::where('borongan_import_id', $id)
            ->get()
            ->groupBy('nip');

        foreach ($grouped as $nip => $rows) {
            $first = $rows->first();
            $totalGram = $rows->sum('berat_gram');
            $totalUpah = $rows->sum('upah_file');

            // Upsert rekap — kalau sudah ada (re-approve), update akumulasi tapi jaga potongan/tambahan
            $existing = BoronganRekap::where('borongan_import_id', $id)
                ->where('nip', $nip)
                ->first();

            if ($existing) {
                $existing->total_gram = $totalGram;
                $existing->total_upah = $totalUpah;
                $existing->total_akhir = $totalUpah + $existing->tambahan
                    - $existing->potongan_bpjs
                    - $existing->potongan_lain;
                $existing->save();
            } else {
                BoronganRekap::create([
                    'borongan_import_id' => $id,
                    'pin'                => $first->pin,
                    'nip'                => $nip,
                    'nama'               => $first->nama,
                    'periode_dari'       => $import->tanggal_dari,
                    'periode_sampai'     => $import->tanggal_sampai,
                    'total_gram'         => $totalGram,
                    'total_upah'         => $totalUpah,
                    'potongan_bpjs'      => 0,
                    'potongan_lain'      => 0,
                    'tambahan'           => 0,
                    'total_akhir'        => $totalUpah,
                    'status'             => 'draft',
                ]);
            }
        }

        BoronganHarian::where('borongan_import_id', $id)->update(['status' => 'approved']);
        $import->update(['status' => 'approved']);

        return redirect()->route('borongan.rekapIndex', $id)
            ->with('success', 'Data borongan berhasil diapprove. Rekap per karyawan sudah dibuat.');
    }

    public function updateRekap(Request $request, $rekapId)
    {
        $rekap = BoronganRekap::findOrFail($rekapId);

        $request->validate([
            'potongan_bpjs' => 'nullable|integer|min:0',
            'potongan_lain' => 'nullable|integer|min:0',
            'tambahan'      => 'nullable|integer|min:0',
            'keterangan'    => 'nullable|string|max:255',
        ]);

        $rekap->potongan_bpjs = $request->potongan_bpjs ?? 0;
        $rekap->potongan_lain = $request->potongan_lain ?? 0;
        $rekap->tambahan = $request->tambahan ?? 0;
        $rekap->keterangan = $request->keterangan;
        $rekap->total_akhir = $rekap->total_upah
            + $rekap->tambahan
            - $rekap->potongan_bpjs
            - $rekap->potongan_lain;
        $rekap->updated_by = Auth::guard('admin')->id();
        $rekap->save();

        return response()->json([
            'success'     => true,
            'total_akhir' => $rekap->total_akhir,
        ]);
    }

    public function rekapIndex($id)
    {
        $import = BoronganImport::findOrFail($id);
        $rekaps = BoronganRekap::where('borongan_import_id', $id)
            ->orderBy('nama')
            ->get();

        return view('borongan.rekap', compact('import', 'rekaps'));
    }

    public function getDetail(Request $request, $id, $nip)
    {
        $import = BoronganImport::findOrFail($id);

        // Data borongan harian - groupBy tanggal agar semua entries terakumulasi
        $harianGrouped = BoronganHarian::where('borongan_import_id', $id)
            ->where('nip', $nip)
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn($h) => \Carbon\Carbon::parse($h->tanggal)->format('Y-m-d'));

        // Rekap
        $rekap = BoronganRekap::where('borongan_import_id', $id)
            ->where('nip', $nip)
            ->first();

        // Absensi dari attendance_logs
        $user = User::where('nip', $nip)->first();
        $attendanceLogs = [];
        $absenceNotes = [];

        if ($user) {
            $logs = \App\Models\AttendanceLog::where('pin', $user->pin)
                ->whereBetween('tanggal', [$import->tanggal_dari, $import->tanggal_sampai])
                ->orderBy('datetime')
                ->get()
                ->groupBy(fn($l) => substr((string)$l->tanggal, 0, 10));

            $notes = \App\Models\AbsenceNote::where('pin', $user->pin)
                ->whereBetween('date', [$import->tanggal_dari, $import->tanggal_sampai])
                ->get()
                ->keyBy(fn($n) => $n->date->format('Y-m-d'));

            // Build per-hari
            $periode = [];
            $cur = new \DateTime($import->tanggal_dari->format('Y-m-d'));
            $end = new \DateTime($import->tanggal_sampai->format('Y-m-d'));
            while ($cur <= $end) {
                $tgl = $cur->format('Y-m-d');
                $isSunday = date('N', strtotime($tgl)) == 7;

                $dayLogs = $logs[$tgl] ?? collect();
                $inTimes = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

                $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
                $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

                $note = $notes[$tgl] ?? null;
                
                // Accumulate gram & upah dari semua entries untuk tanggal ini
                $boronganRows = $harianGrouped[$tgl] ?? collect();
                $totalGram = $boronganRows->sum('berat_gram');
                $totalUpah = $boronganRows->sum('upah_file');

                $periode[] = [
                    'tanggal' => $tgl,
                    'is_sunday' => $isSunday,
                    'jam_in' => $inTs ? date('H:i', $inTs) : null,
                    'jam_out' => $outTs ? date('H:i', $outTs) : null,
                    'keterangan' => $note ? $note->code : null,
                    'gram' => $totalGram,
                    'upah' => $totalUpah,
                    'hadir' => ($inTs || $outTs) ? true : false,
                ];

                $cur->modify('+1 day');
            }

            $attendanceLogs = $periode;
        }

        return response()->json([
            'rekap' => $rekap,
            'harian' => $attendanceLogs,
            'nama' => $rekap?->nama ?? $nip,
        ]);
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

    public function undo($id)
    {
        $import = BoronganImport::findOrFail($id);
        if ($import->status === 'approved') {
            return back()->with('error', 'Import yang sudah approved tidak bisa di-undo.');
        }
        
        // Delete all borongan_harian for this import
        BoronganHarian::where('borongan_import_id', $id)->delete();
        
        // Delete the import
        $import->delete();
        
        return redirect()->route('borongan.index')->with('success', 'Upload berhasil di-undo. Semua data telah dihapus.');
    }

    private function normalizeBuluCategory(string $bulu): string
    {
        $value = strtoupper(trim($bulu));
        $value = preg_replace('/[^A-Z0-9 ]+/', '', $value);

        if (str_contains($value, 'VIP')) {
            return 'VIP';
        }
        if (str_contains($value, 'BS A') || str_contains($value, 'BSA')) {
            return 'BS_A';
        }
        if (str_contains($value, 'BS B') || str_contains($value, 'BSB')) {
            return 'BS_B';
        }
        if (str_contains($value, 'BS C') || str_contains($value, 'BSC')) {
            return 'BS_C';
        }

        return 'UNKNOWN';
    }

    private function findRateForCategory($rates, string $category): int
    {
        if ($category === 'UNKNOWN') {
            return 0;
        }

        $match = $rates->firstWhere('kode_kategori', $category);
        if ($match) {
            return (int) $match->rate_per_gram;
        }

        $fallback = $rates->first();
        return $fallback ? (int) $fallback->rate_per_gram : 693;
    }
}
