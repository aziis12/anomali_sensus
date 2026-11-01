<?php

namespace App\Http\Controllers;

use App\Models\DataAnomali;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImporDataLamaController extends Controller
{
    private const BARIS_HEADER = 4;

    private const BARIS_DATA_MULAI = 5;

    private const STATUS_FASIH_BAKU = [
        'APPROVED BY PENGAWAS',
        'SUBMITTED BY PENCACAH',
        'REJECTED BY PENGAWAS',
        'COMPLETED BY ADMIN KABUPATEN',
        'EDITED BY ADMIN KABUPATEN',
        'REVOKED BY PENGAWAS',
        'REJECTED BY ADMIN KABUPATEN',
    ];

    // label yang enak dibaca manusia untuk tiap nama kolom database
    private const LABEL_KOLOM = [
        'status_assignment_fasih' => 'Status assignment fasih',
        'perlu_konfirm_petugas' => 'Perlu konfirm petugas',
        'tanggapan_petugas' => 'Tanggapan petugas',
        'tindak_lanjut_fasih' => 'Tindak lanjut fasih',
        'status_assignment' => 'Penyelesaian anomali',
        'tanggal_respon' => 'Tanggal respon',
    ];

    public function create(): View
    {
        return view('impor-data-lama.create');
    }

    public function hasil(): View
    {
        return view('impor-data-lama.hasil', [
            'hasil' => Cache::get($this->kunciCacheHasil()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file_lama' => 'required|file|mimes:xlsx',
        ]);

        $path = $request->file('file_lama')->store('unggahan_sementara');

        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $barisHeader = $sheet[self::BARIS_HEADER - 1];
        $header = [];
        foreach ($barisHeader as $indeks => $namaKolom) {
            if ($namaKolom) {
                $header[strtolower(trim($namaKolom))] = $indeks;
            }
        }

        // NB: file lama (2-file era) selalu punya SALAH SATU dari dua kolom ini,
        // gak pernah dua-duanya sekaligus dalam satu file. Kolom mana yang ketemu
        // dipakai buat nentuin kategori SELURUH ISI FILE ini usaha atau keluarga —
        // dulu kategori ditentukan dari file mana data berasal, bukan dari teks
        // "Nama Anomali"-nya sendiri (beda dengan format gabungan baru).
        //
        // ASUMSI INI PERLU DIKONFIRMASI: kalau "file lama" yang diupload di sini
        // ternyata adalah hasil dari fitur Ekspor Excel (data-anomali.ekspor) —
        // bukan file asli dari pusat — kolomnya "Nama Kepala Keluarga" dipakai
        // sebagai judul TETAP untuk semua baris (lihat DataAnomaliController::ekspor),
        // termasuk baris kategori usaha. Kalau itu kasusnya, $kategoriFile di bawah
        // akan salah untuk baris usaha, dan perlu pendekatan lain (mis. tambah kolom
        // "Kategori" manual, atau fallback ke pencocokan teks nama_anomali_fasih).
        $kolomNamaKepalaKeluarga = $this->cariKolom($header, 'nama kepala keluarga');
        $kolomNamaUsaha = $kolomNamaKepalaKeluarga === null ? $this->cariKolom($header, 'nama usaha') : null;
        $kategoriFile = $kolomNamaKepalaKeluarga !== null ? 'keluarga' : ($kolomNamaUsaha !== null ? 'usaha' : null);
        $kolomNamaEntitas = $kolomNamaKepalaKeluarga ?? $kolomNamaUsaha;

        $kolomNamaAnomali = $this->cariKolom($header, 'nama anomali');
        $kolomLinkFasih = $this->cariKolom($header, 'link fasih');
        $kolomStatusFasih = $this->cariKolom($header, 'status assigment');
        $kolomPerluKonfirm = $this->cariKolom($header, 'perlu konfirm');
        $kolomPenyelesaian = $this->cariKolom($header, 'penyelesaian');
        $kolomTanggapan = $this->cariKolom($header, 'tanggapan');
        $kolomRespon = $this->cariKolom($header, 'respon tanggal');
        $kolomTindakLanjut = $this->cariKolom($header, 'tindak lanjut');

        if (in_array(null, [$kolomNamaAnomali, $kolomLinkFasih], true)) {
            Storage::delete($path);

            return back()->withErrors(['file_lama' => 'Kolom Nama Anomali atau Link Fasih tidak ditemukan di file.']);
        }

        $jumlahDicocokkan = 0;
        $jumlahDiisi = 0;
        $jumlahTidakKetemu = 0;
        $jumlahSudahTerisiSebelumnya = 0;
        $statusFasihTidakDikenali = [];
        $penyelesaianTidakDikenali = [];
        $tanggalGagalDiparse = [];
        $namaTidakKetemu = [];
        $namaDiisi = [];
        $namaDilewati = [];

        for ($i = self::BARIS_DATA_MULAI - 1; $i < count($sheet); $i++) {
            $baris = $sheet[$i];

            $namaAnomaliMentah = trim((string) ($baris[$kolomNamaAnomali] ?? ''));
            $linkFasih = trim((string) ($baris[$kolomLinkFasih] ?? ''));
            $namaEntitas = $kolomNamaEntitas !== null ? trim((string) ($baris[$kolomNamaEntitas] ?? '')) : null;

            if (! $namaAnomaliMentah || ! $linkFasih) {
                continue;
            }

            // KUNCI UTAMA pencocokan sekarang: link_fasih + kategori + nomor_anomali
            // (bukan lagi teks kategori_anomali_fasih apa adanya). Ini supaya tetap
            // nyambung walau data di database sekarang berasal dari parser format
            // BARU yang teksnya "Anomali Keluarga 4 (...)", sedangkan file lama ini
            // masih nulis "Jumlah Anomali 4 (...)" — beda teks, tapi nomor & kategorinya sama.
            $nomorAnomaliLama = $this->ambilNomorAnomali($namaAnomaliMentah);

            $query = DataAnomali::where('link_fasih', $linkFasih);

            if ($kategoriFile) {
                $query->where('kategori', $kategoriFile);
            }

            if ($nomorAnomaliLama !== null) {
                $query->where('nomor_anomali', $nomorAnomaliLama);
                $kandidat = $query->get();
            } else {
                // fallback kalau nomor gak bisa diextract dari teks (format tak terduga):
                // balik ke pencocokan teks kategori seperti sebelumnya, sebagai jaring pengaman
                $kategoriBersihTeks = $this->ambilKategoriAnomaliDariTeksLama($namaAnomaliMentah);
                if ($kategoriBersihTeks) {
                    $query->where('kategori_anomali_fasih', $kategoriBersihTeks);
                    $kandidat = $query->get();
                } else {
                    $namaAnomaliNormal = $this->normalisasiTeks($namaAnomaliMentah);
                    $kandidat = $query->get()->filter(
                        fn ($row) => $this->normalisasiTeks($row->nama_anomali_fasih) === $namaAnomaliNormal
                    );
                }
            }

            if ($namaEntitas) {
                $namaEntitasNormal = $this->normalisasiTeks($namaEntitas);
                $kandidat = $kandidat->filter(
                    fn ($row) => $this->normalisasiTeks($row->nama_entitas) === $namaEntitasNormal
                );
            }

            $barisCocok = $kandidat->values();

            if ($barisCocok->isEmpty()) {
                $jumlahTidakKetemu++;
                $namaTidakKetemu[] = [
                    'nama' => $namaEntitas ?: '(tanpa nama)',
                    'kategori' => ($kategoriFile ? ucfirst($kategoriFile).' ' : '').($nomorAnomaliLama ?? $namaAnomaliMentah),
                    'link' => $linkFasih,
                ];

                continue;
            }

            $jumlahDicocokkan++;

            $statusFasihMentah = $kolomStatusFasih !== null ? trim((string) ($baris[$kolomStatusFasih] ?? '')) : null;
            $perluKonfirm = $kolomPerluKonfirm !== null ? trim((string) ($baris[$kolomPerluKonfirm] ?? '')) : null;
            $penyelesaianMentah = $kolomPenyelesaian !== null ? trim((string) ($baris[$kolomPenyelesaian] ?? '')) : null;
            $tanggapan = $kolomTanggapan !== null ? trim((string) ($baris[$kolomTanggapan] ?? '')) : null;
            $responTanggalMentah = $kolomRespon !== null ? ($baris[$kolomRespon] ?? null) : null;
            $tindakLanjutMentah = $kolomTindakLanjut !== null ? trim((string) ($baris[$kolomTindakLanjut] ?? '')) : null;

            $statusFasihBaku = $statusFasihMentah ? $this->normalisasiStatusFasih($statusFasihMentah) : null;

            if ($statusFasihMentah && ! $statusFasihBaku) {
                $statusFasihTidakDikenali[$statusFasihMentah] = ($statusFasihTidakDikenali[$statusFasihMentah] ?? 0) + 1;
            }

            $tanggalRespon = $this->parseTanggal($responTanggalMentah);

            if ($responTanggalMentah && ! $tanggalRespon) {
                $key = (string) $responTanggalMentah;
                $tanggalGagalDiparse[$key] = ($tanggalGagalDiparse[$key] ?? 0) + 1;
            }

            $statusPenyelesaian = $this->tentukanStatusPenyelesaian($penyelesaianMentah);

            if ($penyelesaianMentah && $statusPenyelesaian === null) {
                $penyelesaianTidakDikenali[$penyelesaianMentah] = ($penyelesaianTidakDikenali[$penyelesaianMentah] ?? 0) + 1;
            }

            $adaPerubahanBarisIni = false;
            $kolomDiisiBarisIni = [];    // field yang BARU diisi lewat impor ini
            $kolomSudahAdaBarisIni = []; // field yang SUDAH ada isinya sebelum impor (makanya dilewati)

            foreach ($barisCocok as $target) {
                $perluUpdate = [];

                if ($statusFasihMentah && ! $target->status_assignment_fasih) {
                    $perluUpdate['status_assignment_fasih'] = $statusFasihBaku ?: $statusFasihMentah;
                }

                if ($perluKonfirm && ! $target->perlu_konfirm_petugas) {
                    $perluUpdate['perlu_konfirm_petugas'] = $perluKonfirm;
                }

                if ($tanggapan && ! $target->tanggapan_petugas) {
                    $perluUpdate['tanggapan_petugas'] = $tanggapan;
                }

                if ($tindakLanjutMentah && ! $target->tindak_lanjut_fasih) {
                    $perluUpdate['tindak_lanjut_fasih'] = $tindakLanjutMentah;
                }

                if ($statusPenyelesaian !== null && $target->status_assignment !== $statusPenyelesaian) {
                    $perluUpdate['status_assignment'] = $statusPenyelesaian;
                }

                if ($kolomRespon !== null) {
                    $tanggalSama = ($tanggalRespon === null && $target->tanggal_respon === null)
                        || ($tanggalRespon !== null && $target->tanggal_respon !== null && $tanggalRespon->isSameDay($target->tanggal_respon));

                    if (! $tanggalSama) {
                        $perluUpdate['tanggal_respon'] = $tanggalRespon;
                    }
                }

                // rekam kondisi field yang SUDAH terisi di database sebelum impor ini jalan,
                // biar kalau baris ini dilewati, kelihatan alasannya (field mana aja yang sudah ada)
                if ($target->status_assignment_fasih) {
                    $kolomSudahAdaBarisIni['Status assignment fasih'] = $target->status_assignment_fasih;
                }
                if ($target->perlu_konfirm_petugas) {
                    $kolomSudahAdaBarisIni['Perlu konfirm petugas'] = $target->perlu_konfirm_petugas;
                }
                if ($target->tanggapan_petugas) {
                    $kolomSudahAdaBarisIni['Tanggapan petugas'] = $target->tanggapan_petugas;
                }
                if ($target->tindak_lanjut_fasih) {
                    $kolomSudahAdaBarisIni['Tindak lanjut fasih'] = $target->tindak_lanjut_fasih;
                }
                if ($target->status_assignment) {
                    $kolomSudahAdaBarisIni['Penyelesaian anomali'] = $target->status_assignment;
                }
                if ($target->tanggal_respon) {
                    $kolomSudahAdaBarisIni['Tanggal respon'] = $target->tanggal_respon->format('d-m-Y');
                }

                if (! empty($perluUpdate)) {
                    $target->update($perluUpdate);
                    $jumlahDiisi++;
                    $adaPerubahanBarisIni = true;

                    foreach ($perluUpdate as $kolom => $nilai) {
                        $label = self::LABEL_KOLOM[$kolom] ?? $kolom;
                        $nilaiTampil = $nilai instanceof Carbon ? $nilai->format('d-m-Y') : ((string) $nilai ?: '(dikosongkan)');
                        $kolomDiisiBarisIni[$label] = $nilaiTampil;
                    }
                }
            }

            $namaUntukContoh = $namaEntitas ?: '(tanpa nama)';

            if ($adaPerubahanBarisIni) {
                $namaDiisi[] = [
                    'nama' => $namaUntukContoh,
                    'kolom' => $kolomDiisiBarisIni,
                ];
            } else {
                $jumlahSudahTerisiSebelumnya++;
                $namaDilewati[] = [
                    'nama' => $namaUntukContoh,
                    'kolom' => $kolomSudahAdaBarisIni,
                ];
            }
        }

        Storage::delete($path);

        $pesan = "Impor selesai. Baris dicocokkan: {$jumlahDicocokkan}, data diisi: {$jumlahDiisi}, sudah terisi sebelumnya (dilewati): {$jumlahSudahTerisiSebelumnya}, tidak ketemu pasangannya: {$jumlahTidakKetemu}.";

        if (! empty($statusFasihTidakDikenali)) {
            $daftar = collect($statusFasihTidakDikenali)->map(fn ($j, $t) => "\"{$t}\" ({$j}x)")->implode(', ');
            $pesan .= " Istilah status fasih tidak dikenali (tetap disimpan apa adanya): {$daftar}.";
        }

        if (! empty($penyelesaianTidakDikenali)) {
            $daftar = collect($penyelesaianTidakDikenali)->map(fn ($j, $t) => "\"{$t}\" ({$j}x)")->implode(', ');
            $pesan .= " Istilah penyelesaian anomali tidak dikenali (status_assignment tidak diubah): {$daftar}.";
        }

        if (! empty($tanggalGagalDiparse)) {
            $daftar = collect($tanggalGagalDiparse)->map(fn ($j, $t) => "\"{$t}\" ({$j}x)")->implode(', ');
            $pesan .= " Format tanggal gagal dibaca: {$daftar}.";
        }

        // simpan daftar LENGKAP (bukan cuma contoh terpotong) ke cache,
        // buat ditampilkan di halaman hasil terpisah (bisa dibuka di tab baru, tahan logout)
        Cache::put($this->kunciCacheHasil(), [
            'ringkasan' => $pesan,
            'diisi' => $namaDiisi,
            'dilewati' => $namaDilewati,
            'tidak_ketemu' => $namaTidakKetemu,
            'waktu' => now()->format('d M Y, H:i'),
        ], now()->addDays(7));

        return back()->with('status', $pesan)->with('ada_hasil_detail', true);
    }

    /**
     * Extract nomor anomali (integer) dari teks lama "Jumlah Anomali N (...)".
     * Ini yang jadi kunci pencocokan utama sekarang, bukan lagi teks kategorinya.
     */
    private function ambilNomorAnomali(?string $teks): ?int
    {
        if (! $teks) {
            return null;
        }

        if (preg_match('/^Jumlah anomali(?:\s*data)?\s*(\d+)/i', trim($teks), $cocok)) {
            return (int) $cocok[1];
        }

        return null;
    }

    // dipertahankan sebagai FALLBACK kalau ambilNomorAnomali() gagal extract nomor
    private function ambilKategoriAnomaliDariTeksLama(?string $teks): ?string
    {
        if (! $teks) {
            return null;
        }

        if (preg_match('/^(Jumlah anomali(?:\s*data)?\s*\d+)/i', trim($teks), $cocok)) {
            return trim($cocok[1]);
        }

        return null;
    }

    private function normalisasiTeks(?string $teks): string
    {
        $teks = mb_strtolower(trim((string) $teks));
        // buang SEMUA karakter selain huruf dan angka (spasi, tanda kurung, titik, koma, strip, dst)
        // ini sengaja agresif: dua teks yang isinya sama tapi beda gaya spasi/tanda baca
        // (misal "nama(usaha)" vs "nama (usaha)" vs "nama - usaha") akan tetap dianggap sama
        $teks = preg_replace('/[^a-z0-9]+/', '', $teks);

        return $teks;
    }

    private function normalisasiStatusFasih(string $mentah): ?string
    {
        $bersih = strtoupper(trim(preg_replace('/\s+/', ' ', $mentah)));

        if (in_array($bersih, self::STATUS_FASIH_BAKU, true)) {
            return $bersih;
        }

        foreach (self::STATUS_FASIH_BAKU as $baku) {
            $tanpaBy = str_replace(' BY ', ' ', $baku);
            if ($bersih === $tanpaBy) {
                return $baku;
            }
        }

        if (str_contains($bersih, 'REJECTED') && str_contains($bersih, 'KABUPATEN')) {
            return 'REJECTED BY ADMIN KABUPATEN';
        }
        if (str_contains($bersih, 'REJECTED')) {
            return 'REJECTED BY PENGAWAS';
        }
        if (str_contains($bersih, 'REVOKED')) {
            return 'REVOKED BY PENGAWAS';
        }
        if (str_contains($bersih, 'COMPLETED')) {
            return 'COMPLETED BY ADMIN KABUPATEN';
        }
        if (str_contains($bersih, 'EDIT')) {
            return 'EDITED BY ADMIN KABUPATEN';
        }
        if (str_contains($bersih, 'SUBMITTED')) {
            return 'SUBMITTED BY PENCACAH';
        }
        if (str_contains($bersih, 'APPROVED')) {
            return 'APPROVED BY PENGAWAS';
        }

        return null;
    }

    private function tentukanStatusPenyelesaian(?string $penyelesaianMentah): ?string
    {
        $teks = strtolower(trim((string) $penyelesaianMentah));

        if ($teks === '') {
            return null;
        }

        if (str_contains($teks, 'belum')) {
            return 'belum';
        }

        if (str_contains($teks, 'fasih sm')) {
            return 'sudah';
        }

        return null;
    }

    private function parseTanggal($nilai): ?Carbon
    {
        if (! $nilai) {
            return null;
        }

        if (is_numeric($nilai)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject($nilai));
            } catch (\Exception $e) {
                return null;
            }
        }

        $teks = trim((string) $nilai);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'j/n/Y', 'd F Y', 'd M Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $teks);
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($teks);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cariKolom(array $header, string $kata): ?int
    {
        foreach ($header as $nama => $kolom) {
            if (str_contains($nama, $kata)) {
                return $kolom;
            }
        }

        return null;
    }

    // key di-scope per user, biar hasil impor punya si A gak numpuk/ketuker sama punya si B
    private function kunciCacheHasil(): string
    {
        return 'hasil_impor_lama_user_'.auth()->id();
    }
}