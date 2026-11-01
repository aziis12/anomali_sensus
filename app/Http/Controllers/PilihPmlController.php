<?php

namespace App\Http\Controllers;

use App\Models\DataAnomali;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PilihPmlController extends Controller
{
    public function create(): View
    {
        $daftarPml = DataAnomali::whereNotNull('pml')
            ->where('pml', '!=', '')
            ->distinct()
            ->orderBy('pml')
            ->pluck('pml');

        return view('auth.pilih-pml', [
            'daftarPml' => $daftarPml,
            'pmlAktif' => session('nama_pml_dipilih'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_pml' => 'required|string',
        ]);

        session(['nama_pml_dipilih' => $data['nama_pml']]);

        return redirect()->route('data-anomali.index')->with('status', 'Kamu sekarang mengerjakan data atas nama '.$data['nama_pml'].'.');
    }
}