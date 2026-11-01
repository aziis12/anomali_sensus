<?php

namespace App\Http\Controllers;

use App\Models\DataAnomali;
use App\Models\RiwayatUnggah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataAnomaliController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // petugas gak lagi wajib pilih PML dulu sebelum masuk. begitu dia pilih filter "pml" di halaman ini,
        // pilihan itu disimpan sebagai "PML yang lagi dia kerjakan" -> dipakai buat gerbang izin isi tanggapan
        // (lihat queryTerfilter/edit/update/updateGrup). kalau dia gak pilih apa-apa, dia tetap bisa lihat semua data,
        // tapi belum bisa isi tanggapan di baris manapun sampai dia pilih PML-nya.
        if ($user->isPetugas() && $request->has('pml')) {
            session(['nama_pml_dipilih' => $request->pml ?: null]);
        }

        $query = $this->queryTerfilter($request, $user);

        $jumlahPerHalaman = (int) $request->get('per_halaman', 25);
        $jumlahPerHalaman = in_array($jumlahPerHalaman, [25, 50, 100]) ? $jumlahPerHalaman : 25;

        // PENTING: dipaginate di level KELOMPOK_BARIS_EXCEL (1 kelompok_baris_excel = 1 baris
        // di file excel gabungan dari pusat), BUKAN di level baris DataAnomali (1 baris = 1 anomali),
        // dan BUKAN LAGI di level link_fasih.
        //
        // Sebelumnya paginate() jalan di level link_fasih. Itu cukup untuk kasus normal (1
        // link_fasih = 1 baris excel), TAPI ditemukan bahwa satu link_fasih kadang muncul di
        // DUA baris excel berbeda dari pusat (mis. baris usaha & baris keluarga dikirim
        // terpisah, walau sama-sama punya link_fasih & assignment_id yang identik). Kalau
        // dipaginate/grouping di level link_fasih, kedua baris excel itu digabung jadi 1 kartu
        // -> jumlah kartu di web (mis. 2411) jadi lebih sedikit dari jumlah baris excel asli
        // (mis. 3478), walau tidak ada data yang benar-benar hilang.
        //
        // Fix: kolom "kelompok_baris_excel" (diisi saat impor, lihat PemrosesExcelAnomaliGabungan)
        // membedakan setiap baris excel asal, walau link_fasih-nya sama. Dipaginate/grouping
        // berdasarkan itu, supaya 1 kartu = 1 baris excel, dan "menampilkan X dari Y" berarti
        // X dari Y baris excel, sama persis dengan file yang diunggah.
        //
        // PENTING: pakai groupBy('kelompok_baris_excel'), BUKAN distinct(). Keduanya keliatan
        // mirip buat ambil daftar kelompok unik, tapi paginate()->total() cuma akurat kalau
        // pakai groupBy() -- lihat penjelasan lengkap soal kenapa di komentar versi sebelumnya
        // (masalahnya ada di cara Laravel membuang select()+distinct() saat menghitung total()).
        $queryKelompok = (clone $query)
            ->selectRaw('kelompok_baris_excel, MIN(link_fasih) as link_fasih')
            ->groupBy('kelompok_baris_excel')
            ->orderBy('link_fasih')
            ->orderBy('kelompok_baris_excel');
        $data = $queryKelompok->paginate($jumlahPerHalaman)->withQueryString();

        $daftarKelompokHalamanIni = $data->pluck('kelompok_baris_excel')->all();

        $dataAnomaliHalamanIni = (clone $query)
            ->whereIn('kelompok_baris_excel', $daftarKelompokHalamanIni)
            ->orderBy('link_fasih')
            ->orderBy('kelompok_baris_excel')
            ->orderBy('idsubsls')
            ->get();

        $grup = $dataAnomaliHalamanIni->groupBy('kelompok_baris_excel');

        // Beberapa kelompok_baris_excel bisa berbagi link_fasih yang sama (lihat komentar di
        // atas soal kasus baris usaha & keluarga dikirim terpisah dari pusat walau
        // link_fasih-nya identik, mis. kasus "KASANAH" di baris 6 & 198). Di TAMPILAN,
        // kelompok-kelompok begini dibungkus jadi SATU box biar gampang dibandingin,
        // tapi tetap masing-masing kelompok_baris_excel punya baris data (form, status,
        // tanggapan) SENDIRI-SENDIRI di dalam box itu.
        //
        // PENTING bedanya sama fungsi gabungNamaEntitas()/gabungNilaiPerAnomali() di
        // ekspor(): di ekspor, semua baris dalam satu KELOMPOK_BARIS_EXCEL digabung jadi
        // satu SEL (karena itu memang cerminan satu baris excel). Di sini, yang dibungkus
        // adalah beberapa KELOMPOK_BARIS_EXCEL (masing-masing tetap 1 baris excel sendiri)
        // yang kebetulan berbagi link_fasih -- jadi nilainya TIDAK digabung, cuma
        // dikelompokkan secara visual.
        //
        // groupBy() di Collection menjaga urutan kemunculan pertama tiap key, dan karena
        // $grup sendiri sudah terurut oleh link_fasih (lihat orderBy di atas), kelompok-
        // kelompok dengan link_fasih sama otomatis jadi bersebelahan di sini juga.
        //
        // FIX (penting!): $grup di sini key-nya adalah kelompok_baris_excel (hasil groupBy
        // sebelumnya). Waktu di-groupBy() LAGI berdasarkan link_fasih, Collection::groupBy()
        // secara default MEMBUANG key asli tiap item dan menggantinya dengan index numerik
        // ulang (0, 1, 2, ...) -- kecuali eksplisit diminta preserveKeys: true. Tanpa ini,
        // key kelompok_baris_excel yang benar hilang, sehingga di Blade
        // (`@foreach ($kelompokKelompok as $kelompokBarisExcel => $daftarAnomali)`)
        // $kelompokBarisExcel yang ke-render cuma angka 0/1/2/dst, BUKAN nilai
        // kelompok_baris_excel yang asli. Nilai palsu itu ikut ditaruh di hidden input
        // form ("kelompok_baris_excel"), jadi waktu form disubmit ke updateGrup(), query
        // where('kelompok_baris_excel', ...) gak nemu baris manapun yang cocok di database
        // -> 0 baris ke-update -> tombol Simpan keliatan jalan tapi gak benar-benar
        // menyimpan apa-apa (petugas malah kena 403 karena $jumlahDiperbarui === 0).
        $grupBerdasarkanLink = $grup->groupBy(
            fn ($daftarAnomaliKelompok) => $daftarAnomaliKelompok->first()->link_fasih,
            preserveKeys: true
        );

        $daftarPml = [];
        $daftarPpl = [];

        // dropdown filter PML/PPL sekarang tampil buat pegawai maupun petugas.
        // buat petugas, dropdown "pml" ini juga dobel fungsi sebagai penanda "data siapa yang lagi dia kerjakan".
        if ($user->isPegawai() || $user->isPetugas()) {
            $daftarPml = DataAnomali::whereNotNull('pml')->distinct()->orderBy('pml')->pluck('pml');
            $daftarPpl = DataAnomali::whereNotNull('ppl')->distinct()->orderBy('ppl')->pluck('ppl');
        }

        $daftarUnggahan = RiwayatUnggah::orderByDesc('created_at')->get();
        $daftarKecamatan = DataAnomali::whereNotNull('nama_kecamatan')->distinct()->orderBy('nama_kecamatan')->pluck('nama_kecamatan');
        $daftarDesa = DataAnomali::whereNotNull('nama_desa')->distinct()->orderBy('nama_desa')->pluck('nama_desa');
        $daftarTindakLanjut = DataAnomali::whereNotNull('tindak_lanjut_fasih')
            ->where('tindak_lanjut_fasih', 'not like', '%belum%')
            ->distinct()
            ->orderBy('tindak_lanjut_fasih')
            ->pluck('tindak_lanjut_fasih');

        $daftarKategoriAnomali = $this->queryTerfilter($request, $user, kecualikan: ['kategori_anomali'])
            ->whereNotNull('nama_anomali_fasih')
            ->distinct()
            ->orderBy('nama_anomali_fasih')
            ->pluck('nama_anomali_fasih');

        return view('data-anomali.index', [
            'data' => $data,
            'grup' => $grupBerdasarkanLink,
            'user' => $user,
            'kataCari' => $request->get('cari', ''),
            'pmlAktif' => $request->get('pml', ''),
            'pplAktif' => $request->get('ppl', ''),
            'unggahanAktif' => $request->get('unggahan', ''),
            'kecamatanAktif' => $request->get('kecamatan', ''),
            'desaAktif' => $request->get('desa', ''),
            'tindakLanjutAktif' => $request->get('tindak_lanjut', 'belum'),
            'kategoriAnomaliAktif' => $request->get('kategori_anomali', ''),
            'penyelesaianAktif' => $request->get('penyelesaian', ''),

            // dikirim ke view supaya tombol/aksi isi tanggapan bisa dikunci per baris
            // kalau baris itu bukan PML yang lagi dipilih petugas
            'pmlDikerjakanPetugas' => $user->isPetugas() ? session('nama_pml_dipilih') : null,

            'daftarPml' => $daftarPml,
            'daftarPpl' => $daftarPpl,
            'daftarUnggahan' => $daftarUnggahan,
            'daftarKecamatan' => $daftarKecamatan,
            'daftarDesa' => $daftarDesa,
            'daftarTindakLanjut' => $daftarTindakLanjut,
            'daftarKategoriAnomali' => $daftarKategoriAnomali,
        ]);
    }

    public function ekspor(Request $request): StreamedResponse
{
    $user = $request->user();

    // ekspor defaultnya nampilin SEMUA data, beda sama tampilan index() yang defaultnya
    // cuma nampilin yang "belum ditindaklanjuti". kalau user memang lagi filter tindak_lanjut
    // secara eksplisit dari halaman (ikut kebawa lewat query string pas klik ekspor), itu tetap dihormati.
    if (! $request->filled('tindak_lanjut')) {
        $request->merge(['tindak_lanjut' => 'semua']);
    }

    $query = $this->queryTerfilter($request, $user);

    // orderBy('id') di akhir PENTING: menjaga urutan anomali di dalam satu kelompok_baris_excel
    // sesuai urutan aslinya waktu di-parse (lihat PemrosesExcelAnomaliGabungan::prosesFileGabungan,
    // segmen anomali dibuat berurutan sesuai urutan di "Daftar Anomali" sumber), supaya pas
    // digabung lagi jadi satu sel, urutannya balik sama persis kayak file pusat.
    $semua = $query->orderBy('link_fasih')->orderBy('kelompok_baris_excel')->orderBy('id')->get();

    // 1 baris hasil ekspor = 1 kelompok_baris_excel = 1 baris di file excel pusat asli,
    // sama seperti cara tampilan index() mengelompokkan kartu (lihat komentar di index()).
    // Sebelumnya ekspor jalan per baris DataAnomali (1 baris = 1 anomali), jadi kalau satu
    // baris excel pusat berisi beberapa anomali (usaha+keluarga, atau beberapa anomali
    // kategori yang sama), hasil ekspor jadi punya lebih banyak baris daripada file aslinya.
    $kelompokSemua = $semua->groupBy('kelompok_baris_excel');

    // urutan tampilan kolom spesifik di excel, biar rapi dan gampang dibaca
    // kalau ada kolom baru yang belum terdaftar di sini, tetap ikut tampil, cuma taruh di posisi paling belakang
    $urutanKolomSpesifik = [
        'anomali 1 dtsen penjelasan', 'anomali 3 dtsen penjelasan', 'anomali 4 dtsen penjelasan',
        'anomali 5 dtsen penjelasan', 'anomali 6 dtsen penjelasan', 'daya terpasang value',
        'disabilitas', 'jml ac', 'jml art', 'jml kk pasangan', 'jml kulkas', 'jml laptop',
        'jml meteran', 'jml status anomali', 'jumlah ak', 'jumlah art bekerja', 'listrik sebulan',
        'luas lantai', 'pengeluaran sebulan', 'selisih', 'sumber penerangan value', 'total pendapatan',
        'anomali 1 penjelasan', 'anomali 2 penjelasan', 'anomali 4 penjelasan', 'anomali 7 penjelasan',
        'anomali 8 penjelasan', 'badan usaha value', 'internet', 'kbli akhir', 'kbli prelist',
        'lap keuangan', 'non publik', 'nonpublik didirikan', 'produk sendiri value', 'publik',
        'publik didirikan', 'rasio', 'rasio bulan', 'rasio tahun', 'skala unit usaha', 'total pengeluaran',
    ];

    $kolomSpesifikDipakai = [];
    foreach ($semua as $satu) {
        foreach (($satu->data_spesifik ?? []) as $kunci => $nilai) {
            if (! in_array($kunci, $kolomSpesifikDipakai)) {
                $kolomSpesifikDipakai[] = $kunci;
            }
        }
    }
    usort($kolomSpesifikDipakai, function ($a, $b) use ($urutanKolomSpesifik) {
        $posA = array_search($a, $urutanKolomSpesifik);
        $posB = array_search($b, $urutanKolomSpesifik);
        $posA = $posA === false ? 999 : $posA;
        $posB = $posB === false ? 999 : $posB;

        return $posA <=> $posB;
    });

    // "No" ditaruh terpisah karena isinya nomor urut baris, bukan field dari data
    $judulKolomTetap = [
        'No', 'Nama Usaha / Kepala Keluarga', 'Kode Prov', 'Nama Provinsi', 'Kode Kab/Kota', 'Nama Kab/Kota',
        'Kode Kec', 'Nama Kecamatan', 'Kode Desa', 'Nama Desa/Kel', 'Kode SLS', 'Sub SLS',
        'PML', 'PPL', 'Assignment ID', 'Daftar Anomali', 'Tindak Lanjut', 'ID Petugas', 'Email Petugas', 'Link Fasih',
    ];

    $judulKolomSpesifik = array_map('ucwords', $kolomSpesifikDipakai);

    $judulKolomAkhir = [
        'Status Assignment Dalam Fasih', 'Perlu Konfirm Petugas',
        'Penyelesaian Anomali', 'Tanggapan dari Petugas', 'Respon Tanggal',
    ];

    $judulKolom = array_merge($judulKolomTetap, $judulKolomSpesifik, $judulKolomAkhir);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Anomali');

    $sheet->fromArray($judulKolom, null, 'A1');

    $kolomTerakhir = Coordinate::stringFromColumnIndex(count($judulKolom));

    $sheet->getStyle('A1:'.$kolomTerakhir.'1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BD5D34']],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(24);

    $linkSebelumnya = null;
    $warnaSorot = false;
    $baris = 2;
    $nomorUrut = 1;

    foreach ($kelompokSemua as $satuKelompok) {
        $wakil = $satuKelompok->first(); // field yang sama di semua baris kelompok ini (lokasi, assignment, dll)

        if ($wakil->link_fasih !== $linkSebelumnya) {
            $warnaSorot = ! $warnaSorot;
            $linkSebelumnya = $wakil->link_fasih;
        }

        // gabungkan data_spesifik semua anomali dalam kelompok ini. Aman di-array_merge polos:
        // tiap baris cuma bawa kolom "anomali N ..." miliknya sendiri (lihat
        // filterDataSpesifikUntukSegmen), jadi gak ada key bentrok antar baris dalam satu kelompok.
        $dataSpesifikGabungan = [];
        foreach ($satuKelompok as $satu) {
            $dataSpesifikGabungan = array_merge($dataSpesifikGabungan, $satu->data_spesifik ?? []);
        }
        $nilaiSpesifik = array_map(fn ($kunci) => $dataSpesifikGabungan[$kunci] ?? null, $kolomSpesifikDipakai);

        $barisData = array_merge([
            $nomorUrut,
            $this->gabungNamaEntitas($satuKelompok),
            $wakil->kode_provinsi,
            $wakil->nama_provinsi,
            $wakil->kode_kabkota,
            $wakil->nama_kabkota,
            $wakil->kode_kecamatan,
            $wakil->nama_kecamatan,
            $wakil->kode_desa,
            $wakil->nama_desa,
            $wakil->kode_sls,
            $wakil->sub_sls,
            $wakil->pml,
            $wakil->ppl,
            $wakil->assignment_id,
            $satuKelompok->pluck('nama_anomali_fasih')->implode(', '),
            ucfirst(str_replace('_', ' ', $wakil->tindak_lanjut_fasih ?: 'belum')),
            $wakil->id_petugas_fasih,
            $wakil->email_petugas_fasih,
            $wakil->link_fasih,
        ], $nilaiSpesifik, [
            $this->gabungNilaiPerAnomali($satuKelompok, fn ($r) => $r->status_assignment_fasih),
            $this->gabungNilaiPerAnomali($satuKelompok, fn ($r) => $r->perlu_konfirm_petugas),
            $this->gabungNilaiPerAnomali($satuKelompok, fn ($r) => ucfirst($r->status_assignment ?: 'belum')),
            $this->gabungNilaiPerAnomali($satuKelompok, fn ($r) => $r->tanggapan_petugas),
            $this->gabungNilaiPerAnomali($satuKelompok, fn ($r) => $r->tanggal_respon?->format('d-m-Y')),
        ]);

        $sheet->fromArray($barisData, null, "A{$baris}");

        if ($warnaSorot) {
            $sheet->getStyle('A'.$baris.':'.$kolomTerakhir.$baris)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3E3D3']],
            ]);
        }

        $baris++;
        $nomorUrut++;
    }

    $barisTerakhir = $baris - 1;

    $sheet->getStyle('A1:'.$kolomTerakhir.$barisTerakhir)->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E9DCC9']],
        ],
    ]);

    // lebar kolom dibatasi (min 10, maks 30 karakter) supaya kolom dengan teks panjang
    // (misal tanggapan petugas) gak melebar tak terkendali dan bikin file susah dibaca
    $lebarMinimal = 10;
    $lebarMaksimal = 30;

    foreach (range(1, count($judulKolom)) as $indeksKolom) {
        $kolomHuruf = Coordinate::stringFromColumnIndex($indeksKolom);

        $lebarKonten = mb_strlen((string) ($judulKolom[$indeksKolom - 1] ?? ''));
        foreach ($sheet->getColumnIterator($kolomHuruf, $kolomHuruf) as $kolomIter) {
            foreach ($kolomIter->getCellIterator() as $cell) {
                $lebarKonten = max($lebarKonten, mb_strlen((string) $cell->getValue()));
            }
        }

        $lebarFinal = min(max($lebarKonten + 2, $lebarMinimal), $lebarMaksimal);
        $sheet->getColumnDimension($kolomHuruf)->setWidth($lebarFinal);
    }

    // teks yang kepotong karena dibatasi lebar kolomnya tetap kebaca utuh,
    // turun ke baris baru di dalam sel yang sama (bukan melebarkan kolom)
    $sheet->getStyle('A2:'.$kolomTerakhir.$barisTerakhir)->getAlignment()->setWrapText(true);

    $sheet->freezePane('A2');

    $namaFile = 'data-anomali-'.now()->format('Ymd-His').'.xlsx';
    $writer = new Xlsx($spreadsheet);

    return new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
        'Cache-Control' => 'max-age=0',
    ]);
}

/**
 * Rekonstruksi kolom nama entitas balik ke format sumber pusat, kebalikan dari
 * PemrosesExcelAnomaliGabungan::pecahNamaEntitas():
 * - Kalau kelompok ini punya lebih dari satu kategori (usaha DAN keluarga) -> "[Usaha: X] [Keluarga: Y]"
 * - Kalau cuma satu kategori -> nama polos tanpa bracket
 */
private function gabungNamaEntitas(\Illuminate\Support\Collection $kelompok): string
{
    $namaPerKategori = [];
    foreach ($kelompok as $satu) {
        $namaPerKategori[$satu->kategori] = $satu->nama_entitas;
    }

    if (count($namaPerKategori) === 1) {
        return reset($namaPerKategori);
    }

    $bagian = [];
    foreach (['usaha', 'keluarga'] as $kategori) {
        if (isset($namaPerKategori[$kategori])) {
            $bagian[] = '['.ucfirst($kategori).': '.$namaPerKategori[$kategori].']';
        }
    }

    return implode(' ', $bagian);
}

/**
 * Gabungkan nilai sebuah field yang sifatnya per-anomali (status, tanggapan, dst) dari
 * semua baris dalam satu kelompok_baris_excel jadi satu sel. Kalau kelompok cuma punya
 * satu anomali, nilainya polos (gak dikasih label) biar gak berisik. Kalau lebih dari
 * satu, tiap nilai dikasih label "Kategori Nomor: nilai" biar gak ada info yang hilang
 * saat beberapa anomali dipaksa masuk satu baris excel, dan gabungannya persis
 * merefleksikan apa yang tampil di kartu masing-masing anomali di web.
 */
private function gabungNilaiPerAnomali(\Illuminate\Support\Collection $kelompok, callable $ambilNilai): ?string
{
    $satuAnomaliSaja = $kelompok->count() === 1;
    $bagian = [];

    foreach ($kelompok as $satu) {
        $nilai = $ambilNilai($satu);

        if ($nilai === null || $nilai === '') {
            continue;
        }

        $bagian[] = $satuAnomaliSaja
            ? $nilai
            : ucfirst($satu->kategori).' '.$satu->nomor_anomali.': '.$nilai;
    }

    return empty($bagian) ? null : implode('; ', $bagian);
}

    /**
     * @param  array<int, string>  $kecualikan  daftar nama filter yang mau dilewati (gak ikut dipakai buat where)
     */
    private function queryTerfilter(Request $request, $user, array $kecualikan = [])
    {
        $query = DataAnomali::query()->where('status_di_fasih', 'aktif');

        // petugas sekarang gak lagi otomatis dibatasi ke satu PML di sini.
        // dia lihat semua data kayak pegawai; pembatasan cuma berlaku pas dia mau isi tanggapan (lihat edit/update/updateGrup),
        // dan penyaringan tampilan tetap lewat filter 'pml' biasa di bawah (sama kayak yang dipakai pegawai).

        if (! in_array('tindak_lanjut', $kecualikan)) {
            $tindakLanjut = $request->get('tindak_lanjut', 'belum');
            if ($tindakLanjut === 'belum') {
                $query->where('tindak_lanjut_fasih', 'like', '%belum%');
            } elseif ($tindakLanjut !== 'semua') {
                $query->where('tindak_lanjut_fasih', $tindakLanjut);
            }
        }

        if (! in_array('cari', $kecualikan) && $request->filled('cari')) {
            $kata = $request->get('cari');
            $query->where(function ($q) use ($kata) {
                $q->where('nama_entitas', 'like', "%{$kata}%")
                    ->orWhere('idsubsls', 'like', "%{$kata}%")
                    ->orWhere('assignment_id', 'like', "%{$kata}%");
            });
        }

        if (! in_array('pml', $kecualikan) && $request->filled('pml')) {
            $query->where('pml', $request->pml);
        }

        if (! in_array('ppl', $kecualikan) && $request->filled('ppl')) {
            $query->where('ppl', $request->ppl);
        }

        if (! in_array('unggahan', $kecualikan) && $request->filled('unggahan')) {
            $query->where('riwayat_unggah_id', $request->unggahan);
        }

        if (! in_array('kecamatan', $kecualikan) && $request->filled('kecamatan')) {
            $query->where('nama_kecamatan', $request->kecamatan);
        }

        if (! in_array('desa', $kecualikan) && $request->filled('desa')) {
            $query->where('nama_desa', $request->desa);
        }

        if (! in_array('kategori_anomali', $kecualikan) && $request->filled('kategori_anomali')) {
            $query->where('nama_anomali_fasih', $request->kategori_anomali);
        }

        if (! in_array('penyelesaian', $kecualikan) && $request->filled('penyelesaian') && in_array($request->penyelesaian, ['sudah', 'belum'])) {
            $query->where('status_assignment', $request->penyelesaian);
        }

        return $query;
    }

    public function edit(DataAnomali $dataAnomali, Request $request): View
    {
        $user = $request->user();

        if ($user->isPetugas() && $dataAnomali->pml !== session('nama_pml_dipilih')) {
            abort(403, 'Data ini bukan tanggung jawab kamu. Pilih dulu kategori PML yang sesuai di halaman Data Anomali.');
        }

        return view('data-anomali.edit', [
            'dataAnomali' => $dataAnomali,
            'user' => $user,
        ]);
    }

    /**
     * Dipertahankan untuk kompatibilitas kalau masih ada tempat lain (mis. halaman edit
     * per-baris) yang update satu DataAnomali secara individual lewat route-model-binding.
     * Untuk tampilan index (1 form per kelompok_baris_excel), yang dipakai adalah updateGrup() di bawah.
     */
    public function update(DataAnomali $dataAnomali, Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->isPetugas()) {
            // gerbang yang sama kayak di edit(), dipasang juga di sini supaya request PUT langsung
            // ke baris manapun tetap gak bisa nembus data yang bukan PML yang lagi dia pilih
            if ($dataAnomali->pml !== session('nama_pml_dipilih')) {
                abort(403, 'Data ini bukan tanggung jawab kamu. Pilih dulu kategori PML yang sesuai di halaman Data Anomali.');
            }

            $data = $request->validate([
                'tanggapan_petugas' => 'nullable|string',
            ]);

            $dataAnomali->update($data);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok']);
            }

            return back()->with('status', 'Tanggapan berhasil disimpan.');
        }

        $data = $request->validate([
            'status_assignment_fasih' => 'nullable|string',
            'perlu_konfirm_petugas' => 'nullable|string',
            'status_assignment' => 'required|in:sudah,belum',
        ]);

        $data['tanggal_respon'] = $data['status_assignment'] === 'sudah' ? now() : null;
        $data['diupdate_oleh'] = $user->id; // catat pegawai yang memproses baris ini, buat monitoring kinerja

        $dataAnomali->update($data);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('status', 'Data berhasil disimpan.');
    }

    /**
     * Update SEMUA baris DataAnomali dalam satu KELOMPOK_BARIS_EXCEL sekaligus, dari 1 form
     * di tampilan index (bukan lagi 1 form per anomali, dan bukan lagi 1 form per link_fasih).
     *
     * Kenapa kelompok_baris_excel, bukan link_fasih: 1 link_fasih bisa berasal dari 2 baris
     * excel berbeda (mis. kasus "KASANAH" di baris 6 & 198 -- link_fasih sama, isi anomali
     * beda). Supaya tampilan web = jumlah baris excel persis, tiap baris excel jadi 1 kartu
     * dengan tanggapan sendiri-sendiri, walau tetap berbagi link_fasih yang sama.
     *
     * Data di database tetap granular per baris (tidak diubah strukturnya) — ini
     * cuma mengubah CARA mengisinya jadi satu kali per kelompok_baris_excel.
     */
    public function updateGrup(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'kelompok_baris_excel' => 'required|string',
        ]);

        $query = DataAnomali::where('kelompok_baris_excel', $request->input('kelompok_baris_excel'))
            ->where('status_di_fasih', 'aktif');

        if ($user->isPetugas()) {
            // gerbang yang sama kayak update() lama: petugas cuma boleh isi tanggapan
            // untuk grup yang PML-nya sama dengan PML yang lagi dia pilih di filter.
            // Query di-scope ke pml itu juga, supaya kalau (secara tidak wajar) satu
            // kelompok_baris_excel punya baris dengan pml campuran, cuma baris pml-nya cocok yang ke-update.
            if (! session('nama_pml_dipilih')) {
                abort(403, 'Pilih dulu kategori PML yang sesuai di halaman Data Anomali.');
            }

            $query->where('pml', session('nama_pml_dipilih'));

            $data = $request->validate([
                'tanggapan_petugas' => 'nullable|string',
            ]);

            $jumlahDiperbarui = $query->update($data);

            if ($jumlahDiperbarui === 0) {
                abort(403, 'Data ini bukan tanggung jawab kamu. Pilih dulu kategori PML yang sesuai di halaman Data Anomali.');
            }

            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok', 'diperbarui' => $jumlahDiperbarui]);
            }

            return back()->with('status', "Tanggapan berhasil disimpan untuk {$jumlahDiperbarui} anomali.");
        }

        $data = $request->validate([
            'status_assignment_fasih' => 'nullable|string',
            'perlu_konfirm_petugas' => 'nullable|string',
            'status_assignment' => 'required|in:sudah,belum',
        ]);

        $data['tanggal_respon'] = $data['status_assignment'] === 'sudah' ? now() : null;
        $data['diupdate_oleh'] = $user->id;

        $jumlahDiperbarui = $query->update($data);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'diperbarui' => $jumlahDiperbarui]);
        }

        return back()->with('status', "Data berhasil disimpan untuk {$jumlahDiperbarui} anomali.");
    }
}