<?php

namespace App\Http\Controllers;

use App\Models\DataAnomali;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

class KelolaPetugasController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('peran', 'petugas');

        if ($request->filled('cari')) {
            $query->where('name', 'like', '%'.$request->cari.'%');
        }

        $petugas = $query->latest()->paginate(20)->withQueryString();

        // dihitung terpisah biar gak bikin query utama jadi rumit
        $petugas->getCollection()->transform(function ($satu) {
            $kolom = $satu->kategori_petugas;
            $satu->jumlah_data = $kolom
                ? DataAnomali::whereRaw("LOWER($kolom) LIKE ?", ['%'.strtolower($satu->name).'%'])->count()
                : 0;

            return $satu;
        });

        return view('kelola-petugas.index', [
            'petugas' => $petugas,
            'kataCari' => $request->get('cari', ''),
        ]);
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
            'peran' => 'petugas',
            'kategori_petugas' => 'pml',
            'status' => 'aktif',
        ]);

        return back()->with('status', 'Akun petugas berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('kelola-petugas.edit', ['petugas' => $user]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'kategori_petugas' => 'required|in:pml,ppl',
        ]);

        $user->update($data);

        return redirect()->route('kelola-petugas.index')->with('status', 'Data petugas berhasil diperbarui.');
    }

    public function nonaktifkan(User $user): RedirectResponse
    {
        $user->update(['status' => 'nonaktif']);

        return back()->with('status', 'Akun petugas dinonaktifkan.');
    }

    public function aktifkan(User $user): RedirectResponse
    {
        $user->update(['status' => 'aktif']);

        return back()->with('status', 'Akun petugas diaktifkan kembali.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('status', 'Akun petugas dihapus.');
    }
}