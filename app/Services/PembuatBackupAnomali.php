<?php

namespace App\Services;

use App\Models\DataAnomali;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Bikin spreadsheet backup LENGKAP data_anomali -- beda dengan ekspor() di
 * DataAnomaliController (yang isinya untuk DIBACA manusia, digabung per
 * kelompok_baris_excel, dan TIDAK menyertakan kolom kerja seperti
 * tanggapan_petugas/status_assignment secara mentah).
 *
 * Backup ini 1 baris = 1 baris DataAnomali (granular, apa adanya persis
 * seperti di database), dan menyertakan SEMUA kolom termasuk kolom hasil
 * kerja petugas & pegawai. Tujuannya supaya kalau di-restore, semua kerjaan
 * yang sudah diisi orang bisa balik utuh, bukan cuma data mentah dari pusat.
 */
class PembuatBackupAnomali
{
    /**
     * Urutan kolom ini WAJIB SAMA PERSIS dengan yang dibaca RestoreBackupAnomali.
     * "id" WAJIB di posisi pertama -- itu kunci utama proses restore untuk
     * menentukan UPDATE (id ketemu di database) atau BUAT BARU (id kosong/tak ketemu).
     */
    public const KOLOM_BACKUP = [
        'id', 'assignment_id', 'kategori', 'kategori_anomali_fasih', 'idsubsls',
        'kode_provinsi', 'nama_provinsi', 'kode_kabkota', 'nama_kabkota',
        'kode_kecamatan', 'nama_kecamatan', 'kode_desa', 'nama_desa',
        'kode_sls', 'sub_sls', 'nama_entitas', 'link_fasih', 'kelompok_baris_excel',
        'nama_anomali_fasih', 'urutan_duplikat', 'nomor_anomali', 'daftar_nomor_anomali',
        'deskripsi_anomali', 'status_dari_fasih', 'tindak_lanjut_fasih',
        'email_petugas_fasih', 'id_petugas_fasih', 'pml', 'ppl', 'nama_sls',
        'perlu_konfirm_petugas', 'tanggapan_petugas', 'status_assignment',
        'status_assignment_fasih', 'penyelesaian_anomali', 'tanggal_respon',
        'status_di_fasih', 'data_spesifik', 'riwayat_unggah_id', 'diupdate_oleh',
    ];

    public function buat(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Backup Data Anomali');

        $sheet->fromArray(self::KOLOM_BACKUP, null, 'A1');
        $kolomTerakhir = Coordinate::stringFromColumnIndex(count(self::KOLOM_BACKUP));

        $sheet->getStyle('A1:'.$kolomTerakhir.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D6A4F']],
        ]);

        $baris = 2;

        // chunk() supaya aman buat data yang jumlahnya besar, gak nge-load
        // semua baris ke memori PHP sekaligus
        DataAnomali::orderBy('id')->chunk(500, function ($kelompokBaris) use ($sheet, &$baris) {
            foreach ($kelompokBaris as $satu) {
                $nilaiBaris = array_map(function ($kolom) use ($satu) {
                    $nilai = $satu->getAttribute($kolom);

                    // kolom array (data_spesifik, daftar_nomor_anomali) disimpan sebagai
                    // teks JSON di dalam sel, supaya bisa dibaca balik persis waktu restore
                    if (is_array($nilai)) {
                        return json_encode($nilai, JSON_UNESCAPED_UNICODE);
                    }

                    // tanggal disimpan format Y-m-d (bukan format tampilan d-m-Y),
                    // biar gak ambigu dan gampang di-parse balik saat restore
                    if ($nilai instanceof \Illuminate\Support\Carbon || $nilai instanceof \DateTimeInterface) {
                        return $nilai->format('Y-m-d');
                    }

                    return $nilai;
                }, self::KOLOM_BACKUP);

                $sheet->fromArray($nilaiBaris, null, "A{$baris}");
                $baris++;
            }
        });

        $sheet->freezePane('A2');

        return $spreadsheet;
    }
}