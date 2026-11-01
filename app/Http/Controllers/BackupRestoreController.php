<?php

namespace App\Http\Controllers;

use App\Services\PembuatBackupAnomali;
use App\Services\RestoreBackupAnomali;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupRestoreController extends Controller
{
    public function __construct(
        private PembuatBackupAnomali $pembuat,
        private RestoreBackupAnomali $restoreService,
    ) {
    }

    public function unduh(): StreamedResponse
    {
        $spreadsheet = $this->pembuat->buat();
        $writer = new Xlsx($spreadsheet);

        $namaFile = 'backup-data-anomali-'.now()->format('Ymd-His').'.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function formRestore(): View
    {
        return view('backup-restore.restore');
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'file_backup' => 'required|file|mimes:xlsx',
            'konfirmasi' => 'required|in:PULIHKAN',
        ], [
            'konfirmasi.in' => 'Ketik persis "PULIHKAN" (huruf besar semua) untuk konfirmasi.',
        ]);

        $path = $request->file('file_backup')->store('backup_sementara');

        try {
            $hasil = $this->restoreService->proses(Storage::path($path));
        } catch (\Exception $e) {
            Storage::delete($path);

            return back()->withErrors(['file_backup' => $e->getMessage()]);
        }

        Storage::delete($path);

        return back()->with('status', sprintf(
            'Restore selesai. Dibuat baru: %d, diperbarui: %d, dilewati (baris rusak/kosong): %d.',
            $hasil['dibuat'], $hasil['diperbarui'], $hasil['dilewati']
        ));
    }

    /**
     * Alternatif buat "cron" kalau hosting cPanel gak punya menu Cron Jobs (atau gak
     * punya Terminal/SSH sama sekali). Endpoint ini SENGAJA ditaruh di LUAR middleware
     * 'auth' (lihat routes/web.php) supaya bisa dipanggil oleh layanan cron eksternal
     * (mis. cron-job.org) yang cuma bisa hit URL biasa, bukan login dulu.
     *
     * Diproteksi pakai token rahasia dari .env (BACKUP_CRON_TOKEN) yang dicocokkan ke
     * parameter URL -- BUKAN otentikasi user biasa. Tanpa token yang cocok, request
     * ditolak 403. Token ini WAJIB diisi acak & panjang di .env, dan URL lengkapnya
     * (termasuk token) JANGAN disebar/ditaruh di tempat publik.
     */
    public function backupOtomatisViaHttp(Request $request, string $token): JsonResponse
    {
        $tokenRahasia = config('services.backup_cron.token');

        if (! $tokenRahasia || ! hash_equals($tokenRahasia, $token)) {
            abort(403, 'Token tidak valid.');
        }

        $spreadsheet = $this->pembuat->buat();

        $namaFile = 'backup-otomatis-'.now()->format('Ymd-His').'.xlsx';
        $path = 'backup_otomatis/'.$namaFile;

        $tempPath = tempnam(sys_get_temp_dir(), 'backup');
        (new Xlsx($spreadsheet))->save($tempPath);
        Storage::disk('local')->put($path, file_get_contents($tempPath));
        unlink($tempPath);

        // hapus backup otomatis yang lebih tua dari 14 hari, biar disk server gak penuh
        $batasWaktu = now()->subDays(14)->timestamp;
        foreach (Storage::disk('local')->files('backup_otomatis') as $file) {
            if (Storage::disk('local')->lastModified($file) < $batasWaktu) {
                Storage::disk('local')->delete($file);
            }
        }

        return response()->json(['status' => 'ok', 'file' => $namaFile]);
    }

    /**
     * Daftar file backup otomatis yang tersimpan di server (storage/app/backup_otomatis),
     * supaya superadmin bisa lihat & download langsung dari web ini tanpa perlu buka
     * File Manager cPanel manual.
     */
    public function daftarOtomatis(): View
    {
        $daftarFile = collect(Storage::disk('local')->files('backup_otomatis'))
            ->sortByDesc(fn ($path) => Storage::disk('local')->lastModified($path))
            ->map(fn ($path) => [
                'nama' => basename($path),
                'ukuran_kb' => round(Storage::disk('local')->size($path) / 1024, 1),
                'dibuat' => \Illuminate\Support\Carbon::createFromTimestamp(Storage::disk('local')->lastModified($path)),
            ])
            ->values();

        return view('backup-restore.otomatis', ['daftarFile' => $daftarFile]);
    }

    /**
     * Download satu file backup otomatis tertentu. basename() WAJIB dipakai untuk
     * membersihkan input nama file dari path traversal (mis. "../../.env") --
     * tanpa ini, orang bisa mencoba mengakses file lain di luar folder backup_otomatis
     * lewat parameter URL.
     */
    public function unduhOtomatis(string $namaFile): \Symfony\Component\HttpFoundation\Response
    {
        $namaFileAman = basename($namaFile);
        $path = 'backup_otomatis/'.$namaFileAman;

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return Storage::disk('local')->download($path);
    }
}