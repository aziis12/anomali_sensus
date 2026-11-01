<?php

use App\Http\Controllers\BackupRestoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataAnomaliController;
use App\Http\Controllers\KelolaPegawaiController;
use App\Http\Controllers\KelolaPetugasController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\MonitoringTindakLanjutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatUnggahController;
use App\Http\Controllers\UnggahBertahapController;
use App\Http\Controllers\UnggahExcelController;
use App\Http\Controllers\UnggahReferensiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImporDataLamaController;


Route::get('/', function () {
    return redirect()->route('login');
});

// Trigger backup otomatis lewat URL, dipanggil layanan cron EKSTERNAL (mis. cron-job.org)
// -- dipakai kalau hosting cPanel gak punya menu "Cron Jobs" sendiri. SENGAJA di luar
// middleware 'auth' karena layanan cron eksternal gak bisa login, tapi tetap diproteksi
// pakai token rahasia (lihat BackupRestoreController::backupOtomatisViaHttp & .env).
Route::get('cron/backup-otomatis/{token}', [App\Http\Controllers\BackupRestoreController::class, 'backupOtomatisViaHttp'])
    ->name('backup.otomatis.http');

Route::middleware(['auth'])->group(function () {
    Route::middleware('peran:superadmin')->group(function () {

  //-----------------------  
Route::get('perbaiki-sinkron-sekali-pakai', function () {
    // Langkah 1: cari kelompok_baris_excel yang isinya CAMPURAN (sebagian baris
    // status_assignment='sudah', sebagian 'belum') -- ini kemungkinan besar efek
    // samping dari run sinkronisasi versi LAMA (per-baris, sebelum diperbaiki).
    // Cuma baris yang diupdate_oleh-nya NULL dan status_assignment='sudah' yang
    // di-reset balik ke 'belum' -- itu penanda baris ini diubah OTOMATIS oleh
    // sinkronisasi (bukan diisi manual oleh pegawai lewat form, yang selalu
    // ngisi diupdate_oleh dengan id pegawai). Jadi pekerjaan manual pegawai
    // TIDAK akan tersentuh oleh langkah reset ini.
    $kelompokCampuran = \App\Models\DataAnomali::where('status_di_fasih', 'aktif')
        ->get(['id', 'kelompok_baris_excel', 'status_assignment', 'diupdate_oleh'])
        ->groupBy('kelompok_baris_excel')
        ->filter(fn ($grup) => $grup->pluck('status_assignment')->unique()->count() > 1);
 
    $idBarisDireset = $kelompokCampuran
        ->flatMap(fn ($grup) => $grup->where('status_assignment', 'sudah')->whereNull('diupdate_oleh')->pluck('id'))
        ->all();
 
    \App\Models\DataAnomali::whereIn('id', $idBarisDireset)->update([
        'status_assignment' => 'belum',
        'tanggal_respon' => null,
        'diupdate_oleh' => null,
    ]);
 
    // Langkah 2: jalankan ulang sinkronisasi versi baru (per kelompok, bukan
    // per baris) -- sekarang kelompok yang tadi di-reset bisa dievaluasi ulang
    // dengan benar.
    $sinkronisasi = new \App\Services\SinkronisasiPenyelesaianAnomali;
    $jumlahDisinkronkan = $sinkronisasi->jalankan();
 
    return sprintf(
        'Selesai. %d kelompok campuran diperbaiki (%d baris di-reset ke "belum"), lalu %d kelompok berhasil disinkronkan jadi "sudah".',
        $kelompokCampuran->count(),
        count($idBarisDireset),
        $jumlahDisinkronkan
    );
});


//-------------------------

        // format LAMA: 2 file terpisah (usaha & keluarga)
        Route::post('unggah-excel', [UnggahExcelController::class, 'store'])->name('unggah.excel');

        // format BARU: 1 file gabungan (usaha & keluarga sudah jadi satu file dari pusat)
        Route::post('unggah-excel-gabungan', [UnggahExcelController::class, 'storeGabungan'])->name('unggah.excel.gabungan');

        // format BARU versi BERTAHAP: tiap request hanya memproses sebagian baris,
        // supaya tidak kena batas waktu/memori hosting (lihat UnggahBertahapController)
        Route::post('unggah-excel-gabungan/mulai', [UnggahBertahapController::class, 'mulai'])->name('unggah.excel.gabungan.mulai');
        Route::post('unggah-excel-gabungan/proses', [UnggahBertahapController::class, 'proses'])->name('unggah.excel.gabungan.proses');
        Route::post('unggah-excel-gabungan/selesai', [UnggahBertahapController::class, 'selesai'])->name('unggah.excel.gabungan.selesai');

        // Backup & Restore data (lihat App\Http\Controllers\BackupRestoreController)
        Route::get('backup', [BackupRestoreController::class, 'unduh'])->name('backup.unduh');
        Route::get('restore', [BackupRestoreController::class, 'formRestore'])->name('backup.restore.form');
        Route::post('restore', [BackupRestoreController::class, 'restore'])->name('backup.restore');
        Route::get('backup-otomatis', [BackupRestoreController::class, 'daftarOtomatis'])->name('backup.otomatis.daftar');
        Route::get('backup-otomatis/{namaFile}', [BackupRestoreController::class, 'unduhOtomatis'])->name('backup.otomatis.unduh');

        // Route::post('unggah-referensi', [UnggahReferensiController::class, 'store'])->name('unggah.referensi');
        Route::get('impor-data-lama', [ImporDataLamaController::class, 'create'])->name('impor-data-lama.create');
        Route::post('impor-data-lama', [ImporDataLamaController::class, 'store'])->name('impor-data-lama.store');
Route::get('/impor-data-lama/hasil', [ImporDataLamaController::class, 'hasil'])
    ->name('impor-data-lama.hasil');      
        Route::get('kelola-pegawai', [KelolaPegawaiController::class, 'index'])->name('kelola-pegawai.index');
        Route::post('kelola-pegawai', [KelolaPegawaiController::class, 'store'])->name('kelola-pegawai.store');
        Route::patch('kelola-pegawai/{user}/nonaktifkan', [KelolaPegawaiController::class, 'nonaktifkan'])->name('kelola-pegawai.nonaktifkan');
        Route::patch('kelola-pegawai/{user}/aktifkan', [KelolaPegawaiController::class, 'aktifkan'])->name('kelola-pegawai.aktifkan');

        Route::get('riwayat-unggah', [RiwayatUnggahController::class, 'index'])->name('riwayat-unggah.index');
        Route::get('kelola-petugas', [KelolaPetugasController::class, 'index'])->name('kelola-petugas.index');
        Route::get('riwayat-unggah/{riwayatUnggah}', [RiwayatUnggahController::class, 'show'])->name('riwayat-unggah.show');
        Route::delete('riwayat-unggah/{riwayatUnggah}', [RiwayatUnggahController::class, 'destroy'])->name('riwayat-unggah.destroy');
        Route::post('kelola-petugas', [KelolaPetugasController::class, 'store'])->name('kelola-petugas.store');
        Route::get('kelola-petugas/{user}/edit', [KelolaPetugasController::class, 'edit'])->name('kelola-petugas.edit');
        Route::put('kelola-petugas/{user}', [KelolaPetugasController::class, 'update'])->name('kelola-petugas.update');
        Route::patch('kelola-petugas/{user}/nonaktifkan', [KelolaPetugasController::class, 'nonaktifkan'])->name('kelola-petugas.nonaktifkan');
        Route::patch('kelola-petugas/{user}/aktifkan', [KelolaPetugasController::class, 'aktifkan'])->name('kelola-petugas.aktifkan');
        Route::delete('kelola-petugas/{user}', [KelolaPetugasController::class, 'destroy'])->name('kelola-petugas.destroy');

        Route::delete('kelola-pegawai/{user}', [KelolaPegawaiController::class, 'destroy'])->name('kelola-pegawai.destroy');

        // Rekap PML/PJ/PPL (Monitoring Tindak Lanjut): dulu 1 halaman gabungan, sekarang
        // dipisah jadi beberapa halaman -- rekap dikelompokkan per PJ, per PML, dan per PPL.
        // /monitoring-tindak-lanjut (tanpa suffix) di-redirect ke per-pj supaya
        // link/bookmark lama tetap jalan.
                Route::redirect('monitoring-tindak-lanjut', '/monitoring-tindak-lanjut/per-pj');
        Route::get('monitoring-tindak-lanjut/per-pj', [MonitoringTindakLanjutController::class, 'perPj'])->name('monitoring-tindak-lanjut.per-pj');
        Route::get('monitoring-tindak-lanjut/per-pj/pdf', [MonitoringTindakLanjutController::class, 'perPjPdf'])->name('monitoring-tindak-lanjut.per-pj.pdf');
        Route::get('monitoring-tindak-lanjut/per-pml', [MonitoringTindakLanjutController::class, 'perPml'])->name('monitoring-tindak-lanjut.per-pml');
        Route::get('monitoring-tindak-lanjut/per-pml/pdf', [MonitoringTindakLanjutController::class, 'perPmlPdf'])->name('monitoring-tindak-lanjut.per-pml.pdf');
        Route::get('monitoring-tindak-lanjut/per-ppl', [MonitoringTindakLanjutController::class, 'perPpl'])->name('monitoring-tindak-lanjut.per-ppl');
        Route::get('monitoring-tindak-lanjut/per-ppl/pdf', [MonitoringTindakLanjutController::class, 'perPplPdf'])->name('monitoring-tindak-lanjut.per-ppl.pdf');
        Route::get('monitoring-tindak-lanjut/grafik', [MonitoringTindakLanjutController::class, 'grafik'])->name('monitoring-tindak-lanjut.grafik');
        Route::post('monitoring-tindak-lanjut/unggah-pj', [MonitoringTindakLanjutController::class, 'unggahPj'])->name('monitoring-tindak-lanjut.unggah-pj');

        Route::get('monitoring/progress-petugas', [MonitoringController::class, 'progressPetugasView'])->name('monitoring.progress-petugas');
        Route::get('monitoring/progress-pegawai', [MonitoringController::class, 'progressPegawaiView'])->name('monitoring.progress-pegawai');
    });

    // catatan: route 'pilih-pml' (dan middleware 'pilih.pml') sudah tidak dipakai lagi.
    // petugas sekarang langsung masuk ke Data Anomali dan memilih "kategori PML" lewat
    // dropdown filter di halaman itu sendiri, bukan lewat halaman terpisah sebelum masuk.

    Route::middleware(['peran:pegawai,petugas'])->group(function () {
        Route::get('data-anomali', [DataAnomaliController::class, 'index'])->name('data-anomali.index');
        Route::get('data-anomali/{dataAnomali}/edit', [DataAnomaliController::class, 'edit'])->name('data-anomali.edit');

        // WAJIB didaftarkan SEBELUM route 'data-anomali/{dataAnomali}' (PUT) di bawahnya.
        // Kalau dibalik urutannya, Laravel akan coba mencocokkan "update-grup" sebagai
        // parameter {dataAnomali} (route model binding) duluan -> error 404 "model not found",
        // karena keduanya sama-sama method PUT ke path yang mirip (data-anomali/...).
        //
        // Update SEMUA baris DataAnomali dalam satu link_fasih sekaligus, dari 1 form
        // per card di tampilan index (bukan lagi 1 form per anomali).
        Route::put('data-anomali/update-grup', [DataAnomaliController::class, 'updateGrup'])->name('data-anomali.update-grup');

        Route::put('data-anomali/{dataAnomali}', [DataAnomaliController::class, 'update'])->name('data-anomali.update');
    });

    // ekspor dipisah dari grup di atas karena superadmin juga perlu akses
    // (ada link "Ekspor Data Anomali" di sidebar superadmin)
    Route::middleware(['peran:superadmin,pegawai,petugas'])->group(function () {
        Route::get('data-anomali/ekspor', [DataAnomaliController::class, 'ekspor'])->name('data-anomali.ekspor');
        Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    });

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__.'/auth.php';