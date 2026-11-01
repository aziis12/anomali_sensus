<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DataAnomali;
use App\Models\PenugasanPj;
use Illuminate\Support\Collection;

/**
 * Perhitungan rekap Ditindaklanjuti (FASIH) + Dikerjakan (pegawai), dipecah per
 * kombinasi PML+Kecamatan sesuai Penugasan PJ, lalu bisa diringkas per PJ atau
 * per PML lewat agregasi().
 *
 * Dipakai bareng oleh MonitoringTindakLanjutController (halaman rekap superadmin)
 * dan MonitoringController (halaman Progress pegawai & petugas), supaya angka yang
 * ditampilkan ke pegawai/petugas SELALU sama dengan yang dilihat superadmin --
 * gak ada lagi hitungan versi kedua yang bisa beda hasil.
 */
trait HitungRekapPj
{
    /**
     * Data mentah 1 baris = 1 kombinasi PML+Kecamatan, dibandingkan dengan
     * progres tindak lanjut & pengerjaan di data_anomali.
     */
    private function ambilRekapDetail(): Collection
    {
        $penugasan = PenugasanPj::query()->get()
            ->unique(fn ($satu) => $this->kunciNormalisasi($satu->pml, $satu->kecamatan))
            ->values();

        $dataPerPmlKec = DataAnomali::where('status_di_fasih', 'aktif')
            ->get()
            ->groupBy(fn ($baris) => $this->kunciNormalisasi($baris->pml, $baris->nama_kecamatan));

        return $penugasan->map(function ($satuPenugasan) use ($dataPerPmlKec) {
            $kunci = $this->kunciNormalisasi($satuPenugasan->pml, $satuPenugasan->kecamatan);
            $dataKelompokIni = $dataPerPmlKec->get($kunci, collect());

            // per kelompok_baris_excel (1 baris excel), bukan per baris DataAnomali,
            // konsisten dengan Data Anomali & halaman rekap lainnya
            $perKelompokBarisExcel = $dataKelompokIni->groupBy('kelompok_baris_excel');

            $ditindaklanjutiSudah = $perKelompokBarisExcel->filter(function ($grup) {
                $nilai = strtolower(trim($grup->first()->tindak_lanjut_fasih ?? ''));

                return $nilai !== '' && ! str_contains($nilai, 'belum');
            })->count();
            $ditindaklanjutiBelum = $perKelompokBarisExcel->count() - $ditindaklanjutiSudah;

            $dikerjakan = $perKelompokBarisExcel->filter(
                fn ($grup) => $grup->first()->status_assignment === 'sudah'
            )->count();
            $sisaBelumDikerjakan = $perKelompokBarisExcel->count() - $dikerjakan;

            return (object) [
                'pml' => $satuPenugasan->pml,
                'pj' => $satuPenugasan->pj,
                'kecamatan' => $satuPenugasan->kecamatan,
                'ditindaklanjuti_sudah' => $ditindaklanjutiSudah,
                'ditindaklanjuti_belum' => $ditindaklanjutiBelum,
                'dikerjakan' => $dikerjakan,
                'sisa_belum_dikerjakan' => $sisaBelumDikerjakan,
                'ditemukan' => $dataKelompokIni->isNotEmpty(),
            ];
        })->values();
    }

    /**
     * Ringkas data detail (per PML+Kecamatan) jadi 1 baris per $kolom ('pml' atau 'pj').
     */
    private function agregasi(Collection $detail, string $kolom): Collection
    {
        return $detail
            ->groupBy(fn ($baris) => $baris->{$kolom} ?: '(kosong)')
            ->map(function ($grup, $namaGrup) use ($kolom) {
                return (object) [
                    'nama' => $namaGrup,
                    'pj_terkait' => $kolom === 'pml' ? $grup->pluck('pj')->filter()->unique()->join(', ') : null,
                    'daftar_kecamatan' => $grup->pluck('kecamatan')->filter()->unique()->sort()->values()->join(', '),
                    'ditindaklanjuti_sudah' => $grup->sum('ditindaklanjuti_sudah'),
                    'ditindaklanjuti_belum' => $grup->sum('ditindaklanjuti_belum'),
                    'dikerjakan' => $grup->sum('dikerjakan'),
                    'sisa_belum_dikerjakan' => $grup->sum('sisa_belum_dikerjakan'),
                    'ditemukan' => $grup->contains('ditemukan', true),
                ];
            })
            ->sortBy('nama')
            ->values();
    }

    /**
     * Data mentah 1 baris = 1 kelompok_baris_excel, dipakai khusus buat rekap per PPL.
     * Beda sama ambilRekapDetail(): PPL gak punya tabel "penugasan" terpisah kayak
     * PenugasanPj -- dia cuma kolom polos di data_anomali (ikut kebawa dari excel pusat,
     * lihat DataAnomaliController::index() yang juga baca $ppl langsung dari sini).
     * Jadi sumbernya langsung dari DataAnomali, BUKAN dari penugasan_pj.
     */
    private function ambilRekapPplDetail(): Collection
    {
        return DataAnomali::where('status_di_fasih', 'aktif')
            ->get()
            ->groupBy('kelompok_baris_excel')
            ->map(function ($grup) {
                $wakil = $grup->first();

                $nilaiTindakLanjut = strtolower(trim($wakil->tindak_lanjut_fasih ?? ''));
                $sudahDitindaklanjuti = $nilaiTindakLanjut !== '' && ! str_contains($nilaiTindakLanjut, 'belum');
                $sudahDikerjakan = $wakil->status_assignment === 'sudah';

                return (object) [
                    'ppl' => $wakil->ppl,
                    'pml' => $wakil->pml,
                    'ditindaklanjuti_sudah' => $sudahDitindaklanjuti ? 1 : 0,
                    'ditindaklanjuti_belum' => $sudahDitindaklanjuti ? 0 : 1,
                    'dikerjakan' => $sudahDikerjakan ? 1 : 0,
                    'sisa_belum_dikerjakan' => $sudahDikerjakan ? 0 : 1,
                ];
            })
            ->values();
    }

    /**
     * Ringkas ambilRekapPplDetail() jadi 2 tingkat: PML -> daftar PPL yang dia pegang,
     * masing-masing PPL bawa angka sendiri (ditindaklanjuti sudah/belum, dikerjakan/sisa).
     * PML dipilih jadi level utama karena dia yang konsepnya bertanggung jawab
     * nindaklanjutin ke FASIH -- persis kayak tampilan Excel: 1 baris PML disorot
     * (jumlah gabungan semua PPL-nya), diikuti baris-baris PPL polos di bawahnya.
     */
    private function agregasiPmlPpl(Collection $detail): Collection
    {
        return $detail
            ->groupBy(fn ($baris) => $baris->pml ?: '(kosong)')
            ->map(function ($grupPml, $namaPml) {
                $daftarPpl = $grupPml
                    ->groupBy(fn ($baris) => $baris->ppl ?: '(kosong)')
                    ->map(function ($grupPpl, $namaPpl) {
                        return (object) [
                            'nama' => $namaPpl,
                            'ditindaklanjuti_sudah' => $grupPpl->sum('ditindaklanjuti_sudah'),
                            'ditindaklanjuti_belum' => $grupPpl->sum('ditindaklanjuti_belum'),
                            'dikerjakan' => $grupPpl->sum('dikerjakan'),
                            'sisa_belum_dikerjakan' => $grupPpl->sum('sisa_belum_dikerjakan'),
                        ];
                    })
                    ->sortBy('nama')
                    ->values();

                return (object) [
                    'nama' => $namaPml,
                    'daftar_ppl' => $daftarPpl,
                    'ditindaklanjuti_sudah' => $daftarPpl->sum('ditindaklanjuti_sudah'),
                    'ditindaklanjuti_belum' => $daftarPpl->sum('ditindaklanjuti_belum'),
                    'dikerjakan' => $daftarPpl->sum('dikerjakan'),
                    'sisa_belum_dikerjakan' => $daftarPpl->sum('sisa_belum_dikerjakan'),
                ];
            })
            ->sortBy('nama')
            ->values();
    }

    private function totalDari(Collection $rekap): object
    {
        return (object) [
            'ditindaklanjuti_sudah' => $rekap->sum('ditindaklanjuti_sudah'),
            'ditindaklanjuti_belum' => $rekap->sum('ditindaklanjuti_belum'),
            'dikerjakan' => $rekap->sum('dikerjakan'),
            'sisa_belum_dikerjakan' => $rekap->sum('sisa_belum_dikerjakan'),
        ];
    }

    // kunci gabungan pml+kecamatan yang dinormalisasi (trim + lowercase), dipakai untuk
    // mencocokkan baris Penugasan PJ ke data_anomali walau ada beda spasi/kapitalisasi
    // kecil antara file PJ dan file anomali pusat.
    private function kunciNormalisasi(?string $pml, ?string $kecamatan): string
    {
        return strtolower(trim($pml ?? '')).'|'.strtolower(trim($kecamatan ?? ''));
    }
}