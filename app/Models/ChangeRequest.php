<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'cr_number',
        'department',
        'type',
        'sifat_perubahan',
        'risk_identification',
        'potential_cause',
        'severity',
        'occurrence',
        'detection',
        'rpn',
        'risk_control',
        'action',
        'attachment_path',
        'attachment_description',
        'status',
        'initiator_id',
        'rencana_tindakan',
        'pic_id',
        'timeline',
        'hasil_verifikasi',
        'awal_sebelum_perubahan',
        'usulan_perubahan',
        'alasan_perubahan',
        'analisis_dampak',
        'qa_verification_data',
    ];

    protected $casts = [
        'qa_verification_data' => 'array',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}
