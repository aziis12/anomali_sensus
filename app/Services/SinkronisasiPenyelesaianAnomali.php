<?php

namespace App\Services;

use App\Models\DataAnomali;
use Illuminate\Support\Collection;

/**
 * Menyamakan status_assignment ("Penyelesaian Anomali" di web) dengan
 * tindak_lanjut_fasih yang datang dari Fasih/pusat, KHUSUS untuk
 * KELOMPOK_BARIS_EXCEL (1 kelompok = 1 kartu di halaman Data Anomali = 1
 * baris di file excel pusat) yang:
 * 1. SEMUA baris DataAnomali di dalamnya masih status_assignment = 'belum'
 *    (belum ada satupun yang pernah disentuh manual lewat form web), DAN
 * 2. SEMUA baris DataAnomali di dalamnya tindak_lanjut_fasih-nya sudah
 *    menunjukkan pernah ditindaklanjuti (bukan kosong, bukan mengandung
 *    kata "belum").
 *
 * PENTING (kenapa per KELOMPOK, bukan per baris DataAnomali satu-satu):
 * Seluruh sistem ini mengasumsikan satu kelompok_baris_excel selalu punya
 * nilai status_assignment yang SERAGAM di semua barisnya -- karena satu-
 * satunya jalur update manual (updateGrup(), dipanggil dari 1 form per kartu
 * di Data Anomali) selalu mengubah SEMUA baris dalam kelompok itu jadi nilai
 * yang sama sekaligus. Kartu di Data Anomali ("Penyelesaian: Sudah/Belum")
 * dan hitungan "Anomali Dikerjakan" di rekap PJ/PML (HitungRekapPj) sama-sama
 * bergantung pada asumsi ini -- Data Anomali pakai every() (cek SEMUA baris),
 * HitungRekapPj pakai first() (cek baris PERTAMA saja, dianggap cukup karena
 * asumsi di atas).
 *
 * Versi SEBELUMNYA dari service ini mengubah status_assignment per baris
 * DataAnomali satu-satu berdasarkan tindak_lanjut_fasih baris itu sendiri.
 * Kalau satu kelompok_baris_excel punya lebih dari satu anomali dan cuma
 * sebagian barisnya yang tindak_lanjut_fasih-nya "sudah", itu bikin SATU
 * kelompok jadi punya isi status_assignment CAMPURAN (sebagian 'sudah',
 * sebagian 'belum') -- melanggar asumsi di atas, dan menyebabkan kartu Data
 * Anomali (every()) dan rekap progres (first()) bisa menghasilkan angka yang
 * BEDA untuk kelompok yang sama. Versi ini memperbaikinya dengan menyinkronkan
 * SELURUH kelompok sekaligus, bukan baris per baris.
 *
 * Dipakai di 2 tempat:
 * - Otomatis di akhir PemrosesExcelAnomaliGabungan::proses().
 * - Command `anomali:sinkronkan-penyelesaian-awal` (manual/dry-run).
 *
 * CATATAN (idempotent): syarat #1 di atas ("SEMUA baris masih 'belum'")
 * membuat sinkronisasi ini aman dijalankan berkali-kali -- begitu satu
 * kelompok pernah disentuh (baik oleh sinkronisasi ini sendiri di run
 * sebelumnya, maupun oleh pegawai lewat form), kelompok itu tidak akan
 * disentuh lagi di run berikutnya.
 */
class SinkronisasiPenyelesaianAnomali
{
    /**
     * Jalankan sinkronisasi. Mengembalikan jumlah KELOMPOK_BARIS_EXCEL (kartu)
     * yang ikut disinkronkan -- konsisten dengan cara "baru"/"diperbarui"
     * dihitung di tempat lain pada sistem ini (per kelompok, bukan per baris).
     */
    public function jalankan(): int
    {
        $daftarKelompok = $this->kelompokSiapDisinkronkan();

        if (empty($daftarKelompok)) {
            return 0;
        }

        DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->whereIn('kelompok_baris_excel', $daftarKelompok)
            ->update([
                'status_assignment' => 'sudah',
                'tanggal_respon' => now(),
                'diupdate_oleh' => null,
            ]);

        return count($daftarKelompok);
    }

    /**
     * Hitung berapa KELOMPOK_BARIS_EXCEL yang AKAN kena sinkronisasi kalau
     * jalankan() dipanggil, tanpa benar-benar mengubah apa pun.
     */
    public function hitungYangPerluDisinkronkan(): int
    {
        return count($this->kelompokSiapDisinkronkan());
    }

    /**
     * Contoh beberapa kelompok yang akan kena sinkronisasi (diwakili 1 baris
     * per kelompok), dipakai buat ditampilkan sekilas di --dry-run supaya
     * bisa dicek kewajarannya.
     */
    public function contohBarisYangPerluDisinkronkan(int $limit = 10): Collection
    {
        $daftarKelompok = array_slice($this->kelompokSiapDisinkronkan(), 0, $limit);

        if (empty($daftarKelompok)) {
            return collect();
        }

        return DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->whereIn('kelompok_baris_excel', $daftarKelompok)
            ->get(['id', 'kelompok_baris_excel', 'link_fasih', 'nama_entitas', 'nama_anomali_fasih', 'tindak_lanjut_fasih'])
            ->groupBy('kelompok_baris_excel')
            ->map(fn ($grup) => $grup->first())
            ->values();
    }

    /**
     * Cari kelompok_baris_excel mana saja yang memenuhi SEMUA syarat berikut:
     * - masih aktif (status_di_fasih = 'aktif')
     * - SEMUA baris di dalamnya status_assignment masih 'belum'
     * - SEMUA baris di dalamnya tindak_lanjut_fasih menunjukkan sudah pernah
     *   ditindaklanjuti (logic disamakan persis dengan $semuaSudahTindakLanjut
     *   di view data-anomali/index.blade.php, supaya konsisten sama badge
     *   "Sudah ditindaklanjuti" yang tampil di kartu)
     *
     * @return array<int, string>
     */
    private function kelompokSiapDisinkronkan(): array
    {
        return DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->get(['kelompok_baris_excel', 'status_assignment', 'tindak_lanjut_fasih'])
            ->groupBy('kelompok_baris_excel')
            ->filter(function (Collection $grup) {
                $belumPernahDisentuh = $grup->every(
                    fn ($baris) => $baris->status_assignment === 'belum'
                );

                if (! $belumPernahDisentuh) {
                    return false;
                }

                return $grup->every(function ($baris) {
                    $nilai = strtolower(trim($baris->tindak_lanjut_fasih ?? ''));

                    return $nilai !== '' && ! str_contains($nilai, 'belum');
                });
            })
            ->keys()
            ->all();
    }
}