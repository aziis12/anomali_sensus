<?php

namespace App\Http\Controllers;

use App\Services\PemrosesExcelAnomali;
use App\Services\PemrosesExcelAnomaliGabungan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnggahExcelController extends Controller
{
    public function __construct(
        private PemrosesExcelAnomali $pemroses,
        private PemrosesExcelAnomaliGabungan $pemrosesGabungan,
    ) {
    }

    // format LAMA: 2 file terpisah (usaha & keluarga). Tetap dipertahankan
    // untuk jaga-jaga kalau pusat balik lagi ke format lama.
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // baca Excel + simpan data bisa lama & berat memori untuk file besar
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'tanggal_data' => 'required|date',
            'file_usaha' => 'required|file|mimes:xlsx',
            'file_keluarga' => 'required|file|mimes:xlsx',
            // file referensi PML/PPL/Nama SLS OPSIONAL: cuma perlu diunggah kalau
            // memang ada data PML/PPL/SLS yang baru atau berubah.
            'file_referensi' => 'nullable|file|mimes:xlsx',
        ]);

        $pathUsaha = $request->file('file_usaha')->store('unggahan_sementara');
        $pathKeluarga = $request->file('file_keluarga')->store('unggahan_sementara');
        $pathReferensi = $request->hasFile('file_referensi')
            ? $request->file('file_referensi')->store('unggahan_sementara')
            : null;

        try {
            $riwayat = $this->pemroses->proses(
                Storage::path($pathUsaha),
                Storage::path($pathKeluarga),
                $pathReferensi ? Storage::path($pathReferensi) : null,
                $request->user()->id,
                $request->input('tanggal_data')
            );
        } catch (\Exception $e) {
            Storage::delete(array_filter([$pathUsaha, $pathKeluarga, $pathReferensi]));

            return $this->gagal($request, $e->getMessage());
        }

        Storage::delete(array_filter([$pathUsaha, $pathKeluarga, $pathReferensi]));

        return $this->berhasil($request, $riwayat);
    }

    // format BARU: 1 file gabungan (usaha & keluarga sudah jadi satu, per link fasih
    // digabung jadi satu baris dengan "Daftar Anomali" berisi semua anomalinya).
    public function storeGabungan(Request $request): RedirectResponse|JsonResponse
    {
        // baca Excel + simpan data bisa lama & berat memori untuk file besar
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'tanggal_data' => 'required|date',
            'file_gabungan' => 'required|file|mimes:xlsx',
            // sama seperti format lama: opsional.
            'file_referensi' => 'nullable|file|mimes:xlsx',
        ]);

        $pathGabungan = $request->file('file_gabungan')->store('unggahan_sementara');
        $pathReferensi = $request->hasFile('file_referensi')
            ? $request->file('file_referensi')->store('unggahan_sementara')
            : null;

        try {
            $riwayat = $this->pemrosesGabungan->proses(
                Storage::path($pathGabungan),
                $pathReferensi ? Storage::path($pathReferensi) : null,
                $request->user()->id,
                $request->input('tanggal_data')
            );
        } catch (\Exception $e) {
            Storage::delete(array_filter([$pathGabungan, $pathReferensi]));

            return $this->gagal($request, $e->getMessage());
        }

        Storage::delete(array_filter([$pathGabungan, $pathReferensi]));

        return $this->berhasil($request, $riwayat);
    }

    /**
     * Respons sukses. Kalau dipanggil dari progress bar (XHR, header Accept: application/json)
     * balikannya JSON; kalau dari submit form biasa (tanpa JavaScript) tetap redirect + flash.
     */
    private function berhasil(Request $request, $riwayat): RedirectResponse|JsonResponse
    {
        $pesan = "Berhasil diproses. Baru: {$riwayat->jumlah_baru}, diperbarui: {$riwayat->jumlah_diperbarui}, selesai di fasih: {$riwayat->jumlah_selesai_di_fasih}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan]);
        }

        return back()->with('status', $pesan);
    }

    /**
     * Respons gagal (pesan dari exception pemroses). Format error-nya sama dengan
     * validasi Laravel (422 + "errors"), jadi progress bar menampilkannya dengan cara yang sama.
     */
    private function gagal(Request $request, string $pesan): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'errors' => ['file_referensi' => [$pesan]],
            ], 422);
        }

        return back()->withErrors(['file_referensi' => $pesan]);
    }
}