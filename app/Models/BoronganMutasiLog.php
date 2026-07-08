<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoronganMutasiLog extends Model
{
    protected $table = 'borongan_mutasi_log';
    protected $fillable = ['payroll_id', 'nip', 'jenis_a', 'import_id_a', 'jenis_b', 'import_id_b', 'status', 'resolved_by', 'resolved_at'];
    protected $casts = ['resolved_at' => 'datetime'];
}
