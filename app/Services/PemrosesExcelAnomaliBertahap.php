<?php

namespace App\Services;

use App\Models\DataAnomali;
use App\Models\ReferensiPplPml;
use App\Models\RiwayatUnggah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use XMLReader;
use ZipArchive;

/**
 * Versi BERTAHAP & CEPAT dari PemrosesExcelAnomaliGabungan.
 *
 * Alur: mulai() -> prosesBatch() berulang -> selesaikan().
 *
 * mulai(): file xlsx dibaca SEKALI lewat streaming XML (tanpa PhpSpreadsheet, jadi cepat
 *          & hemat memori), lalu dipecah jadi file potongan JSON kecil.
 * prosesBatch(): tiap request cuma membaca 1 potongan (waktu konstan, tidak makin lambat),
 *          mengambil baris lama dari database SEKALI per batch, lalu hanya meng-UPDATE
 *          baris yang isinya benar-benar berubah.
 */
class PemrosesExcelAnomaliBertahap
{
    private const BARIS_HEADER = 4;

    private const BARIS_DATA_MULAI = 6;

    private const BARIS_PER_BATCH = 400;

    private const KOLOM_NAMA_ENTITAS = 'nama usaha / kepala keluarga';

    private const KOLOM_DAFTAR_ANOMALI = 'daftar anomali';

    public function mulai(string $pathGabungan, ?string $pathReferensi, int $idPengunggah, ?string $tanggalData): array
    {
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        if ($pathReferensi) {
            (new PemrosesExcelAnomaliGabungan)->prosesReferensi($pathReferensi);
        }

        $riwayat = RiwayatUnggah::create([
            'diunggah_oleh' => $idPengunggah,
            'nama_file_gabungan' => basename($pathGabungan),
            'tanggal_data' => $tanggalData,
        ]);

        $dirChunk = $this->dirChunk($riwayat->id);
        if (! is_dir($dirChunk)) {
            mkdir($dirChunk, 0775, true);
        }

        [$barisHeader, $totalBaris] = $this->ekstrakKeChunk($pathGabungan, $dirChunk);

        $header = [];
        foreach ($barisHeader as $indeks => $namaKolom) {
            if ($namaKolom !== null && trim((string) $namaKolom) !== '') {
                $header[strtolower(trim((string) $namaKolom))] = $indeks;
            }
        }

        if (! isset($header[self::KOLOM_DAFTAR_ANOMALI])) {
            throw new \Exception('Kolom "Daftar Anomali" tidak ditemukan di file. Pastikan header ada di baris '.self::BARIS_HEADER.'.');
        }

        $this->simpanState($riwayat->id, [
            'path' => $pathGabungan,
            'path_referensi' => $pathReferensi,
            'total_baris' => $totalBaris,
            'header' => $header,
            'duplikat' => [],
            'urutan_link' => [],
            'baru' => 0,
            'diperbarui' => 0,
            'baris_diproses' => 0,
        ]);

        return [
            'riwayat_id' => $riwayat->id,
            'awal' => self::BARIS_DATA_MULAI,
            'total_baris' => $totalBaris,
        ];
    }

    public function prosesBatch(int $idRiwayat, int $awal): array
    {
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $t0 = microtime(true);

        $state = $this->ambilState($idRiwayat);
        $total = $state['total_baris'];
        $awal = max($awal, self::BARIS_DATA_MULAI);

        if ($awal > $total) {
            return ['berikutnya' => $total + 1, 'total_baris' => $total, 'selesai_baca' => true];
        }

        $akhir = min($awal + self::BARIS_PER_BATCH - 1, $total);
        $header = $state['header'];

        $rows = $this->bacaChunk($idRiwayat, intdiv($awal - self::BARIS_DATA_MULAI, self::BARIS_PER_BATCH));
        $detikBaca = round(microtime(true) - $t0, 2);

        $petaReferensi = ReferensiPplPml::get(['idsubsls', 'pml', 'ppl', 'nama_sls'])->keyBy('idsubsls');

        // ambil SEKALI semua baris lama untuk link_fasih di batch ini (bukan 1 SELECT per anomali)
        $daftarLink = [];
        foreach ($rows as $baris) {
            $l = $this->ambilNilai($baris, $header, 'link fasih');
            if ($l !== null && $l !== '') {
                $daftarLink[$l] = true;
            }
        }
        $baruDariDb = [];
        if ($daftarLink) {
            foreach (array_chunk(array_keys($daftarLink), 300) as $potong) {
                foreach (DataAnomali::whereIn('link_fasih', $potong)->get() as $m) {
                    $baruDariDb[$m->link_fasih.'|'.$m->assignment_id.'|'.$m->kategori.'|'.$m->nomor_anomali.'|'.$m->urutan_duplikat] = $m;
                }
            }
        }

        $idTanpaPerubahan = [];

        DB::transaction(function () use ($rows, $header, $petaReferensi, $idRiwayat, &$state, &$baruDariDb, &$idTanpaPerubahan) {
            foreach ($rows as $nomorBarisExcel => $baris) {
                $state['baris_diproses']++;

                $assignmentId = $this->ambilNilai($baris, $header, 'assignment id');
                if (! $assignmentId) {
                    continue;
                }

                $daftarAnomaliMentah = $this->ambilNilai($baris, $header, self::KOLOM_DAFTAR_ANOMALI);
                if (! $daftarAnomaliMentah) {
                    continue;
                }

                $petaNamaEntitas = $this->pecahNamaEntitas($this->ambilNilai($baris, $header, self::KOLOM_NAMA_ENTITAS));

                $kodeDesa = trim((string) $this->ambilNilai($baris, $header, 'kode desa'));
                $kodeSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'kode sls')), 4, '0', STR_PAD_LEFT);
                $subSls = str_pad($this->bersihkanAngka($this->ambilNilai($baris, $header, 'sub sls')), 2, '0', STR_PAD_LEFT);
                $idsubsls = $kodeDesa.$kodeSls.$subSls;

                $referensi = $petaReferensi[$idsubsls] ?? null;

                $linkFasih = (string) $this->ambilNilai($baris, $header, 'link fasih');
                $kunciLink = substr(md5($linkFasih), 0, 12);
                $state['urutan_link'][$kunciLink] = ($state['urutan_link'][$kunciLink] ?? 0) + 1;
                $kelompokBarisExcel = $linkFasih.'|'.$state['urutan_link'][$kunciLink];

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
                    'link_fasih' => $linkFasih,
                    'kelompok_baris_excel' => $kelompokBarisExcel,
                    'assignment_id' => $assignmentId,
                    'tindak_lanjut_fasih' => $this->ambilNilai($baris, $header, 'tindak lanjut'),
                    'email_petugas_fasih' => $this->ambilNilai($baris, $header, 'email petugas'),
                    'id_petugas_fasih' => $this->ambilNilai($baris, $header, 'id petugas'),
                    'pml' => $referensi->pml ?? null,
                    'ppl' => $referensi->ppl ?? null,
                    'nama_sls' => $referensi->nama_sls ?? null,
                    'riwayat_unggah_id' => $idRiwayat,
                ];

                $dataSpesifikBaris = $this->ambilKolomSpesifik($baris, $header);
                $segmenAnomali = $this->pecahDaftarAnomali($daftarAnomaliMentah);

                $adaSegmenValid = false;
                $semuaBaru = true;

                foreach ($segmenAnomali as $segmen) {
                    $parsed = $this->parseSegmenAnomali($segmen);
                    if (! $parsed) {
                        Log::warning('PemrosesExcelAnomaliBertahap: segmen gagal di-parse', ['baris_excel' => $nomorBarisExcel]);

                        continue;
                    }

                    [$kategori, $nomorAnomali, $deskripsi] = $parsed;

                    $namaEntitas = $petaNamaEntitas[$kategori] ?? $petaNamaEntitas['tunggal'] ?? null;
                    if (! $namaEntitas) {
                        Log::warning('PemrosesExcelAnomaliBertahap: nama entitas tidak ketemu', [
                            'baris_excel' => $nomorBarisExcel,
                            'kategori' => $kategori,
                        ]);

                        continue;
                    }

                    $kunciHitung = substr(md5($linkFasih.'|'.$assignmentId.'|'.$kategori.'|'.$nomorAnomali), 0, 12);
                    $state['duplikat'][$kunciHitung] = ($state['duplikat'][$kunciHitung] ?? 0) + 1;
                    $urutanKe = $state['duplikat'][$kunciHitung];

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

                    $kunciUnik = [
                        'link_fasih' => $linkFasih,
                        'assignment_id' => $assignmentId,
                        'kategori' => $kategori,
                        'nomor_anomali' => $nomorAnomali,
                        'urutan_duplikat' => $urutanKe,
                    ];

                    $kunciPeta = $linkFasih.'|'.$assignmentId.'|'.$kategori.'|'.$nomorAnomali.'|'.$urutanKe;
                    $model = $baruDariDb[$kunciPeta] ?? null;

                    if ($model) {
                        $semuaBaru = false;
                        $model->fill($dataUtama);
                        $kotor = $model->getDirty();
                        unset($kotor['riwayat_unggah_id'], $kotor['updated_at']);

                        if ($kotor) {
                            $model->save();
                        } else {
                            // isi tidak berubah: cukup tandai riwayat_unggah_id lewat 1 query massal
                            $idTanpaPerubahan[] = $model->id;
                        }
                    } else {
                        DataAnomali::create(array_merge($kunciUnik, $dataUtama));
                    }

                    $adaSegmenValid = true;
                }

                if ($adaSegmenValid) {
                    $semuaBaru ? $state['baru']++ : $state['diperbarui']++;
                }
            }

            foreach (array_chunk($idTanpaPerubahan, 500) as $potong) {
                DataAnomali::whereIn('id', $potong)->update([
                    'riwayat_unggah_id' => $idRiwayat,
                    'updated_at' => now(),
                ]);
            }
        });

        // state baru disimpan setelah transaksi sukses, jadi batch yang gagal bisa diulang
        $this->simpanState($idRiwayat, $state);

        return [
            'berikutnya' => $akhir + 1,
            'total_baris' => $total,
            'selesai_baca' => $akhir >= $total,
            'detik_baca_chunk' => $detikBaca,
            'detik_total' => round(microtime(true) - $t0, 2),
        ];
    }

    public function selesaikan(int $idRiwayat): RiwayatUnggah
    {
        set_time_limit(0);

        $state = $this->ambilState($idRiwayat);
        $riwayat = RiwayatUnggah::findOrFail($idRiwayat);

        $jumlahSelesai = DataAnomali::where('status_di_fasih', 'aktif')
            ->where(function ($q) use ($riwayat) {
                $q->where('riwayat_unggah_id', '!=', $riwayat->id)
                    ->orWhereNull('riwayat_unggah_id');
            })
            ->update(['status_di_fasih' => 'tidak_muncul_lagi']);

        $jumlahDisinkronkan = (new SinkronisasiPenyelesaianAnomali)->jalankan();

        $breakdownTindakLanjut = DataAnomali::query()
            ->where('status_di_fasih', 'aktif')
            ->whereNotNull('tindak_lanjut_fasih')
            ->where('tindak_lanjut_fasih', '!=', '')
            ->selectRaw('tindak_lanjut_fasih as kategori, COUNT(DISTINCT kelompok_baris_excel) as jumlah')
            ->groupBy('tindak_lanjut_fasih')
            ->pluck('jumlah', 'kategori');

        $riwayat->update([
            'jumlah_baru' => $state['baru'],
            'jumlah_diperbarui' => $state['diperbarui'],
            'jumlah_selesai_di_fasih' => $jumlahSelesai,
            'total_data_saat_ini' => DataAnomali::where('status_di_fasih', 'aktif')
                ->distinct()
                ->count('kelompok_baris_excel'),
            'breakdown_tindak_lanjut' => $breakdownTindakLanjut,
        ]);

        Log::info('PemrosesExcelAnomaliBertahap: proses selesai', [
            'riwayat_unggah_id' => $riwayat->id,
            'baris_excel_diproses' => $state['baris_diproses'],
            'baru' => $state['baru'],
            'diperbarui' => $state['diperbarui'],
            'selesai_di_fasih' => $jumlahSelesai,
            'penyelesaian_anomali_disinkronkan' => $jumlahDisinkronkan,
        ]);

        @unlink($state['path']);
        if (! empty($state['path_referensi'])) {
            @unlink($state['path_referensi']);
        }
        Storage::delete($this->pathState($idRiwayat));
        Storage::deleteDirectory('unggahan_sementara/chunk_'.$idRiwayat);

        return $riwayat;
    }

    // ---------- state sementara ----------

    private function pathState(int $idRiwayat): string
    {
        return 'unggahan_sementara/state_'.$idRiwayat.'.json';
    }

    private function simpanState(int $idRiwayat, array $state): void
    {
        Storage::put($this->pathState($idRiwayat), json_encode($state, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function ambilState(int $idRiwayat): array
    {
        $isi = Storage::exists($this->pathState($idRiwayat)) ? Storage::get($this->pathState($idRiwayat)) : null;
        if (! $isi) {
            throw new \Exception('Data proses upload tidak ditemukan. Ulangi upload dari awal.');
        }

        return json_decode($isi, true);
    }

    // ---------- baca xlsx sekali (streaming XML) -> potongan JSON ----------

    private function dirChunk(int $idRiwayat): string
    {
        return Storage::path('unggahan_sementara/chunk_'.$idRiwayat);
    }

    private function bacaChunk(int $idRiwayat, int $indeks): array
    {
        $file = $this->dirChunk($idRiwayat).'/'.$indeks.'.json';
        if (! is_file($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true) ?: [];
        ksort($data);

        return $data;
    }

    /**
     * Baca sheet pertama lewat XMLReader. Mengembalikan [barisHeader, totalBaris].
     * Baris data (>= BARIS_DATA_MULAI) ditulis ke file potongan: <dir>/<indeks>.json
     * berisi { nomorBarisExcel: { indeksKolom0Based: nilai } }.
     */
    private function ekstrakKeChunk(string $path, string $dirChunk): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \Exception('File xlsx tidak bisa dibuka. Pastikan file tidak rusak.');
        }

        $entriSheet = $this->cariEntriSheet($zip);
        $shared = $this->bacaSharedStrings($zip, $path);
        $zip->close();

        $r = new XMLReader;
        if (! $r->open('zip://'.$path.'#'.$entriSheet)) {
            throw new \Exception('Sheet pertama di file xlsx tidak bisa dibaca.');
        }

        $barisHeader = [];
        $totalBaris = 0;
        $buffer = [];
        $indeksBuffer = null;

        $nomorBaris = 0;
        $cells = [];
        $kol = 0;
        $tipe = null;
        $nilai = null;

        $flush = function () use (&$buffer, &$indeksBuffer, $dirChunk) {
            if ($indeksBuffer !== null && $buffer) {
                file_put_contents(
                    $dirChunk.'/'.$indeksBuffer.'.json',
                    json_encode($buffer, JSON_INVALID_UTF8_SUBSTITUTE)
                );
            }
            $buffer = [];
        };

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                switch ($r->localName) {
                    case 'row':
                        $nomorBaris = (int) $r->getAttribute('r');
                        $cells = [];
                        if ($r->isEmptyElement) {
                            $nomorBaris = 0;
                        }
                        break;
                    case 'c':
                        $kol = $this->indeksKolom((string) $r->getAttribute('r'));
                        $tipe = $r->getAttribute('t');
                        $nilai = null;
                        break;
                    case 'v':
                        $nilai = $r->readString();
                        break;
                    case 't':
                        if ($tipe === 'inlineStr') {
                            $nilai = ($nilai ?? '').$r->readString();
                        }
                        break;
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT) {
                if ($r->localName === 'c') {
                    if ($nilai !== null && $nilai !== '') {
                        if ($tipe === 's') {
                            $nilai = $shared[(int) $nilai] ?? '';
                        }
                        if ($nilai !== '') {
                            $cells[$kol] = $nilai;
                        }
                    }
                } elseif ($r->localName === 'row' && $nomorBaris > 0) {
                    if ($nomorBaris === self::BARIS_HEADER) {
                        $barisHeader = $cells;
                    } elseif ($nomorBaris >= self::BARIS_DATA_MULAI && $cells) {
                        $totalBaris = max($totalBaris, $nomorBaris);
                        $indeks = intdiv($nomorBaris - self::BARIS_DATA_MULAI, self::BARIS_PER_BATCH);
                        if ($indeks !== $indeksBuffer) {
                            $flush();
                            $indeksBuffer = $indeks;
                        }
                        $buffer[$nomorBaris] = $cells;
                    }
                }
            }
        }
        $flush();
        $r->close();

        return [$barisHeader, max($totalBaris, self::BARIS_DATA_MULAI - 1)];
    }

    private function cariEntriSheet(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($wb && $rels && preg_match('/<(?:\w+:)?sheet\b[^>]*\br:id="([^"]+)"/', $wb, $m)) {
            if (preg_match_all('/<Relationship\b[^>]*>/', $rels, $semua)) {
                foreach ($semua[0] as $tag) {
                    if (str_contains($tag, 'Id="'.$m[1].'"') && preg_match('/Target="([^"]+)"/', $tag, $t)) {
                        $target = ltrim($t[1], '/');

                        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function bacaSharedStrings(ZipArchive $zip, string $path): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $r = new XMLReader;
        if (! $r->open('zip://'.$path.'#xl/sharedStrings.xml')) {
            return [];
        }

        $hasil = [];
        $cur = '';
        $didalamSi = false;

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                if ($r->localName === 'si') {
                    $cur = '';
                    $didalamSi = true;
                    if ($r->isEmptyElement) {
                        $hasil[] = '';
                        $didalamSi = false;
                    }
                } elseif ($r->localName === 'rPh') {
                    $r->next(); // lewati teks fonetik
                } elseif ($r->localName === 't' && $didalamSi) {
                    $cur .= $r->readString();
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT && $r->localName === 'si') {
                $hasil[] = $cur;
                $didalamSi = false;
            }
        }
        $r->close();

        return $hasil;
    }

    // "AB12" -> 27 (0-based)
    private function indeksKolom(string $ref): int
    {
        $huruf = preg_replace('/[^A-Za-z]/', '', $ref);
        $n = 0;
        for ($i = 0, $len = strlen($huruf); $i < $len; $i++) {
            $n = $n * 26 + (ord(strtoupper($huruf[$i])) - 64);
        }

        return max($n - 1, 0);
    }

    // ---------- helper parsing (sama dengan PemrosesExcelAnomaliGabungan) ----------

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

    private function pecahDaftarAnomali(string $teks): array
    {
        $segmen = preg_split(
            '/,\s*(?=Anomali\s+(?:Keluarga|Usaha)\s+\d+\s*\()/i',
            trim($teks)
        );

        return array_values(array_filter(array_map('trim', $segmen)));
    }

    private function parseSegmenAnomali(string $segmen): ?array
    {
        if (! preg_match('/^Anomali\s+(Keluarga|Usaha)\s+(\d+)\s*\((.*)\)\s*$/is', trim($segmen), $cocok)) {
            return null;
        }

        return [strtolower($cocok[1]), (int) $cocok[2], trim($cocok[3])];
    }

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