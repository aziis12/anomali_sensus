<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class KelolaPegawaiController extends Controller
{
    public function index(): View
    {
        $pegawai = User::where('peran', 'pegawai')->latest()->get();

        return view('kelola-pegawai.index', ['pegawai' => $pegawai]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'peran' => 'pegawai',
            'status' => 'aktif',
        ]);

        return back()->with('status', 'Akun pegawai berhasil dibuat.');
    }

    public function nonaktifkan(User $user): RedirectResponse
    {
        $user->update(['status' => 'nonaktif']);

        return back()->with('status', 'Akun pegawai dinonaktifkan.');
    }

    public function aktifkan(User $user): RedirectResponse
    {
        $user->update(['status' => 'aktif']);

        return back()->with('status', 'Akun pegawai diaktifkan kembali.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('status', 'Kamu gak bisa menghapus akunmu sendiri.');
        }

        $user->delete();

        return back()->with('status', 'Akun pegawai berhasil dihapus.');
    }
}