<?php

namespace App\Services;

use App\Models\DataAnomali;
use App\Models\ReferensiPplPml;
use App\Models\RiwayatUnggah;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PemrosesExcelAnomali
{
    private const BARIS_HEADER = 4;

    private const BARIS_DATA_MULAI = 6;

    private array $kolomNamaEntitas = [
        'keluarga' => 'nama kepala keluarga',
        'usaha' => 'nama usaha',
    ];

    public function proses(string $pathUsaha, string $pathKeluarga, string $pathReferensi, int $idPengunggah, ?string $tanggalData = null): RiwayatUnggah
    {
        // referensi diproses duluan supaya lookup pml/ppl/nama sls langsung kena pas data anomali diproses
        $this->prosesReferensi($pathReferensi);

        $riwayat = RiwayatUnggah::create([
            'diunggah_oleh' => $idPengunggah,
            'nama_file_usaha' => basename($pathUsaha),
            'nama_file_keluarga' => basename($pathKeluarga),
            // tanggal periode data dipilih manual oleh superadmin, bukan otomatis dari waktu submit
            'tanggal_data' => $tanggalData,
        ]);

        $hasilUsaha = $this->prosesSatuFile($pathUsaha, 'usaha', $riwayat->id);
        $hasilKeluarga = $this->prosesSatuFile($pathKeluarga, 'keluarga', $riwayat->id);

        // kunci lengkap dipakai buat nandain baris "selesai di fasih", bukan cuma assignment_id
        // supaya baris kembar (assignment_id sama, nama anomali sama persis) yang urutannya
        // udah gak match di file terbaru juga ikut ketandai dengan benar, gak nyangkut jadi "aktif" terus
        $semuaKunciLengkap = array_merge($hasilUsaha['kunci_lengkap'], $hasilKeluarga['kunci_lengkap']);

        $jumlahSelesai = DataAnomali::where('status_di_fasih', 'aktif')
            ->get()
            ->filter(function ($baris) use ($semuaKunciLengkap) {
                $kunciBaris = $baris->assignment_id.'|'.$baris->kategori.'|'.$baris->nama_anomali_fasih.'|'.$baris->urutan_duplikat;

                return ! in_array($kunciBaris, $semuaKunciLengkap);
            })
            ->each(function ($baris) {
                $baris->update(['status_di_fasih' => 'tidak_muncul_lagi']);
            })
            ->count();

        // snapshot kondisi tindak_lanjut_fasih setelah upload ini selesai diproses,
        // dipakai buat nampilin perbandingan "+X data" di dashboard monitoring tindak lanjut pusat.
        // diambil setelah proses reconciliation di atas biar mencerminkan kondisi paling akhir.
        // difilter status_di_fasih = 'aktif' supaya konsisten dengan angka $total dan $perKategori
        // yang ditampilkan di dashboard (MonitoringTindakLanjutController) — sebelumnya query ini
        // ikut menghitung baris yang sudah "tidak_muncul_lagi", sehingga grafik jadi tidak nyambung
        // dengan kartu total di atasnya.
        $breakdownTindakLanjut = DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->selectRaw('tindak_lanjut_fasih as kategori, COUNT(*) as jumlah')
            ->whereNotNull('tindak_lanjut_fasih')
            ->where('tindak_lanjut_fasih', '!=', '')
            ->groupBy('tindak_lanjut_fasih')
            ->pluck('jumlah', 'kategori');

        $riwayat->update([
            'jumlah_baru' => $hasilUsaha['baru'] + $hasilKeluarga['baru'],
            'jumlah_diperbarui' => $hasilUsaha['diperbarui'] + $hasilKeluarga['diperbarui'],
            'jumlah_selesai_di_fasih' => $jumlahSelesai,
            // sama seperti breakdown di atas: difilter 'aktif' biar konsisten dengan $total di dashboard
            'total_data_saat_ini' => DataAnomali::where('status_di_fasih', 'aktif')->count(),
            'breakdown_tindak_lanjut' => $breakdownTindakLanjut,
        ]);

        return $riwayat;
    }

    public function prosesReferensi(string $path): int
    {
        $spreadsheet = IOFactory::load($path);
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

    private function prosesSatuFile(string $path, string $kategori, int $idRiwayat): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $barisHeader = $sheet[self::BARIS_HEADER - 1];
        $header = [];
        foreach ($barisHeader as $indeks => $namaKolom) {
            if ($namaKolom) {
                $header[strtolower(trim($namaKolom))] = $indeks;
            }
        }

        $kolomNamaEntitas = $this->kolomNamaEntitas[$kategori];
        $baru = 0;
        $diperbarui = 0;
        $daftarAssignmentId = [];
        $daftarKunciLengkap = [];
        $penghitungDuplikat = [];

        for ($i = self::BARIS_DATA_MULAI - 1; $i < count($sheet); $i++) {
            $baris = $sheet[$i];

            $assignmentId = $this->ambilNilai($baris, $header, 'assignment id');
            if (! $assignmentId) {
                continue;
            }

            $namaEntitas = $this->ambilNilai($baris, $header, $kolomNamaEntitas);
            if (! $namaEntitas) {
                continue;
            }

            $kodeDesa = trim((string) $this->ambilNilai($baris, $header, 'kode desa'));
            $kodeSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'kode sls')), 4, '0', STR_PAD_LEFT);
            $subSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'sub sls')), 2, '0', STR_PAD_LEFT);
            $idsubsls = $kodeDesa.$kodeSls.$subSls;

            $referensi = ReferensiPplPml::where('idsubsls', $idsubsls)->first();

            $namaAnomaliMentah = $this->ambilNilai($baris, $header, 'nama anomali');
            [$nomorAnomali, $deskripsiAnomali, $statusDariFasih] = $this->pecahNamaAnomali($namaAnomaliMentah);
            $daftarNomorAnomali = $this->ambilSemuaNomorAnomali($namaAnomaliMentah);
            $kategoriAnomali = $this->ambilKategoriAnomali($namaAnomaliMentah);

            // hitung berapa kali kombinasi ini sudah muncul di file yang sedang diproses,
            // biar mastiin baris yang benar-benar kembar tetap kepertahankan semua, bukan saling menimpa
            $kunciHitung = $assignmentId.'|'.$kategori.'|'.$namaAnomaliMentah;
            $penghitungDuplikat[$kunciHitung] = ($penghitungDuplikat[$kunciHitung] ?? 0) + 1;
            $urutanKe = $penghitungDuplikat[$kunciHitung];

            $statusAssignmentFasihExcel = $this->ambilNilai($baris, $header, 'status assigment dalam fasih');

            $dataUtama = [
                'kategori' => $kategori,
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
                'nama_entitas' => $namaEntitas,
                'link_fasih' => $this->ambilNilai($baris, $header, 'link fasih'),
                'nama_anomali_fasih' => $namaAnomaliMentah,
                'kategori_anomali_fasih' => $kategoriAnomali,
                'nomor_anomali' => $nomorAnomali,
                'daftar_nomor_anomali' => $daftarNomorAnomali,
                'deskripsi_anomali' => $deskripsiAnomali,
                'status_dari_fasih' => $statusDariFasih,
                'urutan_duplikat' => $urutanKe,

                'tindak_lanjut_fasih' => $this->ambilNilai($baris, $header, 'tindak lanjut'),
                'email_petugas_fasih' => $this->ambilNilai($baris, $header, 'email petugas'),
                'id_petugas_fasih' => $this->ambilNilai($baris, $header, 'id petugas'),
                'pml' => $referensi->pml ?? null,
                'ppl' => $referensi->ppl ?? null,
                'nama_sls' => $referensi->nama_sls ?? null,
                'status_di_fasih' => 'aktif',
                'data_spesifik' => $this->ambilKolomSpesifik($baris, $header, $kolomNamaEntitas),
                'riwayat_unggah_id' => $idRiwayat,
            ];

            // status assignment fasih cuma diisi kalau ada di excel, kalau enggak biarkan nilai default database yang jalan
            // pas baris pertama kali dibuat. Kalau baris sudah ada, kolom ini gak diikutkan supaya isian manual pegawai gak ketimpa.
            if ($statusAssignmentFasihExcel) {
                $dataUtama['status_assignment_fasih'] = $statusAssignmentFasihExcel;
            }

            $kunciUnik = [
                'assignment_id' => $assignmentId,
                'kategori' => $kategori,
                'nama_anomali_fasih' => $namaAnomaliMentah,
                'urutan_duplikat' => $urutanKe,
            ];

            $sudahAda = DataAnomali::where($kunciUnik)->exists();

            // kalau baris ini ditandai "tidak_muncul_lagi" di upload sebelumnya, tapi sekarang muncul lagi,
            // pastikan statusnya balik jadi aktif
            $dataUtama['status_di_fasih'] = 'aktif';

            DataAnomali::updateOrCreate($kunciUnik, $dataUtama);

            $sudahAda ? $diperbarui++ : $baru++;
            $daftarAssignmentId[] = $assignmentId;
            $daftarKunciLengkap[] = $assignmentId.'|'.$kategori.'|'.$namaAnomaliMentah.'|'.$urutanKe;
        }

        return [
            'baru' => $baru,
            'diperbarui' => $diperbarui,
            'assignment_id' => $daftarAssignmentId,
            'kunci_lengkap' => $daftarKunciLengkap,
        ];
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

    private function pecahNamaAnomali(?string $teks): array
    {
        if (! $teks) {
            return [null, null, null];
        }

        if (preg_match('/Jumlah Anomali (\d+) \(([^)]+)\)\s*(.*)/i', $teks, $cocok)) {
            return [(int) $cocok[1], trim($cocok[2]), trim($cocok[3]) ?: null];
        }

        return [null, null, $teks];
    }

    private function ambilSemuaNomorAnomali(?string $teks): array
    {
        if (! $teks) {
            return [];
        }

        if (! preg_match('/anomali\s*(?:data\s*)?((?:\d+\s*)+)/i', $teks, $cocok)) {
            return [];
        }

        preg_match_all('/\d+/', $cocok[1], $semuaAngka);

        $nomor = array_map('intval', $semuaAngka[0] ?? []);
        $nomor = array_filter($nomor, fn ($n) => $n >= 1 && $n <= 8);

        return array_values(array_unique($nomor));
    }

    private function ambilKategoriAnomali(?string $teks): ?string
    {
        if (! $teks) {
            return null;
        }

        if (preg_match('/^(Jumlah anomali(?:\s*data)?\s*\d+)/i', trim($teks), $cocok)) {
            return trim($cocok[1]);
        }

        return null;
    }

    private function ambilKolomSpesifik(array $baris, array $header, string $kolomNamaEntitas): array
    {
        $kolomYangSudahDipetakan = [
            'no', 'kode prov', 'nama provinsi', 'kode kab/kota', 'nama kab/kota',
            'kode kec', 'nama kecamatan', 'kode desa', 'nama desa/kel', 'kode sls', 'sub sls',
            'assignment id', 'nama anomali', 'tindak lanjut', 'id petugas', 'email petugas',
            'link fasih', 'idsubsls', 'status assigment dalam fasih', $kolomNamaEntitas,
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
}