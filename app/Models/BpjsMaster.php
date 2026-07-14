<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpjsMaster extends Model
{
    protected $table = 'bpjs_master';

    protected $fillable = [
        'nip',
        'nominal',
        'keterangan',
    ];

    protected $casts = [
        'nominal' => 'integer',
    ];
}
