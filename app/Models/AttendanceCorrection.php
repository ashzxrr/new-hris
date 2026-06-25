<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    protected $table = 'attendance_corrections';

    protected $fillable = [
        'pin','tanggal','jam_in','jam_out','status','keterangan','edited_by','lembur_menit','lembur_approved'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lembur_approved' => 'boolean',
    ];
}
