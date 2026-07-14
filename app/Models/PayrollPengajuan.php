<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPengajuan extends Model
{
    protected $table = 'payroll_pengajuan';

    protected $fillable = [
        'payroll_id',
        'nip',
        'nama',
        'jenis',
        'gaji_real',
        'komplain',
        'insentif',
        'potongan_lain',
        'potongan_bpjs',
        'total_lembur',
        'total_akhir',
        'no_rekening',
        'nama_bank',
        'email',
        'diajukan_at',
        'diajukan_by',
    ];

    protected $casts = [
        'diajukan_at' => 'datetime',
        'total_lembur' => 'integer',
    ];
}
