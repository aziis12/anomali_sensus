<?php

namespace App\Services;

use App\Models\DataAnomali;
use App\Models\ReferensiPplPml;
use App\Models\RiwayatUnggah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parser untuk format baru dari pusat: SATU file excel yang sudah menggabungkan
 * data usaha & keluarga, dengan:
 * - Kolom "Nama Usaha / Kepala Keluarga" bisa berisi:
 *     "[Usaha: KERAJINAN KAYU (SRI ANDAYANI)] [Keluarga: MOCHAMAD ADRI PURNOMO]" (dua entitas)
 *   atau cuma nama polos tanpa bracket kalau cuma ada satu entitas, mis. "SUNDARI"
 * - Kolom "Daftar Anomali" berisi SEMUA anomali (usaha & keluarga) baris itu jadi satu teks,
 *   dipisah koma, tiap segmen berformat "Anomali Keluarga N (...)" atau "Anomali Usaha N (...)"
 *
 * Satu baris excel dipecah jadi BEBERAPA baris DataAnomali (1 baris = 1 entitas + 1 anomali),
 * sama strukturnya dengan hasil PemrosesExcelAnomali (format lama 2 file).
 *
 * CATATAN DIAGNOSTIK:
 * Baris/segmen yang di-skip dicatat ke log dengan nomor baris Excel saja, TANPA isi data
 * sensitif (tanpa nama entitas, link_fasih, atau assignment_id mentah).
 *
 * CATATAN "kelompok_baris_excel":
 * Satu link_fasih bisa muncul di lebih dari satu baris excel. Supaya tampilan web bisa
 * menampilkan satu kartu per baris excel, tiap DataAnomali dicatat "kelompok_baris_excel"-nya:
 * link_fasih + urutan kemunculan link_fasih itu dalam satu file. Field ini BUKAN bagian
 * kunci unik updateOrCreate.
 *
 * CATATAN PENGHITUNGAN "baru"/"diperbarui":
 * Dihitung per kelompok_baris_excel (1 baris excel), bukan per anomali. Status "sudah ada
 * sebelumnya" diambil dari snapshot di awal proses.
 *
 * CATATAN FILE REFERENSI OPSIONAL:
 * $pathReferensi boleh null. Kalau null, tabel referensi tidak disentuh dan tiap baris tetap
 * dicocokkan ke referensi yang sudah ada di database lewat idsubsls.
 *
 * CATATAN SINKRONISASI OTOMATIS "Penyelesaian Anomali":
 * Di akhir proses() otomatis memanggil SinkronisasiPenyelesaianAnomali::jalankan() sebelum
 * breakdown & total dihitung.
 *
 * FIX "TANGGAPAN HILANG SAAT UPLOAD ULANG":
 * Kunci unik memakai nomor_anomali (stabil), bukan teks penuh anomali yang bisa berubah
 * antar upload.
 *
 * FIX PERFORMA & MEMORI (ditambahkan):
 * 1. Deteksi "tidak_muncul_lagi" sekarang SATU query UPDATE berdasarkan riwayat_unggah_id
 *    (semua baris yang muncul di file ini sudah di-set riwayat_unggah_id = id riwayat ini).
 *    Sebelumnya: get() semua baris aktif ke memori + in_array O(n x m) + update per baris,
 *    yang bikin memory habis & timeout saat data sudah besar.
 * 2. Referensi PML/PPL/SLS dimuat sekali ke peta (keyBy idsubsls), bukan query per baris.
 * 3. Daftar kelompok_baris_excel unik & link_fasih unik pakai key array (O(1)), bukan in_array.
 * 4. Reader PhpSpreadsheet pakai setReadDataOnly + spreadsheet dilepas dari memori setelah dibaca.
 */
class PemrosesExcelAnomaliGabungan
{
    private const BARIS_HEADER = 4;

    private const BARIS_DATA_MULAI = 6;

    private const KOLOM_NAMA_ENTITAS = 'nama usaha / kepala keluarga';

    private const KOLOM_DAFTAR_ANOMALI = 'daftar anomali';

    public function proses(string $pathGabungan, ?string $pathReferensi, int $idPengunggah, ?string $tanggalData = null): RiwayatUnggah
    {
        if ($pathReferensi) {
            $this->prosesReferensi($pathReferensi);
        }

        $riwayat = RiwayatUnggah::create([
            'diunggah_oleh' => $idPengunggah,
            'nama_file_gabungan' => basename($pathGabungan),
            'tanggal_data' => $tanggalData,
        ]);

        $hasil = $this->prosesFileGabungan($pathGabungan, $riwayat->id);

        // Baris aktif yang TIDAK ikut ter-update oleh file ini (riwayat_unggah_id-nya bukan
        // id riwayat ini) berarti sudah gak muncul lagi di file terbaru -> tidak_muncul_lagi.
        // Satu query UPDATE, tanpa memuat apa pun ke memori PHP.
        $jumlahSelesai = DataAnomali::where('status_di_fasih', 'aktif')
            ->where(function ($q) use ($riwayat) {
                $q->where('riwayat_unggah_id', '!=', $riwayat->id)
                    ->orWhereNull('riwayat_unggah_id');
            })
            ->update(['status_di_fasih' => 'tidak_muncul_lagi']);

        // Sinkronisasi otomatis "Penyelesaian Anomali" (lihat docblock kelas).
        $jumlahDisinkronkan = (new SinkronisasiPenyelesaianAnomali)->jalankan();

        // Breakdown & total dihitung per KELOMPOK_BARIS_EXCEL (1 baris excel pusat),
        // konsisten dengan DataAnomaliController & MonitoringTindakLanjutController.
        $breakdownTindakLanjut = DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->whereNotNull('tindak_lanjut_fasih')
            ->where('tindak_lanjut_fasih', '!=', '')
            ->selectRaw('tindak_lanjut_fasih as kategori, COUNT(DISTINCT kelompok_baris_excel) as jumlah')
            ->groupBy('tindak_lanjut_fasih')
            ->pluck('jumlah', 'kategori');

        $riwayat->update([
            'jumlah_baru' => $hasil['baru'],
            'jumlah_diperbarui' => $hasil['diperbarui'],
            'jumlah_selesai_di_fasih' => $jumlahSelesai,
            'total_data_saat_ini' => DataAnomali::where('status_di_fasih', 'aktif')
                ->distinct()
                ->count('kelompok_baris_excel'),
            'breakdown_tindak_lanjut' => $breakdownTindakLanjut,
        ]);

        Log::info('PemrosesExcelAnomaliGabungan: ringkasan proses selesai', [
            'riwayat_unggah_id' => $riwayat->id,
            'file_referensi_diunggah' => $pathReferensi !== null,
            'total_baris_excel_berisi_data' => $hasil['diagnostik']['total_baris_excel'],
            'baris_diskip_assignment_id_kosong' => count($hasil['diagnostik']['skip_assignment_kosong']),
            'nomor_baris_assignment_id_kosong' => $hasil['diagnostik']['skip_assignment_kosong'],
            'baris_diskip_daftar_anomali_kosong' => count($hasil['diagnostik']['skip_daftar_anomali_kosong']),
            'nomor_baris_daftar_anomali_kosong' => $hasil['diagnostik']['skip_daftar_anomali_kosong'],
            'baris_tanpa_segmen_valid_sama_sekali' => count($hasil['diagnostik']['baris_tanpa_segmen_valid']),
            'nomor_baris_tanpa_segmen_valid' => $hasil['diagnostik']['baris_tanpa_segmen_valid'],
            'total_segmen_gagal_parse_format' => $hasil['diagnostik']['total_segmen_gagal_parse'],
            'total_segmen_nama_entitas_tidak_ketemu' => $hasil['diagnostik']['total_segmen_nama_tidak_ketemu'],
            'total_link_fasih_unik_berhasil_masuk' => $hasil['diagnostik']['total_link_fasih_unik'],
            'total_kelompok_baris_excel_unik_berhasil_masuk' => $hasil['diagnostik']['total_kelompok_baris_excel_unik'],
            'total_assignment_id_unik_di_excel' => $hasil['diagnostik']['total_assignment_id_unik'],
            'assignment_id_dipakai_lebih_dari_1_baris' => $hasil['diagnostik']['assignment_id_dobel'],
            'jumlah_penyelesaian_anomali_disinkronkan_otomatis' => $jumlahDisinkronkan,
        ]);

        return $riwayat;
    }

    /**
     * Identik dengan PemrosesExcelAnomali::prosesReferensi. Sengaja diduplikasi
     * supaya service lama & baru tetap berdiri sendiri-sendiri.
     */
    public function prosesReferensi(string $path): int
    {
        $sheet = $this->bacaSheet($path);

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
            throw new \Exception('Kolom idsub, pml, ppl, atau nama sls tidak ditemukan di file referensi. Pastikan header ada di baris pertama.');
        }

        $jumlah = 0;
        for ($i = 1; $i < count($sheet); $i++) {
            $baris = $sheet[$i];

            $idsubsls = preg_replace('/\.0$/', '', trim((string) ($baris[$kolomIdsub] ?? '')));

            if (! $idsubsls) {
                continue;
            }

            ReferensiPplPml::updateOrCreate(
                ['idsubsls' => $idsubsls],
                [
                    'pml' => trim((string) ($baris[$kolomPml] ?? '')) ?: null,
                    'ppl' => trim((string) ($baris[$kolomPpl] ?? '')) ?: null,
                    'nama_sls' => trim((string) ($baris[$kolomNamaSls] ?? '')) ?: null,
                ]
            );

            $jumlah++;
        }

        return $jumlah;
    }

    /**
     * Baca sheet aktif jadi array, dengan memori sehemat mungkin:
     * setReadDataOnly (abaikan styling) + lepas objek spreadsheet setelah dibaca.
     */
    private function bacaSheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        gc_collect_cycles();

        return $sheet;
    }

    private function prosesFileGabungan(string $path, int $idRiwayat): array
    {
        $sheet = $this->bacaSheet($path);

        $barisHeader = $sheet[self::BARIS_HEADER - 1];
        $header = [];
        foreach ($barisHeader as $indeks => $namaKolom) {
            if ($namaKolom) {
                $header[strtolower(trim($namaKolom))] = $indeks;
            }
        }

        if (! isset($header[self::KOLOM_DAFTAR_ANOMALI])) {
            throw new \Exception('Kolom "Daftar Anomali" tidak ditemukan di file. Pastikan header ada di baris '.self::BARIS_HEADER.'.');
        }

        $baru = 0;
        $diperbarui = 0;
        $penghitungDuplikat = [];

        // Snapshot kelompok_baris_excel yang SUDAH aktif sebelum file ini diproses
        // (diambil sekali, bukan query live per baris). distinct() supaya hasilnya ringkas.
        $kelompokBarisExcelSudahAdaSebelumnya = DataAnomali::where('status_di_fasih', 'aktif')
            ->distinct()
            ->pluck('kelompok_baris_excel')
            ->flip()
            ->all();

        // Peta referensi PML/PPL/Nama SLS dimuat SEKALI (bukan query per baris excel).
        // Kalau idsubsls belum ada referensinya, hasilnya null seperti sebelumnya.
        $petaReferensi = ReferensiPplPml::get(['idsubsls', 'pml', 'ppl', 'nama_sls'])->keyBy('idsubsls');

        // === counter & pencatat diagnostik (tanpa data sensitif) ===
        $totalBarisExcel = 0;
        $skipAssignmentKosong = [];
        $skipDaftarAnomaliKosong = [];
        $barisTanpaSegmenValid = [];
        $totalSegmenGagalParse = 0;
        $totalSegmenNamaTidakKetemu = 0;
        $petaAssignmentIdKeBarisExcel = []; // assignment_id => [nomor baris excel, ...]

        $petaLinkFasihKeUrutanBarisExcel = []; // link_fasih => sudah ketemu berapa kali
        $kelompokBarisExcelUnik = [];          // set: kelompok_baris_excel => true
        $linkFasihUnikBerhasilMasuk = [];      // set: link_fasih => true

        // Simpan per BATCH dalam transaksi (bukan autocommit per query): jauh lebih cepat
        // karena database tidak perlu menulis ke disk di setiap updateOrCreate().
        // Kalau ada error, batch yang sedang berjalan di-rollback.
        DB::beginTransaction();
        try {
        for ($i = self::BARIS_DATA_MULAI - 1; $i < count($sheet); $i++) {
            $baris = $sheet[$i];
            $nomorBarisExcel = $i + 1; // nomor baris asli di file excel (1-based)

            // lewati baris yang benar-benar kosong semua kolomnya
            $barisKosongSemua = true;
            foreach ($baris as $sel) {
                if ($sel !== null && trim((string) $sel) !== '') {
                    $barisKosongSemua = false;
                    break;
                }
            }
            if ($barisKosongSemua) {
                continue;
            }

            $totalBarisExcel++;

            if ($totalBarisExcel % 300 === 0) {
                DB::commit();
                DB::beginTransaction();
            }

            $assignmentId = $this->ambilNilai($baris, $header, 'assignment id');
            if (! $assignmentId) {
                $skipAssignmentKosong[] = $nomorBarisExcel;

                continue;
            }

            $daftarAnomaliMentah = $this->ambilNilai($baris, $header, self::KOLOM_DAFTAR_ANOMALI);
            if (! $daftarAnomaliMentah) {
                $skipDaftarAnomaliKosong[] = $nomorBarisExcel;

                continue;
            }

            $petaAssignmentIdKeBarisExcel[$assignmentId][] = $nomorBarisExcel;

            $namaMentah = $this->ambilNilai($baris, $header, self::KOLOM_NAMA_ENTITAS);
            $petaNamaEntitas = $this->pecahNamaEntitas($namaMentah);

            $kodeDesa = trim((string) $this->ambilNilai($baris, $header, 'kode desa'));
            $kodeSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'kode sls')), 4, '0', STR_PAD_LEFT);
            $subSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'sub sls')), 2, '0', STR_PAD_LEFT);
            $idsubsls = $kodeDesa.$kodeSls.$subSls;

            // referensi dicari dari peta yang sudah dimuat di awal (bukan query per baris)
            $referensi = $petaReferensi[$idsubsls] ?? null;

            // link_fasih baris ini + urutan kemunculan ke berapa untuk link_fasih tsb
            $linkFasihBarisIni = $this->ambilNilai($baris, $header, 'link fasih');
            $petaLinkFasihKeUrutanBarisExcel[$linkFasihBarisIni] = ($petaLinkFasihKeUrutanBarisExcel[$linkFasihBarisIni] ?? 0) + 1;
            $kelompokBarisExcel = $linkFasihBarisIni.'|'.$petaLinkFasihKeUrutanBarisExcel[$linkFasihBarisIni];

            $kelompokBarisExcelUnik[$kelompokBarisExcel] = true;

            // field yang sama nilainya buat semua anomali hasil pecahan baris excel ini
            $dataUmum = [
                'idsubsls' => $idsubsls,
                'kode_provinsi' => $this->ambilNilai($baris, $header, 'kode prov'),
                'nama_provinsi' => $this->ambilNilai($baris, $header, 'nama provinsi'),
                'kode_kabkota' => $this->ambilNilai($baris, $header, 'kode kab/kota'),
                'nama_kabkota' => $this->ambilNilai($baris, $header, 'nama kab/kota'),
                'kode_kecamatan' => $this->ambilNilai($baris, $header, 'kode kec'),
                'nama_kecamatan' => $this->ambilNilai($baris, $header, 'nama kecamatan'),
                'kode_desa' => $kodeDesa,
                'nama_desa' => $this->ambilNilai($baris, $header, 'nama desa/kel'),
                'kode_sls' => $kodeSls,
                'sub_sls' => $subSls,
                'link_fasih' => $linkFasihBarisIni,
                'kelompok_baris_excel' => $kelompokBarisExcel,
                'assignment_id' => $assignmentId,
                'tindak_lanjut_fasih' => $this->ambilNilai($baris, $header, 'tindak lanjut'),
                'email_petugas_fasih' => $this->ambilNilai($baris, $header, 'email petugas'),
                'id_petugas_fasih' => $this->ambilNilai($baris, $header, 'id petugas'),
                'pml' => $referensi->pml ?? null,
                'ppl' => $referensi->ppl ?? null,
                'nama_sls' => $referensi->nama_sls ?? null,
                // PENTING: dipakai proses() untuk mendeteksi baris yang tidak muncul lagi
                'riwayat_unggah_id' => $idRiwayat,
            ];

            $dataSpesifikBaris = $this->ambilKolomSpesifik($baris, $header);

            $segmenAnomali = $this->pecahDaftarAnomali($daftarAnomaliMentah);

            $adaSegmenValidDiBarisIni = false;

            foreach ($segmenAnomali as $segmen) {
                $parsed = $this->parseSegmenAnomali($segmen);
                if (! $parsed) {
                    $totalSegmenGagalParse++;
                    Log::warning('PemrosesExcelAnomaliGabungan: segmen gagal di-parse (format tidak dikenali)', [
                        'baris_excel' => $nomorBarisExcel,
                    ]);

                    continue;
                }

                [$kategori, $nomorAnomali, $deskripsi] = $parsed;

                $namaEntitas = $petaNamaEntitas[$kategori] ?? $petaNamaEntitas['tunggal'] ?? null;
                if (! $namaEntitas) {
                    $totalSegmenNamaTidakKetemu++;
                    Log::warning('PemrosesExcelAnomaliGabungan: nama entitas tidak ditemukan untuk kategori segmen', [
                        'baris_excel' => $nomorBarisExcel,
                        'kategori' => $kategori,
                    ]);

                    continue;
                }

                // urutan duplikat berdasarkan NOMOR ANOMALI; kunci pakai link_fasih (bukan
                // cuma assignment_id) supaya baris usaha & keluarga satu assignment tidak saling timpa.
                $kunciHitung = $linkFasihBarisIni.'|'.$assignmentId.'|'.$kategori.'|'.$nomorAnomali;
                $penghitungDuplikat[$kunciHitung] = ($penghitungDuplikat[$kunciHitung] ?? 0) + 1;
                $urutanKe = $penghitungDuplikat[$kunciHitung];

                $dataUtama = array_merge($dataUmum, [
                    'kategori' => $kategori,
                    'nama_entitas' => $namaEntitas,
                    'nama_anomali_fasih' => $segmen,
                    'kategori_anomali_fasih' => 'Anomali '.ucfirst($kategori).' '.$nomorAnomali,
                    'nomor_anomali' => $nomorAnomali,
                    'daftar_nomor_anomali' => [$nomorAnomali],
                    'deskripsi_anomali' => $deskripsi,
                    'status_dari_fasih' => null,
                    'urutan_duplikat' => $urutanKe,
                    'data_spesifik' => $this->filterDataSpesifikUntukSegmen($dataSpesifikBaris, $nomorAnomali),
                    'status_di_fasih' => 'aktif',
                ]);

                // Kunci unik pakai nomor_anomali (stabil), BUKAN teks penuh segmen.
                $kunciUnik = [
                    'link_fasih' => $linkFasihBarisIni,
                    'assignment_id' => $assignmentId,
                    'kategori' => $kategori,
                    'nomor_anomali' => $nomorAnomali,
                    'urutan_duplikat' => $urutanKe,
                ];

                DataAnomali::updateOrCreate($kunciUnik, $dataUtama);

                $adaSegmenValidDiBarisIni = true;
                $linkFasihUnikBerhasilMasuk[$linkFasihBarisIni] = true;
            }

            if ($adaSegmenValidDiBarisIni) {
                // "baru"/"diperbarui" dihitung SEKALI PER BARIS EXCEL (kelompok_baris_excel)
                if (isset($kelompokBarisExcelSudahAdaSebelumnya[$kelompokBarisExcel])) {
                    $diperbarui++;
                } else {
                    $baru++;
                }
            } else {
                $barisTanpaSegmenValid[] = $nomorBarisExcel;
                Log::warning('PemrosesExcelAnomaliGabungan: baris excel tidak menghasilkan DataAnomali sama sekali', [
                    'baris_excel' => $nomorBarisExcel,
                    'jumlah_segmen_di_baris_ini' => count($segmenAnomali),
                ]);
            }
        }

        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // assignment_id yang dipakai oleh lebih dari 1 baris excel (cuma nomor baris yang dicatat)
        $assignmentIdDobel = [];
        foreach ($petaAssignmentIdKeBarisExcel as $daftarBaris) {
            if (count($daftarBaris) > 1) {
                $assignmentIdDobel[] = $daftarBaris;
            }
        }

        return [
            'baru' => $baru,
            'diperbarui' => $diperbarui,
            'diagnostik' => [
                'total_baris_excel' => $totalBarisExcel,
                'skip_assignment_kosong' => $skipAssignmentKosong,
                'skip_daftar_anomali_kosong' => $skipDaftarAnomaliKosong,
                'baris_tanpa_segmen_valid' => $barisTanpaSegmenValid,
                'total_segmen_gagal_parse' => $totalSegmenGagalParse,
                'total_segmen_nama_tidak_ketemu' => $totalSegmenNamaTidakKetemu,
                'total_assignment_id_unik' => count($petaAssignmentIdKeBarisExcel),
                'assignment_id_dobel' => $assignmentIdDobel,
                'total_kelompok_baris_excel_unik' => count($kelompokBarisExcelUnik),
                'total_link_fasih_unik' => count($linkFasihUnikBerhasilMasuk),
            ],
        ];
    }

    /**
     * "[Usaha: KERAJINAN KAYU (SRI ANDAYANI)] [Keluarga: MOCHAMAD ADRI PURNOMO]"
     *   -> ['usaha' => 'KERAJINAN KAYU (SRI ANDAYANI)', 'keluarga' => 'MOCHAMAD ADRI PURNOMO']
     *
     * "SUNDARI" (tanpa bracket sama sekali)
     *   -> ['tunggal' => 'SUNDARI']
     */
    private function pecahNamaEntitas(?string $namaMentah): array
    {
        if (! $namaMentah) {
            return [];
        }

        $namaMentah = trim($namaMentah);
        $hasil = [];

        if (preg_match_all('/\[\s*(Usaha|Keluarga)\s*:\s*([^\]]+)\]/i', $namaMentah, $cocok, PREG_SET_ORDER)) {
            foreach ($cocok as $satu) {
                $hasil[strtolower($satu[1])] = trim($satu[2]);
            }
        }

        if (empty($hasil)) {
            $hasil['tunggal'] = $namaMentah;
        }

        return $hasil;
    }

    /**
     * Pecah teks "Daftar Anomali" jadi beberapa segmen, dipisah di koma yang diikuti
     * awal pola baru "Anomali Keluarga/Usaha N (...)". Koma di dalam tanda kurung
     * tidak ikut terpotong.
     */
    private function pecahDaftarAnomali(string $teks): array
    {
        $segmen = preg_split(
            '/,\s*(?=Anomali\s+(?:Keluarga|Usaha)\s+\d+\s*\()/i',
            trim($teks)
        );

        return array_values(array_filter(array_map('trim', $segmen)));
    }

    /**
     * "Anomali Keluarga 4 (Luas lantai per kapita < 3 m2 atau > 200 m2)"
     *   -> ['keluarga', 4, 'Luas lantai per kapita < 3 m2 atau > 200 m2']
     */
    private function parseSegmenAnomali(string $segmen): ?array
    {
        if (! preg_match('/^Anomali\s+(Keluarga|Usaha)\s+(\d+)\s*\((.*)\)\s*$/is', trim($segmen), $cocok)) {
            return null;
        }

        return [strtolower($cocok[1]), (int) $cocok[2], trim($cocok[3])];
    }

    /**
     * Kolom penjelasan bernomor (mis. "Anomali 1 Penjelasan") cuma relevan buat baris
     * dengan nomor_anomali yang sama; kolom lain yang tidak bernomor ikut ke semua pecahan.
     */
    private function filterDataSpesifikUntukSegmen(array $dataSpesifikBaris, int $nomorAnomali): array
    {
        $hasil = [];
        foreach ($dataSpesifikBaris as $kunci => $nilai) {
            if (preg_match('/^anomali\s*(\d+)/i', $kunci, $cocok) && (int) $cocok[1] !== $nomorAnomali) {
                continue;
            }

            $hasil[$kunci] = $nilai;
        }

        return $hasil;
    }

    private function ambilKolomSpesifik(array $baris, array $header): array
    {
        $kolomYangSudahDipetakan = [
            'no', 'kode prov', 'nama provinsi', 'kode kab/kota', 'nama kab/kota',
            'kode kec', 'nama kecamatan', 'kode desa', 'nama desa/kel', 'kode sls', 'sub sls',
            'assignment id', self::KOLOM_DAFTAR_ANOMALI, 'tindak lanjut', 'id petugas', 'email petugas',
            'link fasih', 'idsubsls', self::KOLOM_NAMA_ENTITAS,
        ];

        $hasil = [];
        foreach ($header as $namaKolom => $indeks) {
            if (in_array($namaKolom, $kolomYangSudahDipetakan)) {
                continue;
            }

            $nilai = $baris[$indeks] ?? null;
            if ($nilai !== null && $nilai !== '') {
                $hasil[$namaKolom] = $nilai;
            }
        }

        return $hasil;
    }

    private function ambilNilai(array $baris, array $header, string $namaKolom): ?string
    {
        if (! isset($header[$namaKolom])) {
            return null;
        }

        $nilai = $baris[$header[$namaKolom]] ?? null;

        return $nilai === null ? null : trim((string) $nilai);
    }

    private function bersihkanAngka(?string $nilai): string
    {
        return preg_replace('/\.0$/', '', trim((string) $nilai));
    }
}