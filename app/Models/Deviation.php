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
        'description',
        'attachment_path',
        'attachment_description',
        'status',
        'initiator_id',
        'reject_reason',
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
