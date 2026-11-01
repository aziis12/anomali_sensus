<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HitungRekapPj;
use App\Models\DataAnomali;
use App\Models\PenugasanPj;
use App\Models\RiwayatUnggah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MonitoringTindakLanjutController extends Controller
{
    use HitungRekapPj;

    /**
     * Halaman Rekap per PJ: 1 baris = 1 PJ (bukan per PJ+PML+Kecamatan lagi).
     * PJ itu konsepnya sama kayak "pegawai" -- yang mau dilihat cuma progres
     * gabungannya, bukan breakdown detail per kecamatan yang dia pegang.
     */
    public function perPj(): View
    {
        $detail = $this->ambilRekapDetail();
        $rekap = $this->agregasi($detail, 'pj');

        return view('monitoring-tindak-lanjut.per-pj', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'adaPenugasan' => PenugasanPj::query()->exists(),
        ]);
    }

    /**
     * Halaman Rekap per PML: 1 baris = 1 PML, totalnya gabungan semua
     * kecamatan yang dia pegang.
     */
    public function perPml(): View
    {
        $detail = $this->ambilRekapDetail();
        $rekap = $this->agregasi($detail, 'pml');

        return view('monitoring-tindak-lanjut.per-pml', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'adaPenugasan' => PenugasanPj::query()->exists(),
        ]);
    }

    /**
     * Halaman Rekap per PPL: dikelompokkan 2 tingkat, PML -> daftar PPL yang dia
     * pegang (biar PML gak berulang-ulang di tiap baris). Beda sumber data dari
     * per-PJ/per-PML -- PPL dihitung langsung dari data_anomali (kolom bawaan dari
     * excel pusat), gak lewat tabel penugasan_pj, jadi gak butuh $adaPenugasan.
     */
    public function perPpl(): View
    {
        $detail = $this->ambilRekapPplDetail();
        $rekap = $this->agregasiPmlPpl($detail);

        return view('monitoring-tindak-lanjut.per-ppl', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
        ]);
    }

    /**
     * Unduh Rekap per PJ dalam bentuk PDF (landscape), supaya semua kolom
     * & baris ikut tercetak tanpa terpotong seperti kalau screenshot browser.
     */
    public function perPjPdf()
    {
        $detail = $this->ambilRekapDetail();
        $rekap = collect($this->agregasi($detail, 'pj'))
            ->sortByDesc('sisa_belum_dikerjakan')
            ->values();

        $pdf = Pdf::loadView('monitoring-tindak-lanjut.pdf.per-pj', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-per-pj-'.now()->format('Y-m-d_H-i').'.pdf');
    }

    /**
     * Unduh Rekap per PML dalam bentuk PDF (landscape).
     */
    public function perPmlPdf()
    {
        $detail = $this->ambilRekapDetail();
        $rekap = collect($this->agregasi($detail, 'pml'))
            ->sortByDesc('sisa_belum_dikerjakan')
            ->values();

        $pdf = Pdf::loadView('monitoring-tindak-lanjut.pdf.per-pml', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-per-pml-'.now()->format('Y-m-d_H-i').'.pdf');
    }

    /**
     * Unduh Rekap per PPL dalam bentuk PDF (landscape), dikelompokkan per PML
     * sama kayak tampilan web.
     */
    public function perPplPdf()
    {
        $detail = $this->ambilRekapPplDetail();
        $rekap = $this->agregasiPmlPpl($detail)
            ->sortByDesc('sisa_belum_dikerjakan')
            ->values();

        $pdf = Pdf::loadView('monitoring-tindak-lanjut.pdf.per-ppl', [
            'rekap' => $rekap,
            'totalRekap' => $this->totalDari($rekap),
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-per-ppl-'.now()->format('Y-m-d_H-i').'.pdf');
    }

    /**
     * Terima upload excel Penugasan PJ dari superadmin. Format kolom di excel:
     * No, Email PML, PML, Email PJ, PJ, Kec (kolom "No" cuma informasi nomor
     * urut manual, tidak dipakai untuk logika apapun, jadi diabaikan saat
     * parsing).
     *
     * Setiap upload me-replace TOTAL isi tabel penugasan_pj (bukan
     * menambah/menimpa sebagian), supaya tabel ini selalu mencerminkan file
     * terakhir yang diupload, konsisten dengan pola upload lain di sistem ini.
     */
    public function unggahPj(Request $request): RedirectResponse
    {
        $request->validate([
            'file_pj' => 'required|file|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file_pj')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $semuaBaris = $sheet->toArray(null, true, true, false);

        // baris pertama diasumsikan header, dilewati
        $barisData = array_slice($semuaBaris, 1);

        $dataBaru = [];
        foreach ($barisData as $satuBaris) {
            // urutan kolom sesuai file: No, Email PML, PML, Email PJ, PJ, Kec
            [, $emailPml, $pml, $emailPj, $pj, $kecamatan] = array_pad(array_values($satuBaris), 6, null);

            $pml = trim((string) $pml);
            $pj = trim((string) $pj);
            $kecamatan = trim((string) $kecamatan);

            if ($pml === '' && $pj === '' && $kecamatan === '') {
                continue; // lewati baris kosong (mis. baris terakhir file yang gak sengaja kebawa)
            }

            $dataBaru[] = [
                'email_pml' => trim((string) $emailPml) ?: null,
                'pml' => $pml,
                'email_pj' => trim((string) $emailPj) ?: null,
                'pj' => $pj,
                'kecamatan' => $kecamatan,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // PENTING: pakai delete(), BUKAN truncate(). TRUNCATE di MySQL
        // melakukan implicit commit di tengah eksekusi (bagian dari DDL), jadi
        // kalau dipanggil di dalam DB::transaction(), transaksinya "putus"
        // duluan sebelum Laravel sempat commit di akhir -> error "There is no
        // active transaction". delete() adalah perintah DML biasa yang aman
        // dipakai di dalam transaksi.
        DB::transaction(function () use ($dataBaru) {
            PenugasanPj::query()->delete();
            foreach (array_chunk($dataBaru, 500) as $potongan) {
                PenugasanPj::insert($potongan);
            }
        });

        return back()->with('status', 'Data penugasan PJ berhasil diperbarui ('.count($dataBaru).' baris).');
    }

    /**
     * Konten Monitoring yang lama: total keseluruhan, breakdown per kategori
     * tindak lanjut, dan grafik tren riwayat unggah. Diakses lewat submenu
     * "Grafik & Breakdown".
     */
    public function grafik(): View
    {
        $total = DataAnomali::where('status_di_fasih', 'aktif')
            ->distinct()
            ->count('kelompok_baris_excel');

        $perKategori = DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->whereNotNull('tindak_lanjut_fasih')
            ->where('tindak_lanjut_fasih', '!=', '')
            ->selectRaw('tindak_lanjut_fasih as kategori, COUNT(DISTINCT kelompok_baris_excel) as jumlah')
            ->groupBy('tindak_lanjut_fasih')
            ->orderByDesc('jumlah')
            ->get()
            ->map(function ($baris) use ($total) {
                $baris->persentase = $total > 0 ? round(($baris->jumlah / $total) * 100, 1) : 0;

                return $baris;
            });

        $tanpaKategori = DataAnomali::where('status_di_fasih', 'aktif')
            ->where(function ($q) {
                $q->whereNull('tindak_lanjut_fasih')->orWhere('tindak_lanjut_fasih', '');
            })
            ->distinct()
            ->count('kelompok_baris_excel');

        $riwayatSekarang = RiwayatUnggah::latest('id')->first();
        $riwayatSebelumnya = $riwayatSekarang
            ? RiwayatUnggah::where('id', '<', $riwayatSekarang->id)->latest('id')->first()
            : null;

        $selisihTotal = null;
        if ($riwayatSebelumnya && $riwayatSebelumnya->total_data_saat_ini !== null) {
            $selisihTotal = $total - $riwayatSebelumnya->total_data_saat_ini;
        }

        $breakdownSebelumnya = collect($riwayatSebelumnya->breakdown_tindak_lanjut ?? []);

        $perKategori = $perKategori->map(function ($baris) use ($breakdownSebelumnya, $riwayatSebelumnya) {
            $baris->selisih = $riwayatSebelumnya
                ? $baris->jumlah - $breakdownSebelumnya->get($baris->kategori, 0)
                : null;

            return $baris;
        });

        $grafik = $this->siapkanDataGrafik();
        $riwayatTabel = $this->siapkanRiwayatTabel();

        return view('monitoring-tindak-lanjut.grafik', [
            'total' => $total,
            'perKategori' => $perKategori,
            'tanpaKategori' => $tanpaKategori,
            'selisihTotal' => $selisihTotal,
            'riwayatSekarang' => $riwayatSekarang,
            'riwayatSebelumnya' => $riwayatSebelumnya,
            'grafik' => $grafik,
            'riwayatTabel' => $riwayatTabel,
        ]);
    }

    private function siapkanRiwayatTabel(): \Illuminate\Support\Collection
    {
        $riwayat = RiwayatUnggah::whereNotNull('total_data_saat_ini')
            ->orderBy('created_at')
            ->get();

        $totalSebelumnya = null;
        $sudahSebelumnya = null;
        $belumSebelumnya = null;
        $hasil = collect();

        foreach ($riwayat as $baris) {
            $breakdown = collect($baris->breakdown_tindak_lanjut ?? []);
            $sudah = $breakdown->filter(
                fn ($jumlah, $kategori) => ! str_contains(strtolower($kategori), 'belum')
            )->sum();
            $belum = $breakdown->filter(
                fn ($jumlah, $kategori) => str_contains(strtolower($kategori), 'belum')
            )->sum();

            $hasil->push((object) [
                'tanggal' => $baris->created_at,
                'total' => $baris->total_data_saat_ini,
                'sudah' => $sudah,
                'belum' => $belum,
                'selisih_total' => $totalSebelumnya === null ? null : $baris->total_data_saat_ini - $totalSebelumnya,
                'selisih_sudah' => $sudahSebelumnya === null ? null : $sudah - $sudahSebelumnya,
                'selisih_belum' => $belumSebelumnya === null ? null : $belum - $belumSebelumnya,
            ]);

            $totalSebelumnya = $baris->total_data_saat_ini;
            $sudahSebelumnya = $sudah;
            $belumSebelumnya = $belum;
        }

        return $hasil->reverse()->values();
    }

    private function siapkanDataGrafik(): array
    {
        $riwayat = RiwayatUnggah::whereNotNull('total_data_saat_ini')
            ->orderBy('created_at')
            ->get();

        $labelUpload = [];
        $totalPerUpload = [];
        $selisihPerUpload = [];
        $kategoriYangMuncul = [];
        $breakdownPerUpload = [];

        $totalSebelumnya = null;

        foreach ($riwayat as $baris) {
            $labelUpload[] = $baris->created_at->translatedFormat('d M Y H:i');
            $totalPerUpload[] = $baris->total_data_saat_ini;
            $selisihPerUpload[] = $totalSebelumnya === null ? 0 : $baris->total_data_saat_ini - $totalSebelumnya;
            $totalSebelumnya = $baris->total_data_saat_ini;

            $breakdown = $baris->breakdown_tindak_lanjut ?? [];
            $breakdownPerUpload[] = $breakdown;

            foreach (array_keys($breakdown) as $kategori) {
                $kategoriYangMuncul[$kategori] = true;
            }
        }

        $datasetPerKategori = [];
        foreach (array_keys($kategoriYangMuncul) as $kategori) {
            $datasetPerKategori[$kategori] = array_map(
                fn ($breakdown) => $breakdown[$kategori] ?? 0,
                $breakdownPerUpload
            );
        }

        return [
            'label_upload' => $labelUpload,
            'total_per_upload' => $totalPerUpload,
            'selisih_per_upload' => $selisihPerUpload,
            'dataset_per_kategori' => $datasetPerKategori,
        ];
    }
}