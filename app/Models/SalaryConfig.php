<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryConfig extends Model
{
    protected $fillable = [
        'pin', 'nip', 'nama', 'kategori_gaji', 'nominal', 'berlaku_dari', 'keterangan', 'created_by'
    ];

    protected $casts = [
        'berlaku_dari' => 'date',
    ];
}
