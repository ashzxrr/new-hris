<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $table = 'attendance_logs';
    public $timestamps = false;
    protected $fillable = ['pin', 'datetime', 'tanggal', 'status', 'verified', 'machine_name', 'photo_url'];
    protected $casts = [
        'datetime' => 'datetime',
    ];
}
