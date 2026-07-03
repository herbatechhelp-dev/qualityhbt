<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deviation extends Model
{
    use HasFactory;

    protected $fillable = [
        'deviation_number',
        'department',
        'pic',
        'tanggal_temuan',
        'description',
        'jenis_penyimpangan',
        'identifikasi_penyimpangan',
        'kepala_departemen',
        'attachment_path',
        'attachment_description',
        'attachments',
        'risk_analysis',
        'status',
        'initiator_id',
        'reject_reason',
    ];

    protected $casts = [
        'jenis_penyimpangan'       => 'array',
        'identifikasi_penyimpangan' => 'array',
        'attachments'              => 'array',
        'risk_analysis'            => 'array',
        'tanggal_temuan'           => 'date',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function capa()
    {
        return $this->hasOne(Capa::class);
    }
}
