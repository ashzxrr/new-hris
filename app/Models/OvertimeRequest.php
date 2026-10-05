<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    protected $table = 'overtime_requests';

    protected $fillable = [
        'pin', 'nip', 'nama', 'tanggal', 'jam_out', 'lembur_menit', 'status', 'keterangan', 'approved_by', 'approved_at', 'created_by'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'approved_at' => 'datetime',
    ];
}
