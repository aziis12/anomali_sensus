<?php

namespace App\Services;

use App\Models\DataAnomali;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Restore dari file backup buatan PembuatBackupAnomali.
 *
 * PENTING -- ini BEDA dari PemrosesExcelAnomaliGabungan (importer upload rutin
 * dari pusat):
 * - Sumber file: backup buatan sistem sendiri, BUKAN file dari pusat/Fasih.
 *   Header di baris 1, data mulai baris 2 (beda dengan file pusat: header di
 *   baris 4, data mulai baris 6). File dari pusat / file hasil menu "Ekspor
 *   Excel" biasa TIDAK BISA dipakai di sini -- akan ditolak sejak validasi awal.
 * - Kunci pencocokan baris: kolom "id" (primary key), BUKAN kombinasi
 *   link_fasih|assignment_id|kategori|.... Ini supaya SEMUA kolom -- termasuk
 *   tanggapan_petugas, status_assignment, status_assignment_fasih,
 *   perlu_konfirm_petugas, tanggal_respon -- ikut dipulihkan persis seperti isi
 *   backup, bukan cuma data "mentah" dari pusat seperti kalau lewat importer biasa.
 * - id ada & ketemu di database -> UPDATE baris itu, semua kolom ditimpa sesuai backup.
 * - id kosong, atau id ada tapi sudah gak ketemu di database (baris ini pernah
 *   kehapus/hilang) -> BUAT BARU dengan id yang sama seperti di file backup.
 */
class RestoreBackupAnomali
{
    private const KOLOM_ARRAY = ['data_spesifik', 'daftar_nomor_anomali'];

    private const KOLOM_TANGGAL = ['tanggal_respon'];

    private const KOLOM_INTEGER = ['id', 'urutan_duplikat', 'nomor_anomali', 'riwayat_unggah_id', 'diupdate_oleh'];

    public function proses(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($sheet) < 1) {
            throw new \Exception('File kosong.');
        }

        $barisHeader = array_map(fn ($h) => strtolower(trim((string) $h)), $sheet[0]);

        // validasi longgar tapi penting: minimal kolom "id" dan "assignment_id"
        // harus ada, supaya file yang jelas-jelas BUKAN file backup (mis. file
        // Ekspor biasa, atau file dari pusat) ditolak sejak awal dengan pesan
        // yang jelas -- bukan malah diproses setengah-setengah dan bikin data rusak.
        if (! in_array('id', $barisHeader) || ! in_array('assignment_id', $barisHeader)) {
            throw new \Exception('File ini bukan file backup yang valid (kolom "id" dan "assignment_id" tidak ditemukan di baris pertama). Pastikan file yang diunggah adalah hasil unduhan dari menu Backup, bukan file Ekspor biasa atau file dari pusat.');
        }

        $indeksKolom = array_flip($barisHeader);

        $dibuat = 0;
        $diperbarui = 0;
        $dilewati = 0;

        // satu transaksi besar: kalau di tengah jalan ada error fatal yang gak
        // ketangkep try-catch per baris, seluruh proses dibatalkan (rollback),
        // bukan berhenti setengah-setengah dan bikin data campur aduk
        DB::transaction(function () use ($sheet, $indeksKolom, &$dibuat, &$diperbarui, &$dilewati) {
            for ($i = 1; $i < count($sheet); $i++) {
                $baris = $sheet[$i];
                $nomorBarisExcel = $i + 1;

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

                try {
                    $data = $this->barisKeData($baris, $indeksKolom);
                } catch (\Exception $e) {
                    Log::warning('RestoreBackupAnomali: baris dilewati karena gagal diparse', [
                        'baris_excel' => $nomorBarisExcel,
                        'pesan' => $e->getMessage(),
                    ]);
                    $dilewati++;

                    continue;
                }

                $id = $data['id'] ?? null;
                unset($data['id']);

                if ($id && DataAnomali::where('id', $id)->exists()) {
                    DataAnomali::where('id', $id)->update($data);
                    $diperbarui++;
                } elseif ($id) {
                    // id tercatat di file backup tapi sudah gak ada di database
                    // (mis. baris ini pernah kehapus) -> buat ulang dengan id yang sama
                    $data['id'] = $id;
                    DataAnomali::create($data);
                    $dibuat++;
                } else {
                    DataAnomali::create($data);
                    $dibuat++;
                }
            }
        });

        Log::info('RestoreBackupAnomali: restore selesai', compact('dibuat', 'diperbarui', 'dilewati'));

        return compact('dibuat', 'diperbarui', 'dilewati');
    }

    private function barisKeData(array $baris, array $indeksKolom): array
    {
        $data = [];

        foreach (PembuatBackupAnomali::KOLOM_BACKUP as $kolom) {
            if (! isset($indeksKolom[$kolom])) {
                continue; // kolom ini gak ada di file (mis. backup versi lama sebelum kolom baru ditambahkan) -> skip, biar gak fatal
            }

            $nilai = $baris[$indeksKolom[$kolom]] ?? null;
            $nilai = ($nilai === '') ? null : $nilai;

            if ($nilai !== null && in_array($kolom, self::KOLOM_ARRAY)) {
                $decoded = json_decode((string) $nilai, true);
                $nilai = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            } elseif ($nilai !== null && in_array($kolom, self::KOLOM_TANGGAL)) {
                $nilai = \Illuminate\Support\Carbon::parse($nilai)->format('Y-m-d');
            } elseif ($nilai !== null && in_array($kolom, self::KOLOM_INTEGER)) {
                $nilai = (int) $nilai;
            } elseif ($nilai !== null) {
                $nilai = (string) $nilai;
            }

            $data[$kolom] = $nilai;
        }

        if (empty($data['assignment_id']) || empty($data['link_fasih'])) {
            throw new \Exception('Kolom assignment_id atau link_fasih kosong.');
        }

        return $data;
    }
}