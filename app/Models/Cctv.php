<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cctv extends Model
{
    protected $table = 'cctv';

    protected $fillable = [
        'source_id',
        'inventory_code',
        'nama_camera',
        'location',
        'ip_address',
        'type_camera',
        'rtsp_url',
        'latitude',
        'longitude',
        'source_name',
        'sub_division',
        'category',
        'jenis_kategori',
        'merk',
        'serial_number',
        'source_status',
        'reg_date',
        'nvr',
        'nomor_urut',
        'link_img',
        'source_model',
        'source_created_at',
        'source_updated_at',
    ];

    protected $casts = [
        'reg_date' => 'date:Y-m-d',
        'source_created_at' => 'datetime:Y-m-d\TH:i',
        'source_updated_at' => 'datetime:Y-m-d\TH:i',
    ];
}
