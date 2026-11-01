<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenugasanPj extends Model
{
    protected $table = 'penugasan_pj';

    protected $fillable = [
        'email_pml',
        'pml',
        'email_pj',
        'pj',
        'kecamatan',
    ];
}