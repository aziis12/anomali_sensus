<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferensiPplPml extends Model
{
    protected $table = 'referensi_ppl_pml';

    protected $fillable = [
        'idsubsls',
        'pml',
        'ppl',
        'nama_sls',
    ];
}