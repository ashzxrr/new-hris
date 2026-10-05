<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BoronganImport;
use App\Models\BoronganRekap;
use App\Models\PayrollGrandTotal;
use App\Models\PayrollPengajuan;

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

    public function boronganImports()
    {
        return $this->hasMany(BoronganImport::class);
    }

    public function boronganRekaps()
    {
        return $this->hasManyThrough(
            BoronganRekap::class,
            BoronganImport::class,
            'payroll_id',
            'borongan_import_id'
        );
    }

    public function grandTotals()
    {
        return $this->hasMany(PayrollGrandTotal::class);
    }

    public function pengajuans()
    {
        return $this->hasMany(PayrollPengajuan::class);
    }
}
