<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capa extends Model
{
    use HasFactory;

    protected $appends = ['sub_capa_number'];

    protected $fillable = [
        'capa_number',
        'deviation_id',
        'deviation_number_ref',
        'tanggal_penyimpangan',
        'type_capa',
        'tindakan_capa',
        'pic_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'bukti_lapangan_path',
        'hasil_verifikasi_qa',
        'status',
        'initiator_id',
    ];

    public function getSubCapaNumberAttribute()
    {
        return 'CAPA-S1-' . str_replace('/', '', $this->capa_number);
    }

    public function deviation()
    {
        return $this->belongsTo(Deviation::class, 'deviation_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}
