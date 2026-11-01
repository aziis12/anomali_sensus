<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HitungRekapPj;
use App\Models\PenugasanPj;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    use HitungRekapPj;

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isPetugas()) {
            return $this->untukPetugas($request);
        }

        if ($user->isSuperadmin()) {
            // superadmin sekarang punya 2 halaman terpisah, arahin ke default-nya
            return redirect()->route('monitoring.progress-petugas');
        }

        return $this->untukPegawai($request);
    }

    /**
     * Rekap per PJ -- PERSIS sama perhitungannya dengan
     * MonitoringTindakLanjutController::perPj(), supaya angka yang dilihat
     * pegawai == angka yang dilihat superadmin. PJ itu buat pegawai.
     */
    public function progressPegawaiView(): View
    {
        $detail = $this->ambilRekapDetail();
        $rekap = $this->agregasi($detail, 'pj');

        return view('monitoring.progress-pegawai', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'adaPenugasan' => PenugasanPj::query()->exists(),
        ]);
    }

    /**
     * Rekap per PML -- PERSIS sama perhitungannya dengan
     * MonitoringTindakLanjutController::perPml(). PML itu buat petugas.
     */
    public function progressPetugasView(): View
    {
        $detail = $this->ambilRekapDetail();
        $rekap = $this->agregasi($detail, 'pml');

        return view('monitoring.progress-petugas', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'adaPenugasan' => PenugasanPj::query()->exists(),
        ]);
    }

    /**
     * Pegawai lihat rekap per PJ, sama kayak yang superadmin lihat -- gak difilter
     * ke pegawai tertentu, semua PJ ditampilkan.
     */
    private function untukPegawai(Request $request): View
    {
        return $this->progressPegawaiView();
    }

    /**
     * Petugas HARUS pilih kategori PML dulu (session nama_pml_dipilih, dipilih di
     * halaman Data Anomali) sebelum bisa lihat progress-nya. Begitu udah pilih,
     * angkanya dihitung dengan cara yang SAMA seperti rekap per-PML superadmin
     * (ditindaklanjuti_sudah/belum, dikerjakan, sisa_belum_dikerjakan), cuma
     * di-filter ke PML yang dia pilih aja.
     */
    private function untukPetugas(Request $request): View
    {
        $namaPml = session('nama_pml_dipilih');
        $pmlDicari = strtolower(trim($namaPml ?? ''));

        $detailPml = $namaPml
            ? $this->ambilRekapDetail()->filter(
                fn ($baris) => strtolower(trim($baris->pml ?? '')) === $pmlDicari
            )->values()
            : collect();

        // di-agregasi biar dapet bentuk objek yang konsisten (daftar_kecamatan,
        // ditemukan, dst) -- hasilnya cuma 1 baris karena semua sudah difilter ke 1 PML
        $ringkasan = $namaPml
            ? $this->agregasi($detailPml, 'pml')->first()
            : null;

        return view('monitoring.petugas', [
            'namaPml' => $namaPml,
            'ringkasan' => $ringkasan,
            'perKecamatan' => $detailPml,
        ]);
    }
}