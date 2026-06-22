<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BoronganRate extends Model
{
    protected $table = 'borongan_rates';
    protected $fillable = ['kode_kategori', 'nama_kategori', 'jenis', 'rate_per_gram', 'berlaku_dari'];
    protected $casts = ['berlaku_dari' => 'date'];
}
