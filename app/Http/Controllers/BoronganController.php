<?php
namespace App\Http\Controllers;

use App\Models\BoronganHarian;
use App\Models\BoronganImport;
use App\Models\BoronganRate;
use App\Models\BoronganRekap;
use App\Models\BoronganMutasiLog;
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
            'jenis'         => 'required|in:hcr,cabut,moulding',
            'payroll_id'    => 'nullable|exists:payrolls,id',
            'tanggal_dari'  => 'required|date',
            'tanggal_sampai'=> 'required|date|after_or_equal:tanggal_dari',
            'file'          => 'required_if:jenis,hcr,cabut|file|mimes:xlsx,xls',
            'file_kategori' => 'required_if:jenis,moulding|file|mimes:xlsx,xls',
            'file_crosscheck' => 'required_if:jenis,moulding|file|mimes:xlsx,xls',
        ]);

        $tanggalDari  = $request->tanggal_dari;
        $tanggalSampai= $request->tanggal_sampai;

        $usersByNip = User::whereNotNull('nip')->get()->keyBy(fn($u) => trim($u->nip));
        $rates = BoronganRate::where('jenis', $request->jenis)
            ->orderByDesc('berlaku_dari')
            ->get();

        $confirmRevisi = $request->boolean('confirm_revisi');
        $processedCount = 0;
        $skippedSheets = [];
        $hasilPerSheet = [];
        $duplikatDitemukan = [];

        // Helper to create import and persist parsed rows for a single sheet
        $persistSheet = function ($parsedDataSheet, $totalBarisSheet, $totalFlaggedSheet, $fileForName, $sheetLabel, $tanggalFinal) use ($request, &$processedCount, &$hasilPerSheet) {
            DB::beginTransaction();
            try {
                $import = BoronganImport::create([
                    'jenis'          => $request->jenis,
                    'payroll_id'     => $request->payroll_id,
                    'filename'       => $fileForName->getClientOriginalName() . ($sheetLabel ? ' (sheet ' . $sheetLabel . ')' : ''),
                    'tanggal_dari'   => $tanggalFinal,
                    'tanggal_sampai' => $tanggalFinal,
                    'total_baris'    => $totalBarisSheet,
                    'total_flagged'  => $totalFlaggedSheet,
                    'status'         => 'pending',
                    'uploaded_by'    => Auth::guard('admin')->id(),
                ]);

                foreach ($parsedDataSheet as $row) {
                    $row['borongan_import_id'] = $import->id;
                    $row['status'] = 'pending';
                    BoronganHarian::create($row);
                }

                DB::commit();

                $processedCount++;
                $hasilPerSheet[] = [
                    'tanggal' => $parsedDataSheet[0]['tanggal'] ?? null,
                    'import_id' => $import->id,
                    'total_baris' => $totalBarisSheet,
                    'total_flagged' => $totalFlaggedSheet,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        };

        // HCR / CABUT: single uploaded file may contain many sheets (one sheet = one tanggal)
        if ($request->jenis === 'hcr' || $request->jenis === 'cabut') {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();
            $validSheets = [];

            foreach ($sheetNames as $sheetName) {
                $sheetNameTrim = trim((string) $sheetName);
                if ($sheetNameTrim === '' || !ctype_digit($sheetNameTrim)) {
                    $skippedSheets[] = $sheetName . ' (invalid sheet name)';
                    continue;
                }

                $day = (int) $sheetNameTrim;
                try {
                    $tanggalFinal = \Carbon\Carbon::parse($tanggalDari)->setDay($day)->format('Y-m-d');
                } catch (\Exception $e) {
                    $skippedSheets[] = $sheetName . ' (invalid date)';
                    continue;
                }

                if ($tanggalFinal < $tanggalDari || $tanggalFinal > $tanggalSampai) {
                    $skippedSheets[] = $sheetName . ' (date ' . $tanggalFinal . ' out of range)';
                    continue;
                }

                $validSheets[] = ['name' => $sheetNameTrim, 'tanggal' => $tanggalFinal];
            }

            foreach ($validSheets as $sheetInfo) {
                $existingImport = BoronganImport::where('payroll_id', $request->payroll_id)
                    ->where('jenis', $request->jenis)
                    ->where('tanggal_dari', $sheetInfo['tanggal'])
                    ->first();

                if ($existingImport) {
                    $duplikatDitemukan[] = [
                        'tanggal' => $sheetInfo['tanggal'],
                        'sheet' => $sheetInfo['name'],
                        'import_lama' => [
                            'id' => $existingImport->id,
                            'filename' => $existingImport->filename,
                            'status' => $existingImport->status,
                            'created_at' => $existingImport->created_at?->toDateTimeString(),
                        ],
                    ];
                }
            }

            if (!empty($duplikatDitemukan) && !$confirmRevisi) {
                return response()->json(['need_confirmation' => true, 'duplikat' => $duplikatDitemukan], 409);
            }

            if (!empty($duplikatDitemukan) && $confirmRevisi) {
                foreach ($duplikatDitemukan as $duplikat) {
                    if ($duplikat['import_lama']['status'] === 'approved') {
                        return back()->with('error', "Tidak bisa revisi tanggal {$duplikat['tanggal']} karena data sudah di-approve. Undo Upload manual dulu.");
                    }
                }

                foreach ($duplikatDitemukan as $duplikat) {
                    $importLama = BoronganImport::find($duplikat['import_lama']['id']);
                    if ($importLama) {
                        $this->cleanupBoronganImport($importLama);
                    }
                }
            }

            foreach ($validSheets as $sheetInfo) {
                $sheetName = $sheetInfo['name'];
                $tanggalFinal = $sheetInfo['tanggal'];
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) {
                    $skippedSheets[] = $sheetName . ' (sheet not found)';
                    continue;
                }

                // Per-sheet parsing: reuse existing parsing logic but operate on $sheet and $tanggalFinal
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn() ?? 'A';
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

                $parsedDataSheet = [];
                $totalBarisSheet = 0;
                $totalFlaggedSheet = 0;

                if ($request->jenis === 'cabut') {
                    // --- existing cabut parsing, adjusted to use $sheet and $tanggalFinal ---
                    $columns = [
                        'nip'         => null,
                        'nama'        => null,
                        'nojob'       => null,
                        'gram'        => null,
                        'upah'        => null,
                        'bulu'        => null,
                        'keterangan'  => null,
                    ];
                    $headerRowFound = null;

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
                        if ($upahBeriFound || $buluFound) {
                            $headerRowFound = $r;
                            break;
                        }
                    }

                    if (!$headerRowFound) {
                        $skippedSheets[] = $sheetName . ' (header not found)';
                        continue;
                    }

                    $dataStart = $headerRowFound + 1;

                    for ($c = 1; $c <= $highestColIndex; $c++) {
                        $val = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $headerRowFound)->getValue());
                        $low = strtolower($val);

                        if ($columns['bulu'] === null && trim($low) === 'bulu') {
                            $columns['bulu'] = $c;
                        }
                        if ($columns['upah'] === null && stripos($low, 'upah') !== false && $c > 10) {
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

                    if ($columns['nip'] === null) $columns['nip'] = 1;
                    if ($columns['nama'] === null) $columns['nama'] = 2;
                    if ($columns['gram'] === null) $columns['gram'] = 3;
                    if ($columns['upah'] === null) {
                        for ($c = 1; $c <= $highestColIndex; $c++) {
                            $testVal = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $dataStart)->getCalculatedValue();
                            if (is_numeric($testVal) && $testVal > 0) {
                                $columns['upah'] = $c;
                                break;
                            }
                        }
                        if ($columns['upah'] === null) $columns['upah'] = 5;
                    }

                    $dataEnd = $dataStart;
                    $emptyStreak = 0;
                    for ($row = $dataStart; $row <= $highestRow; $row++) {
                        $nipCell = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nip']) . $row)->getValue());
                        $nipLower = strtolower($nipCell);

                        if ($nipCell === '' || $nipLower === 'total' || $nipLower === 'nip') {
                            $emptyStreak++;
                            if ($emptyStreak >= 3) {
                                break;
                            }
                            continue;
                        }

                        $emptyStreak = 0;
                        $dataEnd = $row;
                    }

                    if ($columns['nip'] === null) $columns['nip'] = 1;
                    if ($columns['nama'] === null) $columns['nama'] = 2;
                    if ($columns['gram'] === null) $columns['gram'] = 3;
                    if ($columns['upah'] === null) {
                        for ($c = $highestColIndex; $c > ($columns['gram'] ?? 3); $c--) {
                            $testVal = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $dataStart)->getCalculatedValue();
                            if (is_numeric($testVal) && $testVal > 0) {
                                $columns['upah'] = $c;
                                break;
                            }
                        }
                        if ($columns['upah'] === null) $columns['upah'] = 5;
                    }

                    if ($request->boolean('debug')) {
                        return response()->json(['sheet' => $sheetName, 'header_row' => $headerRowFound, 'columns' => $columns, 'data_end' => $dataEnd]);
                    }

                    for ($row = $dataStart; $row <= $dataEnd; $row++) {
                        $nip = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nip'] ?? 1) . $row)->getValue());
                        if (empty($nip)) continue;
                        if (strtolower($nip) === 'total') continue;
                        if (strtolower($nip) === 'nip') continue;

                        $nama = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nama'] ?? 2) . $row)->getValue());
                        $noJob = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['nojob'] ?? 4) . $row)->getValue());

                        $buluCell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['bulu'] ?? 6) . $row);
                        $bulu = trim((string) $buluCell->getCalculatedValue());

                        $keterangan = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['keterangan'] ?? 7) . $row)->getValue());

                        $gramRaw = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['gram'] ?? 3) . $row)->getCalculatedValue();
                        $upahRaw = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns['upah'] ?? 5) . $row)->getCalculatedValue();

                        $gramStr = trim((string) $gramRaw);
                        if ($gramStr === '-' || $gramStr === '' || strtolower($gramStr) === 'null') {
                            $totalGram = 0;
                        } else {
                            $totalGram = is_numeric($gramRaw) ? (int) $gramRaw : (int) preg_replace('/[^0-9.-]/', '', $gramStr);
                        }

                        $upahStr = trim((string) $upahRaw);
                        if ($upahStr === '-' || $upahStr === '' || strtolower($upahStr) === 'null' || stripos($upahStr, '=') === 0) {
                            $totalUpah = 0;
                        } else {
                            $totalUpah = is_numeric($upahRaw) ? (int) $upahRaw : (int) preg_replace('/[^0-9.-]/', '', $upahStr);
                        }

                        if ($totalGram <= 0 && $totalUpah <= 0) {
                            continue;
                        }

                        $user = $usersByNip[$nip] ?? null;

                        if ($totalGram < 0 || $totalUpah < 0) {
                            $parsedDataSheet[] = [
                                'pin'         => $user->pin ?? null,
                                'nip'         => $nip,
                                'nama'        => $user->nama ?? $nama,
                                'tanggal'     => $tanggalFinal,
                                'kategori'    => 'UNKNOWN',
                                'berat_gram'  => max(0, $totalGram),
                                'upah_sistem' => 0,
                                'upah_file'   => max(0, $totalUpah),
                                'selisih'     => 0,
                                'is_flagged'  => true,
                                'flag_reason' => "Nilai negatif terdeteksi - gram: {$totalGram}, upah: {$totalUpah}. Cek kolom Gram dan Upah Beri.",
                            ];
                            $totalBarisSheet++;
                            $totalFlaggedSheet++;
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

                        $parsedDataSheet[] = [
                            'pin'         => $user->pin ?? null,
                            'nip'         => $nip,
                            'nama'        => $user->nama ?? $nama,
                            'tanggal'     => $tanggalFinal,
                            'kategori'    => $category,
                            'berat_gram'  => $totalGram,
                            'upah_sistem' => $upahSistem,
                            'upah_file'   => $totalUpah,
                            'selisih'     => $selisih,
                            'is_flagged'  => $isFlagged,
                            'flag_reason' => $flagReason,
                        ];

                        $totalBarisSheet++;
                        if ($isFlagged) $totalFlaggedSheet++;
                    }
                } elseif ($request->jenis === 'hcr') {
                    // --- existing hcr parsing adjusted for per-sheet ---
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

                        $parsedDataSheet[] = [
                            'pin'         => $user->pin ?? null,
                            'nip'         => $nip,
                            'nama'        => $user->nama ?? $nama,
                            'tanggal'     => $tanggalFinal,
                            'kategori'    => $request->jenis,
                            'berat_gram'  => (int) $totalGram,
                            'upah_sistem' => (int) ($totalGram * $defaultRate),
                            'upah_file'   => (int) $totalUpah,
                            'selisih'     => $selisih,
                            'is_flagged'  => $isFlagged,
                            'flag_reason' => $flagReason,
                        ];

                        $totalBarisSheet++;
                        if ($isFlagged) $totalFlaggedSheet++;
                    }
                }

                // If no rows parsed, skip creating import
                if ($totalBarisSheet === 0) {
                    $skippedSheets[] = $sheetName . ' (no data parsed)';
                    continue;
                }

                // Persist this sheet's import and rows
                $persistSheet($parsedDataSheet, $totalBarisSheet, $totalFlaggedSheet, $file, $sheetNameTrim, $tanggalFinal);
            }

            // After processing all sheets, redirect with summary
            $msg = "Processed {$processedCount} sheet(s).";
            if (!empty($skippedSheets)) {
                $msg .= ' Skipped: ' . implode('; ', $skippedSheets);
            }
            return redirect()->route('borongan.index')->with('success', $msg);
        }

        // MOULDING: need to pair sheets from both uploaded files
        if ($request->jenis === 'moulding') {
            $file1 = $request->file('file_kategori');
            $file2 = $request->file('file_crosscheck');
            $spreadsheet1 = IOFactory::load($file1->getRealPath());
            $spreadsheet2 = IOFactory::load($file2->getRealPath());

            $names1 = $spreadsheet1->getSheetNames();
            $names2 = $spreadsheet2->getSheetNames();
            $common = array_intersect($names1, $names2);

            $rateMap = $rates->mapWithKeys(function ($rate) {
                return [strtoupper(str_replace(' ', '_', $rate->kode_kategori)) => (int) $rate->rate_per_gram];
            })->toArray();

            $validSheets = [];
            foreach ($common as $sheetName) {
                $sheetNameTrim = trim((string) $sheetName);
                if ($sheetNameTrim === '' || !ctype_digit($sheetNameTrim)) {
                    $skippedSheets[] = $sheetName . ' (invalid sheet name)';
                    continue;
                }

                $day = (int) $sheetNameTrim;
                try {
                    $tanggalFinal = \Carbon\Carbon::parse($tanggalDari)->setDay($day)->format('Y-m-d');
                } catch (\Exception $e) {
                    $skippedSheets[] = $sheetName . ' (invalid date)';
                    continue;
                }

                if ($tanggalFinal < $tanggalDari || $tanggalFinal > $tanggalSampai) {
                    $skippedSheets[] = $sheetName . ' (date ' . $tanggalFinal . ' out of range)';
                    continue;
                }

                $validSheets[] = ['name' => $sheetNameTrim, 'tanggal' => $tanggalFinal];
            }

            foreach ($validSheets as $sheetInfo) {
                $existingImport = BoronganImport::where('payroll_id', $request->payroll_id)
                    ->where('jenis', $request->jenis)
                    ->where('tanggal_dari', $sheetInfo['tanggal'])
                    ->first();

                if ($existingImport) {
                    $duplikatDitemukan[] = [
                        'tanggal' => $sheetInfo['tanggal'],
                        'sheet' => $sheetInfo['name'],
                        'import_lama' => [
                            'id' => $existingImport->id,
                            'filename' => $existingImport->filename,
                            'status' => $existingImport->status,
                            'created_at' => $existingImport->created_at?->toDateTimeString(),
                        ],
                    ];
                }
            }

            if (!empty($duplikatDitemukan) && !$confirmRevisi) {
                return response()->json(['need_confirmation' => true, 'duplikat' => $duplikatDitemukan], 409);
            }

            if (!empty($duplikatDitemukan) && $confirmRevisi) {
                foreach ($duplikatDitemukan as $duplikat) {
                    if ($duplikat['import_lama']['status'] === 'approved') {
                        return back()->with('error', "Tidak bisa revisi tanggal {$duplikat['tanggal']} karena data sudah di-approve. Undo Upload manual dulu.");
                    }
                }

                foreach ($duplikatDitemukan as $duplikat) {
                    $importLama = BoronganImport::find($duplikat['import_lama']['id']);
                    if ($importLama) {
                        $this->cleanupBoronganImport($importLama);
                    }
                }
            }

            foreach ($validSheets as $sheetInfo) {
                $sheetName = $sheetInfo['name'];
                $tanggalFinal = $sheetInfo['tanggal'];
                $sheet1 = $spreadsheet1->getSheetByName($sheetName);
                $sheet2 = $spreadsheet2->getSheetByName($sheetName);
                if (!$sheet1 || !$sheet2) {
                    $skippedSheets[] = $sheetName . ' (sheet not found in one of files)';
                    continue;
                }

                $highestRow1 = $sheet1->getHighestRow();
                $highestColIndex1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet1->getHighestColumn());

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
                    $skippedSheets[] = $sheetName . ' (moulding: header not found)';
                    continue;
                }

                $validCategories = ['nat sbg', 'sbg', 'sbg waj', 'nat waj', 'vip waj', 'pt', 'gpu normal', 'gpu rendaman', 'mk dj'];
                $categoryColumns = [];
                $totalGramColFile1 = null;
                $rateRowNum = $headerRow3 - 1;

                for ($c = 1; $c <= $highestColIndex1; $c++) {
                    $headerVal = trim((string) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $headerRow3)->getValue());
                    $headerLower = trim(strtolower(preg_replace('/\s+/', ' ', $headerVal)));
                    if (stripos($headerLower, 'hcr') !== false || stripos($headerLower, 'indomie') !== false) {
                        continue;
                    }
                    $rateRowVal = (float) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $rateRowNum)->getCalculatedValue();
                    foreach ($validCategories as $cat) {
                        if ($headerLower === strtolower($cat) && is_numeric($rateRowVal) && $rateRowVal > 0) {
                            $categoryColumns[$headerLower] = $c;
                            break;
                        }
                    }
                    if (trim(strtolower($headerVal)) === 'σ berat') {
                        $totalGramColFile1 = $c;
                    }
                }

                $dataStartRow = $headerRow3 + 1;
                for ($r = $headerRow3 + 1; $r <= min($headerRow3 + 5, $highestRow1); $r++) {
                    $nipCell = trim((string) $sheet1->getCell('B' . $r)->getValue());
                    if (preg_match('/^LMG-/', $nipCell) || !empty($nipCell)) {
                        $dataStartRow = $r;
                        break;
                    }
                }

                $file1Data = [];
                for ($row = $dataStartRow; $row <= $highestRow1; $row++) {
                    $nip = trim((string) $sheet1->getCell('B' . $row)->getValue());
                    if (empty($nip) || strtolower($nip) === 'total') {
                        continue;
                    }
                    $nama = trim((string) $sheet1->getCell('C' . $row)->getValue());
                    $categoriesGram = [];
                    $totalGramRow = 0;
                    foreach ($categoryColumns as $catName => $colIdx) {
                        $gramVal = (int) $sheet1->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row)->getCalculatedValue();
                        if ($gramVal > 0) {
                            $categoriesGram[$catName] = $gramVal;
                            $totalGramRow += $gramVal;
                        }
                    }
                    if ($totalGramRow === 0) {
                        continue;
                    }
                    $file1Data[$nip] = ['nama' => $nama, 'categories_gram' => $categoriesGram, 'total_gram' => $totalGramRow];
                }

                $highestRow2 = $sheet2->getHighestRow();
                $highestColIndex2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet2->getHighestColumn());
                $empNoCol = null;
                $receivedQtyCol = null;
                $empNameCol = null;
                for ($c = 1; $c <= $highestColIndex2; $c++) {
                    $headerVal = trim(strtolower((string) $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . '1')->getValue()));
                    if (stripos($headerVal, 'emp no') !== false) {
                        $empNoCol = $c;
                    }
                    if (stripos($headerVal, 'received qty') !== false) {
                        $receivedQtyCol = $c;
                    }
                    if (stripos($headerVal, 'emp name') !== false) {
                        $empNameCol = $c;
                    }
                }

                $parsedDataSheet = [];
                $totalBarisSheet = 0;
                $totalFlaggedSheet = 0;

                for ($row = 2; $row <= $highestRow2; $row++) {
                    $nip = trim((string) $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empNoCol) . $row)->getValue());
                    if (empty($nip) || strtolower($nip) === 'total') {
                        continue;
                    }
                    $nama = trim((string) $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empNameCol) . $row)->getValue());
                    $qty = (int) $sheet2->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($receivedQtyCol) . $row)->getCalculatedValue();
                    if ($qty <= 0) {
                        continue;
                    }

                    $file1Row = $file1Data[$nip] ?? null;
                    $user = $usersByNip[$nip] ?? null;
                    $isFlagged = false;
                    $flagReason = null;
                    if (!$file1Row) {
                        $isFlagged = true;
                        $flagReason = 'Tidak ditemukan di file kategori';
                    }
                    if (!$user) {
                        $isFlagged = true;
                        $flagReason = 'NIP tidak ditemukan di master karyawan';
                    }

                    $totalGram = $file1Row['total_gram'] ?? 0;
                    $upahSistem = 0;
                    if ($file1Row && !empty($file1Row['categories_gram'])) {
                        foreach ($file1Row['categories_gram'] as $catName => $gram) {
                            $rateKey = strtoupper(str_replace(' ', '_', $catName));
                            $upahSistem += ($rateMap[$rateKey] ?? 0) * $gram;
                        }
                    }

                    $parsedDataSheet[] = [
                        'pin' => $user->pin ?? null,
                        'nip' => $nip,
                        'nama' => $file1Row['nama'] ?? $nama,
                        'tanggal' => $tanggalFinal,
                        'kategori' => $request->jenis,
                        'berat_gram' => $totalGram,
                        'upah_sistem' => $upahSistem,
                        'upah_file' => 0,
                        'selisih' => 0,
                        'is_flagged' => $isFlagged,
                        'flag_reason' => $flagReason,
                    ];
                    $totalBarisSheet++;
                    if ($isFlagged) {
                        $totalFlaggedSheet++;
                    }
                }

                if ($totalBarisSheet === 0) {
                    $skippedSheets[] = $sheetName . ' (no data parsed)';
                    continue;
                }

                $persistSheet($parsedDataSheet, $totalBarisSheet, $totalFlaggedSheet, $file1, $sheetNameTrim, $tanggalFinal);
            }

            $msg = "Processed {$processedCount} sheet(s).";
            if (!empty($skippedSheets)) {
                $msg .= ' Skipped: ' . implode('; ', $skippedSheets);
            }
            return redirect()->route('borongan.index')->with('success', $msg);
        }
    }

    protected function cleanupBoronganImport(BoronganImport $import)
    {
        DB::transaction(function () use ($import) {
            BoronganHarian::where('borongan_import_id', $import->id)->delete();
            BoronganRekap::where('borongan_import_id', $import->id)->delete();
            $import->delete();
        });
    }

    public function review($id)
    {
        $import = BoronganImport::findOrFail($id);
        $siblingImports = BoronganImport::where('payroll_id', $import->payroll_id)
            ->where('jenis', $import->jenis)
            ->orderBy('tanggal_dari')
            ->get();
        $pendingMutasi = $this->detectMutasi($import->payroll_id);
        
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
                    'total_upah'  => $rows->sum('upah_sistem'),
                    'is_flagged'  => $rows->contains('is_flagged', true),
                    'flag_count'  => $rows->where('is_flagged', true)->count(),
                ];
            })
            ->sortBy('nama')
            ->values();

        $payrollId = $import->payroll_id;
        return view('borongan.review', compact('import', 'items', 'payrollId', 'pendingMutasi', 'siblingImports'));
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
                    'id'         => $j->id,
                    'kategori'   => $j->kategori,
                    'gram'       => $j->berat_gram,
                    'upah_file'  => $j->upah_file,
                    'upah_sistem'=> $j->upah_sistem,
                    'potongan'   => strtoupper($j->kategori) === 'ST'
                        ? intval($j->upah_file * 0.5)
                        : max(0, intval($j->upah_file - $j->upah_sistem)),
                    'selisih'    => $j->selisih,
                    'is_flagged' => $j->is_flagged,
                    'flag_reason'=> $j->flag_reason,
                ])->values(),
                'total_gram' => $jobs->sum('berat_gram'),
                'total_upah' => $jobs->sum('upah_sistem'),
            ];
        }

        return response()->json([
            'nip'    => $nip,
            'nama'   => $rows->first()?->nama ?? $nip,
            'detail' => $detail,
        ]);
    }

    public function updateUpahSistem(Request $request, $id)
    {
        $request->validate([
            'harian_id' => 'required|exists:borongan_harian,id',
            'upah_sistem' => 'required|integer|min:0',
        ]);

        $harian = BoronganHarian::findOrFail($request->harian_id);
        $upahSistem = intval($request->upah_sistem);
        $potongan = max(0, intval($harian->upah_file - $upahSistem));

        $harian->update([
            'upah_sistem' => $upahSistem,
            'selisih' => intval($harian->upah_file - $upahSistem),
        ]);

        $importId = $harian->borongan_import_id;
        $nip = $harian->nip;

        $totalGram = BoronganHarian::where('borongan_import_id', $importId)
            ->where('nip', $nip)
            ->sum('berat_gram');
        $totalUpah = BoronganHarian::where('borongan_import_id', $importId)
            ->where('nip', $nip)
            ->sum('upah_sistem');

        $rekap = BoronganRekap::where('borongan_import_id', $importId)
            ->where('nip', $nip)
            ->first();

        if ($rekap) {
            $rekap->total_gram = $totalGram;
            $rekap->total_upah = $totalUpah;
            $rekap->total_akhir = $totalUpah + $rekap->tambahan - $rekap->potongan_bpjs - $rekap->potongan_lain;
            $rekap->save();
        }

        return response()->json([
            'success' => true,
            'upah_sistem' => $upahSistem,
            'potongan' => $potongan,
            'total_upah_rekap' => $rekap->total_upah ?? null,
            'total_akhir_rekap' => $rekap->total_akhir ?? null,
        ]);
    }

    public function approve($id)
    {
        $import = BoronganImport::findOrFail($id);

        $payrollId = $import->payroll_id;
        $pendingCount = BoronganMutasiLog::where('payroll_id', $payrollId)->where('status', 'pending')->count();
        if ($pendingCount > 0) {
            return back()->with('error', 'Ada ' . $pendingCount . ' indikasi mutasi karyawan yang belum dikonfirmasi. Selesaikan dulu sebelum approve.');
        }

        // Group borongan_harian by NIP → akumulasi gram & upah
        $grouped = BoronganHarian::where('borongan_import_id', $id)
            ->get()
            ->groupBy('nip');

        foreach ($grouped as $nip => $rows) {
            $first = $rows->first();
            $totalGram = $rows->sum('berat_gram');
            $totalUpah = $rows->sum('upah_sistem');

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

        $siblingImportIds = BoronganImport::where('payroll_id', $import->payroll_id)
            ->where('jenis', $import->jenis)
            ->pluck('id');

        $rekaps = BoronganRekap::whereIn('borongan_import_id', $siblingImportIds)
            ->selectRaw('nip, nama, SUM(total_gram) as total_gram, SUM(total_upah) as total_upah, SUM(potongan_bpjs) as potongan_bpjs, SUM(potongan_lain) as potongan_lain, SUM(tambahan) as tambahan, SUM(komplain) as komplain, SUM(total_akhir) as total_akhir')
            ->groupBy('nip', 'nama')
            ->orderBy('nama')
            ->get();

        $payrollId = $import->payroll_id;
        return view('borongan.rekap', compact('import', 'rekaps', 'payrollId'));
    }

    public function getDetail(Request $request, $id, $nip)
    {
        $import = BoronganImport::findOrFail($id);

        $siblingImportIds = BoronganImport::where('payroll_id', $import->payroll_id)
            ->where('jenis', $import->jenis)
            ->pluck('id');

        $harianGrouped = BoronganHarian::whereIn('borongan_import_id', $siblingImportIds)
            ->where('nip', $nip)
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn($h) => \Carbon\Carbon::parse($h->tanggal)->format('Y-m-d'));

        $rekapRows = BoronganRekap::whereIn('borongan_import_id', $siblingImportIds)
            ->where('nip', $nip)
            ->get();

        $rekapTotal = [
            'total_gram' => $rekapRows->sum('total_gram'),
            'total_upah' => $rekapRows->sum('total_upah'),
            'potongan_bpjs' => $rekapRows->sum('potongan_bpjs'),
            'potongan_lain' => $rekapRows->sum('potongan_lain'),
            'tambahan' => $rekapRows->sum('tambahan'),
            'komplain' => $rekapRows->sum('komplain'),
            'total_akhir' => $rekapRows->sum('total_akhir'),
            'keterangan' => $rekapRows->first()?->keterangan ?? null,
            'nama' => $rekapRows->first()?->nama ?? null,
        ];

        $tanggalDari = BoronganImport::whereIn('id', $siblingImportIds)->min('tanggal_dari');
        $tanggalSampai = BoronganImport::whereIn('id', $siblingImportIds)->max('tanggal_sampai');

        if ($tanggalDari) {
            $tanggalDari = \Carbon\Carbon::parse($tanggalDari);
        }
        if ($tanggalSampai) {
            $tanggalSampai = \Carbon\Carbon::parse($tanggalSampai);
        }

        $user = User::where('nip', $nip)->first();
        $attendanceLogs = [];

        if ($user) {
            $logs = \App\Models\AttendanceLog::where('pin', $user->pin)
                ->whereBetween('tanggal', [$tanggalDari, $tanggalSampai])
                ->orderBy('datetime')
                ->get()
                ->groupBy(fn($l) => substr((string)$l->tanggal, 0, 10));

            $notes = \App\Models\AbsenceNote::where('pin', $user->pin)
                ->whereBetween('date', [$tanggalDari, $tanggalSampai])
                ->get()
                ->keyBy(fn($n) => $n->date->format('Y-m-d'));

            $periode = [];
            if ($tanggalDari && $tanggalSampai) {
                $cur = new \DateTime($tanggalDari->format('Y-m-d'));
                $end = new \DateTime($tanggalSampai->format('Y-m-d'));
                while ($cur <= $end) {
                    $tgl = $cur->format('Y-m-d');
                    $isSunday = date('N', strtotime($tgl)) == 7;

                    $dayLogs = $logs[$tgl] ?? collect();
                    $inTimes = $dayLogs->where('status', 'IN')->map(fn($l) => strtotime((string)$l->datetime));
                    $outTimes = $dayLogs->where('status', 'OUT')->map(fn($l) => strtotime((string)$l->datetime));

                    $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
                    $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

                    $note = $notes[$tgl] ?? null;
                    $boronganRows = $harianGrouped[$tgl] ?? collect();
                    $totalGram = $boronganRows->sum('berat_gram');
                    $totalUpah = $boronganRows->sum('upah_sistem');

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
            }

            $attendanceLogs = $periode;
        }

        return response()->json([
            'rekap' => $rekapTotal,
            'harian' => $attendanceLogs,
            'nama' => $rekapTotal['nama'] ?? $nip,
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

    public function detectMutasi($payrollId)
    {
        if (!$payrollId) return collect();

        $imports = BoronganImport::where('payroll_id', $payrollId)
            ->where('status', '!=', 'deleted')
            ->get();

        $byJenis = $imports->groupBy('jenis');

        $pairs = [
            ['cabut', 'cetak'],
            ['cabut', 'moulding'],
            ['cetak', 'moulding'],
        ];

        foreach ($pairs as [$jenisA, $jenisB]) {
            $listA = $byJenis[$jenisA] ?? collect();
            $listB = $byJenis[$jenisB] ?? collect();

            foreach ($listA as $impA) {
                foreach ($listB as $impB) {
                    if ($impA->id == $impB->id) continue;

                    $nipsA = BoronganRekap::where('borongan_import_id', $impA->id)->pluck('nip')
                        ->map(fn($v) => trim($v))->filter()->unique()->values()->toArray();
                    $nipsB = BoronganRekap::where('borongan_import_id', $impB->id)->pluck('nip')
                        ->map(fn($v) => trim($v))->filter()->unique()->values()->toArray();

                    $common = array_intersect($nipsA, $nipsB);

                    foreach ($common as $nip) {
                        if (empty($nip)) continue;

                        $exists = BoronganMutasiLog::where('payroll_id', $payrollId)
                            ->where('nip', $nip)
                            ->where(function ($q) use ($jenisA, $jenisB) {
                                $q->where(function ($q2) use ($jenisA, $jenisB) {
                                    $q2->where('jenis_a', $jenisA)->where('jenis_b', $jenisB);
                                })->orWhere(function ($q2) use ($jenisA, $jenisB) {
                                    $q2->where('jenis_a', $jenisB)->where('jenis_b', $jenisA);
                                });
                            })->exists();

                        if ($exists) continue;

                        BoronganMutasiLog::create([
                            'payroll_id' => $payrollId,
                            'nip' => $nip,
                            'jenis_a' => $jenisA,
                            'import_id_a' => $impA->id,
                            'jenis_b' => $jenisB,
                            'import_id_b' => $impB->id,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        return BoronganMutasiLog::where('payroll_id', $payrollId)->where('status', 'pending')->get();
    }

    public function resolveMutasi(Request $request, $logId)
    {
        $request->validate(['status' => 'required|in:confirmed,rejected']);

        $log = BoronganMutasiLog::findOrFail($logId);
        $log->update([
            'status' => $request->status,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
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
