<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    protected $fillable = [
        'payroll_id','pin','nip','nama','nominal_harian',
        'hadir','alpha','izin','sakit','setengah_hari','lembur_menit',
        'gaji_pokok','gaji_lembur','tambahan','potongan',
        'total_gaji','keterangan'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
