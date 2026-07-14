<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollGrandTotal extends Model
{
    protected $table = 'payroll_grand_total';

    protected $fillable = [
        'payroll_id',
        'nip',
        'nama',
        'job_label',
        'section',
        'detail_harian',
        'insentif',
        'komplain',
        'potongan_lain',
        'potongan_bpjs',
        'total_lembur',
        'total_akhir',
        'generated_at',
    ];

    protected $casts = [
        'detail_harian' => 'array',
        'generated_at' => 'datetime',
        'insentif' => 'integer',
        'komplain' => 'integer',
        'potongan_lain' => 'integer',
        'potongan_bpjs' => 'integer',
        'total_lembur' => 'integer',
        'total_akhir' => 'integer',
        'section' => 'string',
    ];
}
