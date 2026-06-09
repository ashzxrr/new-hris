<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceNote extends Model
{
    protected $table = 'absence_notes';
    protected $fillable = ['pin', 'date', 'code', 'note', 'created_by'];
    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}
