<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'periode', 'tanggal_dari', 'tanggal_sampai', 'status', 'created_by'
    ];

    protected $casts = [
        'tanggal_dari'   => 'date',
        'tanggal_sampai' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }
}
