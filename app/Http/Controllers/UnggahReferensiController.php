<?php

namespace App\Http\Controllers;

use App\Models\ReferensiPplPml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UnggahReferensiController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file_referensi' => 'required|file|mimes:xlsx',
        ]);

        $path = $request->file('file_referensi')->store('unggahan_sementara');

        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $barisHeader = $sheet[0];
        $header = [];
        foreach ($barisHeader as $indeks => $namaKolom) {
            if ($namaKolom) {
                $header[strtolower(trim($namaKolom))] = $indeks;
            }
        }

        $kolomIdsub = $header['idsub'] ?? null;
        $kolomPml = $header['pml'] ?? null;
        $kolomPpl = $header['ppl'] ?? null;
        $kolomNamaSls = $header['nama sls'] ?? null;

        if ($kolomIdsub === null || $kolomPml === null || $kolomPpl === null || $kolomNamaSls === null) {
            Storage::delete($path);

            return back()->withErrors(['file_referensi' => 'Kolom idsub, pml, ppl, atau nama sls tidak ditemukan di file. Pastikan header ada di baris pertama.']);
        }

        $jumlah = 0;
        for ($i = 1; $i < count($sheet); $i++) {
            $baris = $sheet[$i];
            $idsubsls = trim((string) ($baris[$kolomIdsub] ?? ''));

            if (! $idsubsls) {
                continue;
            }

            ReferensiPplPml::updateOrCreate(
                ['idsubsls' => $idsubsls],
                [
                    'pml' => $baris[$kolomPml] ?? null,
                    'ppl' => $baris[$kolomPpl] ?? null,
                    'nama_sls' => $baris[$kolomNamaSls] ?? null,
                ]
            );

            $jumlah++;
        }

        Storage::delete($path);

        return back()->with('status', "Referensi berhasil diunggah, {$jumlah} baris diproses.");
    }
}