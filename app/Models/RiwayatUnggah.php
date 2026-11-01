<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatUnggah extends Model
{
    protected $table = 'riwayat_unggah';

    protected $fillable = [
        'diunggah_oleh',
        'tanggal_data',
        'nama_file_usaha',
        'nama_file_keluarga',
        // ditambah untuk format BARU: 1 file gabungan (usaha & keluarga jadi satu).
        // nama_file_usaha & nama_file_keluarga tetap dipertahankan (dibiarkan null)
        // supaya riwayat unggahan lama (format 2 file) tidak perlu diubah/dimigrasikan.
        'nama_file_gabungan',
        'jumlah_baru',
        'jumlah_diperbarui',
        'jumlah_selesai_di_fasih',
        'total_data_saat_ini',
        'breakdown_tindak_lanjut',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_data' => 'date',
            'breakdown_tindak_lanjut' => 'array',
        ];
    }

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function dataAnomali()
    {
        return $this->hasMany(DataAnomali::class);
    }
}