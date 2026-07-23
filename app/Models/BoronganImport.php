<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BoronganImport extends Model
{
    protected $table = 'borongan_imports';
    protected $fillable = [
        'payroll_id', 'jenis', 'filename', 'tanggal_dari', 'tanggal_sampai',
        'total_baris', 'total_flagged', 'tambahan_gram', 'tambahan_gram_notes',
        'status', 'uploaded_by'
    ];
    protected $casts = [
        'tanggal_dari'   => 'date',
        'tanggal_sampai' => 'date',
    ];
    
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
    
    public function items()
    {
        return $this->hasMany(BoronganHarian::class);
    }
}
