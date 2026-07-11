# ANALISIS ROOT CAUSE SELISIH PAYROLL HARIAN
**Total Selisih: Rp580.000**  
**Period: 2026-07-10**

---

## RINGKASAN TEMUAN

Berdasarkan review logika code di `PayrollController.php`, saya telah mengidentifikasi **3 bug utama** yang menyebabkan selisih:

### ✅ POIN A: 3 NIP tidak ada di absensi (Rp200.000)
- **NIP: LMG-2025-837** (Duwi Santika) - Rp200.000
- **NIP: LMG-2025-442** (Mukhamad Aditya) - (kosong)
- **NIP: LMG-2024-214** (Wulan Oktavia) - (kosong)

### ✅ POIN B: 9 NIP dengan hari "Hadir" terhitung +1 hari (Rp380.000)
- **Total koreksi selisih nominal**: Rp30k + Rp35k + Rp35k + Rp35k + Rp65k + Rp35k + Rp35k + Rp70k + Rp40k = **Rp380.000**

---

## MASALAH #1: Karyawan Non-Hadir Masuk ke Payroll (POIN A)

**File:** `app/Http/Controllers/PayrollController.php` - Method `tarikAbsensi()` (line 538)

### Root Cause:
```php
$karyawan = User::where('is_active', 1)
    ->where('kategori_gaji', 'harian')
    ->whereNotNull('salary_config_id')
    ->orderBy('nama')
    ->get();
```

**Masalah:** Query mengambil SEMUA karyawan aktif dengan kategori gaji 'harian' **TANPA CHECK** apakah mereka punya attendance logs atau absence notes di periode tersebut.

### Skenario Terjadi:
1. NIP di-set `is_active = 1` dan `kategori_gaji = 'harian'`
2. NIP tidak punya satupun fingerprint log atau absence note di periode payroll
3. Loop periode di line 596 tidak menemukan status apapun
4. Maka: `hadir=0, alpha=0, izin=0, sakit=0, total_gaji=0`
5. Tapi **tetap di-create di payroll_details** via `updateOrCreate()` (line 629)

### Solusi:
Tambah validation **SEBELUM** insert ke PayrollDetail - hanya insert jika:
- Ada minimal 1 attendance log, ATAU
- Ada minimal 1 absence note, ATAU
- Ada minimal 1 attendance correction

---

## MASALAH #2: Hari Libur/Off Days Tidak Di-Filter (POIN B)

**File:** `app/Http/Controllers/PayrollController.php` - Method `tarikAbsensi()` (line 596)

### Root Cause:
```php
foreach ($periode as $tgl) {
    if (date('N', strtotime($tgl)) == 7) continue;  // HANYA skip Minggu
    
    // ...cek attendance...
    
    switch ($status) {
        case 'H': $hadir++; break;
        case 'I': $izin++; break;
        // ...
    }
}
```

**Masalah:**
1. Hanya skip **Hari Minggu** (day 7)
2. **TIDAK skip Hari Libur Nasional/Holiday** - jika hari libur tidak ada absence note L/GL, orang dianggap ALPHA
3. **TIDAK skip hari "Izin" (code I)** saat menghitung hari kerja - jika ada absence note I, hadir tidak bertambah (benar), TAPI jika tidak ada note dan tidak ada logs, maka status='A' (Alpha)

### Skenario Terjadi untuk 9 NIP (Poin B):
Untuk 9 orang ini, ada **1 hari dalam periode yang seharusnya BUKAN hari kerja** (misalnya libur nasional atau day-off yang tidak dicatat), tapi:
- Sistem: Dihitung sebagai hari kerja → hadir+1 → nominal_harian * (hadir) = X
- Realita: Bukan hari kerja → hadir actual = hadir-1 → gaji yang benar = nominal_harian * (hadir-1) = X'

**Contoh Kalkulasi LMG-2025-745 (Eka Nayla):**
- Sistem nominal salah: 480.000 (seharusnya 450.000)
- Selisih nominal: 30.000
- Ini kemungkinan karena:
  - Sistem hitung hadir = 10 hari dengan nominal 480k → 4.800.000
  - Realita hadir = 10 hari dengan nominal 450k → 4.500.000
  - ATAU hadir benar = 9 hari dengan nominal 480k → 4.320.000
  
Selisih 30.000 / hari = nominal actual yang benar

### Kemungkinan Penyebab Konkret:
1. **Hari Libur Nasional** (misal: Cuti Bersama, Lebaran) **tidak di-input ke `absence_notes`** dengan code 'GL' atau 'L'
   - Sistem: Lihat tidak ada logs dan tidak ada note → A (alpha) atau H (jika ada 1 log saja)
   - Realita: Harusnya tidak dihitung sebagai hari kerja

2. **Atau ada 1 hari yang ada di SalaryConfig tapi tidak ada di periode kerja** (misal orang resign atau masuk di tengah bulan, tapi sistem masih hitung)

3. **Atau ada duplikat tanggal atau timezone issue** yang membuat 1 hari tercatat 2x

---

## MASALAH #3: Nominal_Harian Mungkin Salah di Source (SalaryConfig)

Untuk POIN B, user observe bahwa **nominal_harian di sistem LEBIH TINGGI dari seharusnya**:
- LMG-2025-745: 480k → 450k (turun 30k)
- LMG-2025-497: 560k → 525k (turun 35k)
- dst

**Ini kemungkinan:**
1. SalaryConfig nominal di-hitung dari sesuatu yang salah saat di-set
2. Ada proses auto-calculation yang bug saat menentukan nominal per orang
3. Manual entry error di Setup SalaryConfig

---

## REKOMENDASI PERBAIKAN

### Fix #1: Validasi Kehadiran Sebelum Insert PayrollDetail
```php
// Di method tarikAbsensi(), sebelum updateOrCreate():
$totalAttendanceRecord = $hadir + $alpha + $izin + $sakit + $setengahHari;
if ($totalAttendanceRecord == 0) {
    // Skip karyawan ini - tidak ada attendance data sama sekali
    continue;
}

PayrollDetail::updateOrCreate(...);
```

### Fix #2: Handle Holiday/Off-days Dengan Benar
```php
// Buat list hari libur nasional untuk periode
$holidays = Holiday::whereBetween('date', [$dari, $sampai])->pluck('date')->toArray();

foreach ($periode as $tgl) {
    $isSunday = date('N', strtotime($tgl)) == 7;
    $isHoliday = in_array($tgl, $holidays);
    
    if ($isSunday || $isHoliday) continue;  // Skip hari minggu DAN libur nasional
    
    // ... process attendance ...
}
```

### Fix #3: Audit SalaryConfig Untuk 9 NIP
Check tabel `salary_configs`:
```sql
SELECT nip, nominal, berlaku_dari 
FROM salary_configs 
WHERE nip IN (
    'LMG-2025-745', 'LMG-2025-497', 'LMG-2025-457',
    'LMG-2025-524', 'LMG-2025-699', 'LMG-2025-494',
    'LMG-2025-517', 'LMG-2025-525', 'LMG-2025-212'
)
ORDER BY nip, berlaku_dari DESC;
```

Verifikasi nominal yang benar untuk setiap orang, perbaiki di SalaryConfig.

### Fix #4: Tambah Logging/Audit Trail
Setiap kali `tarikAbsensi()` dijalankan, simpan:
- Total karyawan diproses
- Karyawan yang di-skip (attendance record = 0)
- Perubahan yang dibuat

---

## CHECKLIST DEBUGGING

- [ ] Cek apakah ada `holidays` table atau logic untuk hari libur nasional
- [ ] Audit 3 NIP (Poin A) - apakah benar tidak ada attendance logs sama sekali
- [ ] Audit 9 NIP (Poin B) - cek detail harian hadir vs absence notes untuk lihat hari mana yang counted +1
- [ ] Verifikasi nominal di `salary_configs` untuk 9 NIP
- [ ] Cek apakah ada bug di proses sebelumnya yang set `is_active=1` untuk orang yang resign
- [ ] Review `AbsenceNote` untuk periode - apakah hari libur nasional tercatat dengan code GL atau tidak
- [ ] Test ulang `tarikAbsensi()` setelah perbaikan

---

## LAMPIRAN: KODE YANG PERLU DIPERIKSA

### Method: `tarikAbsensi()` - Line 538-651
**File:** `app/Http/Controllers/PayrollController.php`

**Bagian Kritis:**
1. Line 545-550: Query karyawan (tidak validasi attendance)
2. Line 596: Loop periode (tidak handle holidays)
3. Line 612-625: Status determination logic
4. Line 629-641: updateOrCreate (insert tanpa attendance validation)
