<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'excel_no',
        'title',
        'document_number',
        'revision',
        'no_perubahan_cc',
        'effective_date',
        'tgl_review',
        'tgl_review_2',
        'pengganti_lampiran',
        'no_catatan_mutu',
        'dokumen_terkait',
        'tgl_sosialisasi',
        'distribusi',
        'no_pemusnahan',
        'tgl_pemusnahan',
        'tempat_penyimpanan',
        'type',
        'status',
    ];
}
