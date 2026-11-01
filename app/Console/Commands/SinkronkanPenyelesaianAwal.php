<?php

namespace App\Console\Commands;

use App\Services\SinkronisasiPenyelesaianAnomali;
use Illuminate\Console\Command;

/**
 * Command manual (tapi aman dijalankan berkali-kali / idempotent) untuk
 * menyinkronkan kolom status_assignment ("Penyelesaian Anomali" di web ini) dengan
 * histori tindak_lanjut_fasih yang datang dari Fasih.
 *
 * CATATAN (update): sejak SinkronisasiPenyelesaianAnomali dipanggil otomatis di
 * akhir PemrosesExcelAnomaliGabungan::proses(), command ini SEHARUSNYA jarang
 * perlu dijalankan manual lagi -- karena tiap upload data baru sudah otomatis
 * disinkronkan. Command ini tetap dipertahankan sebagai jalur manual/dry-run,
 * mis. buat menyisir baris lama yang entah kenapa belum ke-cover oleh jalur
 * otomatis (misal data yang diimpor sebelum fitur auto-sync ini ada).
 *
 * Logic sinkronisasi sendiri sekarang ada di SinkronisasiPenyelesaianAnomali,
 * dipakai bareng oleh command ini dan oleh proses upload otomatis, supaya
 * perilakunya gak bisa beda antara jalur manual dan jalur otomatis.
 */
class SinkronkanPenyelesaianAwal extends Command
{
    protected $signature = 'anomali:sinkronkan-penyelesaian-awal {--dry-run : Cuma tampilkan berapa baris yang akan diubah, tanpa benar-benar mengubah} {--force : Lewati konfirmasi manual, langsung eksekusi (dipakai kalau dipanggil non-interaktif, misal lewat cron atau route)}';

    protected $description = 'Set status_assignment=sudah untuk baris DataAnomali yang tindak_lanjut_fasih-nya sudah menunjukkan pernah ditindaklanjuti, tapi status_assignment masih default (belum)';

    public function handle(SinkronisasiPenyelesaianAnomali $sinkronisasi): int
    {
        $jumlah = $sinkronisasi->hitungYangPerluDisinkronkan();

        if ($jumlah === 0) {
            $this->info('Tidak ada baris yang perlu disinkronkan. Semua sudah sesuai.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[Dry-run] {$jumlah} baris AKAN di-set status_assignment='sudah' kalau dijalankan tanpa --dry-run.");

            // contoh beberapa baris biar bisa dicek sekilas isinya wajar
            $sinkronisasi->contohBarisYangPerluDisinkronkan()
                ->each(fn ($baris) => $this->line(" - [{$baris->id}] {$baris->nama_entitas} | {$baris->nama_anomali_fasih} | tindak_lanjut_fasih: {$baris->tindak_lanjut_fasih}"));

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Akan mengubah {$jumlah} baris jadi status_assignment='sudah' dengan tanggal_respon=sekarang. Lanjutkan?", true)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $diperbarui = $sinkronisasi->jalankan();

        $this->info("Selesai. {$diperbarui} baris berhasil disinkronkan jadi status_assignment='sudah'.");

        return self::SUCCESS;
    }
}