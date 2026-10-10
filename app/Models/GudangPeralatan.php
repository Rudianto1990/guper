<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GudangPeralatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_barang',
        'nama_barang',
        'foto',
        'qty',
        'tahun',
        'satuan',
        'lokasi',
        'longitude',
        'latitude',
        'gudang_area',
        'kondisi',
        'mekanisme_penghapusan',
        'alasan_hapus',
        'tata_cara',
        'url_cctv',
    ];
}