<?php

namespace App\Console\Commands;

use App\Services\PembuatBackupAnomali;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Backup harian otomatis, disimpan di storage/app/backup_otomatis (di server itu
 * sendiri). Ini BUKAN pengganti backup ke tempat terpisah -- kalau server-nya
 * yang bermasalah (disk rusak, kehapus semua, dst), backup yang nyimpen di
 * server yang sama gak akan menolong. Idealnya folder ini di-sync juga ke luar
 * server (Google Drive, email, atau di-download manual berkala) lewat cPanel /
 * cron terpisah, atau kamu jelaskan di laporan TA sebagai pengembangan lanjutan.
 */
class BackupOtomatis extends Command
{
    protected $signature = 'anomali:backup-otomatis';

    protected $description = 'Buat backup data anomali otomatis dan simpan di storage (dipanggil scheduler harian).';

    public function handle(PembuatBackupAnomali $pembuat): int
    {
        $spreadsheet = $pembuat->buat();

        $namaFile = 'backup-otomatis-'.now()->format('Ymd-His').'.xlsx';
        $path = 'backup_otomatis/'.$namaFile;

        $tempPath = tempnam(sys_get_temp_dir(), 'backup');
        (new Xlsx($spreadsheet))->save($tempPath);

        Storage::disk('local')->put($path, file_get_contents($tempPath));
        unlink($tempPath);

        $this->info("Backup tersimpan: storage/app/{$path}");

        // hapus backup otomatis yang lebih tua dari 14 hari, biar disk server gak penuh
        $batasWaktu = now()->subDays(14)->timestamp;
        foreach (Storage::disk('local')->files('backup_otomatis') as $file) {
            if (Storage::disk('local')->lastModified($file) < $batasWaktu) {
                Storage::disk('local')->delete($file);
            }
        }

        return self::SUCCESS;
    }
}