<?php

namespace App\Http\Controllers;

use App\Models\RiwayatUnggah;
use App\Services\PemrosesExcelAnomaliBertahap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UnggahBertahapController extends Controller
{
    public function __construct(private PemrosesExcelAnomaliBertahap $bertahap)
    {
    }

    public function mulai(Request $request): JsonResponse
    {
        set_time_limit(0);

        $request->validate([
            'tanggal_data' => 'required|date',
            'file_gabungan' => 'required|file|mimes:xlsx',
            'file_referensi' => 'nullable|file|mimes:xlsx',
        ]);

        $pathGabungan = $request->file('file_gabungan')->store('unggahan_sementara');
        $pathReferensi = $request->hasFile('file_referensi')
            ? $request->file('file_referensi')->store('unggahan_sementara')
            : null;

        try {
            $info = $this->bertahap->mulai(
                Storage::path($pathGabungan),
                $pathReferensi ? Storage::path($pathReferensi) : null,
                $request->user()->id,
                $request->input('tanggal_data')
            );
        } catch (\Throwable $e) {
            Storage::delete(array_filter([$pathGabungan, $pathReferensi]));

            return $this->gagal($e);
        }

        return response()->json($info);
    }

    public function proses(Request $request): JsonResponse
    {
        $riwayat = $this->riwayatMilikUser($request);
        $request->validate(['awal' => 'required|integer|min:1']);

        try {
            return response()->json($this->bertahap->prosesBatch($riwayat->id, (int) $request->input('awal')));
        } catch (\Throwable $e) {
            return $this->gagal($e);
        }
    }

    public function selesai(Request $request): JsonResponse
    {
        $riwayat = $this->riwayatMilikUser($request);

        try {
            $r = $this->bertahap->selesaikan($riwayat->id);
        } catch (\Throwable $e) {
            return $this->gagal($e);
        }

        return response()->json([
            'message' => "Berhasil diproses. Baru: {$r->jumlah_baru}, diperbarui: {$r->jumlah_diperbarui}, selesai di fasih: {$r->jumlah_selesai_di_fasih}.",
        ]);
    }

    private function riwayatMilikUser(Request $request): RiwayatUnggah
    {
        $request->validate(['riwayat_id' => 'required|integer']);

        return RiwayatUnggah::where('id', $request->input('riwayat_id'))
            ->where('diunggah_oleh', $request->user()->id)
            ->firstOrFail();
    }

    private function gagal(\Throwable $e): JsonResponse
    {
        Log::error('UnggahBertahap gagal: '.$e->getMessage(), ['file' => $e->getFile(), 'baris' => $e->getLine()]);

        $pesan = get_class($e).': '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine();

        return response()->json([
            'message' => $pesan,
            'errors' => ['file_referensi' => [$pesan]],
        ], 422);
    }
}