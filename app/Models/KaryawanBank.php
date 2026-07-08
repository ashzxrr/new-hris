<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryawanBank extends Model
{
    protected $table = 'karyawan_bank';

    protected $fillable = [
        'nip',
        'nama_bank',
        'no_rekening',
        'email',
    ];
}
