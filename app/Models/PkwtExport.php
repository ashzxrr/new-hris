<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkwtExport extends Model
{
    protected $table = 'pkwt_exports';

    protected $fillable = [
        'user_id',
        'nomor_urut',
        'nomor_surat',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_dibuat',
        'tempat_dibuat',
        'dibuat_oleh',
        'file_path',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_dibuat'  => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(AuthUser::class, 'dibuat_oleh');
    }
}
