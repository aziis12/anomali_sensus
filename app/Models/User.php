<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'peran',
        'kategori_petugas',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function riwayatUnggah()
    {
        return $this->hasMany(RiwayatUnggah::class, 'diunggah_oleh');
    }

    public function isSuperadmin(): bool
    {
        return $this->peran === 'superadmin';
    }

    public function isPegawai(): bool
    {
        return $this->peran === 'pegawai';
    }

    public function isPetugas(): bool
    {
        return $this->peran === 'petugas';
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }
}