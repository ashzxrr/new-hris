<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoronganRekap extends Model
{
    protected $table = 'borongan_rekap';

    protected $fillable = [
        'borongan_import_id', 'pin', 'nip', 'nama',
        'periode_dari', 'periode_sampai',
        'total_gram', 'total_upah',
        'potongan_bpjs', 'potongan_lain', 'tambahan',
        'total_akhir', 'keterangan', 'status', 'updated_by'
    ];

    protected $casts = [
        'periode_dari'   => 'date',
        'periode_sampai' => 'date',
    ];

    public function import()
    {
        return $this->belongsTo(BoronganImport::class, 'borongan_import_id');
    }

    public function harian()
    {
        return $this->hasMany(BoronganHarian::class, 'nip', 'nip');
    }
}
