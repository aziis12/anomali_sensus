<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DataAnomali;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegistrasiPetugasController extends Controller
{
    public function create(): View
    {
        return view('auth.register-petugas');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $namaDicari = trim($data['name']);

        $namaDitemukan = DataAnomali::whereRaw('LOWER(pml) LIKE ?', ['%'.strtolower($namaDicari).'%'])->exists();

        if (! $namaDitemukan) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Nama ini belum ditemukan sebagai PML di data yang sudah diunggah. Pastikan nama ditulis sama seperti di data fasih, atau hubungi pegawai BPS kalau kamu merasa ini keliru.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'peran' => 'petugas',
            'kategori_petugas' => 'pml',
            'status' => 'aktif',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}