<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('superadmin123'),
            'peran' => 'superadmin',
            'status' => 'aktif',
        ]);
    }
}