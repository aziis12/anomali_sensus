<?php

namespace App\Http\Controllers;

use App\Models\DataAnomali;
use App\Models\RiwayatUnggah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatUnggahController extends Controller
{
    public function index(): View
    {
        $riwayat = RiwayatUnggah::with('pengunggah')->latest()->paginate(20);

        return view('riwayat-unggah.index', ['riwayat' => $riwayat]);
    }

    public function show(RiwayatUnggah $riwayatUnggah): View
    {
        $data = DataAnomali::where('riwayat_unggah_id', $riwayatUnggah->id)
            ->orderBy('link_fasih')
            ->orderBy('id')
            ->paginate(50);

        $jumlahSudahDikerjakan = DataAnomali::where('riwayat_unggah_id', $riwayatUnggah->id)
            ->where(function ($q) {
                $q->whereNotNull('tanggapan_petugas')->where('tanggapan_petugas', '!=', '')
                    ->orWhereNotNull('penyelesaian_anomali')->where('penyelesaian_anomali', '!=', '')
                    ->orWhere('status_assignment', 'sudah');
            })
            ->count();

        return view('riwayat-unggah.show', [
            'riwayat' => $riwayatUnggah,
            'data' => $data,
            'jumlahSudahDikerjakan' => $jumlahSudahDikerjakan,
        ]);
    }

    public function destroy(RiwayatUnggah $riwayatUnggah, Request $request): RedirectResponse
    {
        $query = DataAnomali::where('riwayat_unggah_id', $riwayatUnggah->id);

        $jumlahSudahDikerjakan = (clone $query)
            ->where(function ($q) {
                $q->whereNotNull('tanggapan_petugas')->where('tanggapan_petugas', '!=', '')
                    ->orWhereNotNull('penyelesaian_anomali')->where('penyelesaian_anomali', '!=', '')
                    ->orWhere('status_assignment', 'sudah');
            })
            ->count();

        // kalau ada data yang sudah dikerjakan, wajib konfirmasi ulang lewat checkbox di halaman detail
        if ($jumlahSudahDikerjakan > 0 && ! $request->boolean('konfirmasi_hapus_paksa')) {
            return back()->withErrors([
                'hapus' => "Ada {$jumlahSudahDikerjakan} data di riwayat ini yang sudah dikerjakan pegawai/petugas. Centang konfirmasi di bawah kalau kamu tetap yakin ingin menghapus semuanya.",
            ]);
        }

        $jumlahDihapus = $query->count();
        $query->delete();

        $riwayatUnggah->delete();

        return redirect()->route('riwayat-unggah.index')->with('status', "Riwayat unggah dan {$jumlahDihapus} data anomali terkait berhasil dihapus permanen.");
    }
}