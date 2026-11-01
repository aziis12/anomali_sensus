<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataAnomali extends Model
{
    protected $table = 'data_anomali';

    protected $fillable = [
        'assignment_id',
        'kategori',
        'kategori_anomali_fasih',
        'idsubsls',
        'kode_provinsi',
        'nama_provinsi',
        'kode_kabkota',
        'nama_kabkota',
        'kode_kecamatan',
        'nama_kecamatan',
        'kode_desa',
        'nama_desa',
        'kode_sls',
        'sub_sls',
        'nama_entitas',
        'link_fasih',
        'kelompok_baris_excel',
        'nama_anomali_fasih',
        'urutan_duplikat',
        'nomor_anomali',
        'daftar_nomor_anomali',
        'deskripsi_anomali',
        'status_dari_fasih',
        'tindak_lanjut_fasih',
        'email_petugas_fasih',
        'id_petugas_fasih',
        'pml',
        'ppl',
        'nama_sls',
        'perlu_konfirm_petugas',
        'tanggapan_petugas',
        'status_assignment',
        'status_assignment_fasih',
        'penyelesaian_anomali',
        'tanggal_respon',
        'status_di_fasih',
        'data_spesifik',
        'riwayat_unggah_id',
        'diupdate_oleh',
    ];

    protected function casts(): array
    {
        return [
            'data_spesifik' => 'array',
            'tanggal_respon' => 'date',
            'daftar_nomor_anomali' => 'array',
        ];
    }

    // turunkan kategori "bersih" (mis. "Jumlah anomali 3") secara otomatis setiap kali
    // nama_anomali_fasih diisi/diubah, dari mana pun sumbernya (impor lama, impor baru,
    // atau edit manual). Ini menghindari kolom kategori_anomali_fasih jadi kosong/basi
    // gara-gara ada tempat lain yang lupa ngisi kolom ini secara manual.
    protected static function booted(): void
    {
        static::saving(function (DataAnomali $model) {
            if ($model->isDirty('nama_anomali_fasih') && $model->nama_anomali_fasih) {
                if (preg_match('/^(Jumlah anomali(?:\s*data)?\s*\d+)/i', trim($model->nama_anomali_fasih), $cocok)) {
                    $model->kategori_anomali_fasih = trim($cocok[1]);
                }
            }
        });
    }

    public function riwayatUnggah()
    {
        return $this->belongsTo(RiwayatUnggah::class);
    }

    // pegawai yang terakhir memproses/menyimpan baris ini
    public function pembaru()
    {
        return $this->belongsTo(User::class, 'diupdate_oleh');
    }

    // dipakai buat filter dashboard bawaan pegawai, default nampilin yang belum ditindaklanjuti
    public function scopeBelumDitindaklanjuti($query)
    {
        return $query->where('status_assignment', 'belum');
    }

    // dipakai di dashboard petugas, cocokkan berdasarkan email yang dia daftarkan
    // dipakai di dashboard petugas, difilter berdasarkan nama PML yang dipilih di sesi login saat ini
    public function scopeUntukPml($query, string $namaPml)
    {
        return $query->where('pml', $namaPml);
    }
}