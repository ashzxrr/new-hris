<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BoronganImport extends Model
{
    protected $table = 'borongan_imports';
    protected $fillable = [
        'jenis', 'filename', 'tanggal_dari', 'tanggal_sampai',
        'total_baris', 'total_flagged', 'status', 'uploaded_by'
    ];
    protected $casts = [
        'tanggal_dari'   => 'date',
        'tanggal_sampai' => 'date',
    ];
    public function items()
    {
        return $this->hasMany(BoronganHarian::class);
    }
}
