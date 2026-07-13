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
        'is_other_batch_affected',
        'other_batch_affected_details',
        'deviation_frequency',
        'is_production_stopped',
        'immediate_action_details',
        'kepala_departemen',
        'attachment_path',
        'attachment_description',
        'attachments',
        'risk_analysis',
        'status',
        'initiator_id',
        'reject_reason',
        'fishbone_machine',
        'fishbone_man',
        'fishbone_method',
        'fishbone_milieu',
        'fishbone_measurement',
        'fishbone_materials',
        'root_cause',
        'risk_identification_details',
        'risk_analysis_details',
    ];

    protected $casts = [
        'jenis_penyimpangan'         => 'array',
        'identifikasi_penyimpangan'   => 'array',
        'is_other_batch_affected'    => 'boolean',
        'is_production_stopped'      => 'boolean',
        'attachments'                => 'array',
        'risk_analysis'              => 'array',
        'tanggal_temuan'             => 'date',
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
