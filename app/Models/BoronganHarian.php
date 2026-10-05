<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BoronganHarian extends Model
{
    protected $table = 'borongan_harian';
    protected $fillable = [
        'borongan_import_id', 'pin', 'nip', 'nama', 'tanggal',
        'kategori', 'berat_gram', 'gram_note', 'upah_sistem', 'upah_file',
        'selisih', 'is_flagged', 'flag_reason', 'status', 'tambahan_training'
    ];
    protected $casts = [
        'tanggal'    => 'date',
        'berat_gram' => 'float',
        'is_flagged' => 'boolean',
    ];
    public function import()
    {
        return $this->belongsTo(BoronganImport::class, 'borongan_import_id');
    }
}
