<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Monitoring per PJ</title>
    <style>
        @page { margin: 22px 28px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #1c1c1e;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 2px 0;
        }
        .sub {
            font-size: 9.5px;
            color: #6b7280;
            margin: 0 0 14px 0;
        }

        table.ringkasan {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        table.ringkasan td {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            width: 25%;
            vertical-align: top;
        }
        table.ringkasan .label {
            display: block;
            font-size: 8.5px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 3px;
        }
        table.ringkasan .nilai {
            font-size: 14px;
            font-weight: bold;
        }
        .nilai-hijau { color: #16a34a; }
        .nilai-amber { color: #d97706; }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
        }
        table.data th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
            color: #4b5563;
        }
        table.data td {
            text-align: left;
        }
        .angka { text-align: right; }
        .tebal { font-weight: bold; }
        .baris-tidak-ditemukan { background: #fffbeb; }
        .badge {
            font-size: 8px;
            color: #b45309;
            display: block;
        }

        tfoot td {
            font-weight: bold;
            background: #f9fafb;
        }
    </style>
</head>
<body>

    <h1>Rekap Monitoring per PJ</h1>
    <p class="sub">Dicetak pada {{ $tanggalCetak->translatedFormat('d F Y, H:i') }} WIB &middot; Total {{ $rekap->count() }} PJ</p>

    <table class="ringkasan">
        <tr>
            <td>
                <span class="label">Ditindaklanjuti Sudah</span>
                <span class="nilai nilai-hijau">{{ number_format($totalRekap->ditindaklanjuti_sudah) }}</span>
            </td>
            <td>
                <span class="label">Ditindaklanjuti Belum</span>
                <span class="nilai nilai-amber">{{ number_format($totalRekap->ditindaklanjuti_belum) }}</span>
            </td>
            <td>
                <span class="label">Anomali Dikerjakan</span>
                <span class="nilai">{{ number_format($totalRekap->dikerjakan) }}</span>
            </td>
            <td>
                <span class="label">Sisa Belum Dikerjakan</span>
                <span class="nilai">{{ number_format($totalRekap->sisa_belum_dikerjakan) }}</span>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th style="width: 18%;">PJ</th>
                <th>Kecamatan</th>
                <th class="angka" style="width: 12%;">Ditindaklanjuti Sudah</th>
                <th class="angka" style="width: 12%;">Ditindaklanjuti Belum</th>
                <th class="angka" style="width: 12%;">Anomali Dikerjakan</th>
                <th class="angka" style="width: 12%;">Sisa Belum Dikerjakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $i => $baris)
                <tr class="{{ $baris->ditemukan ? '' : 'baris-tidak-ditemukan' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>
                        {{ $baris->nama }}
                        @unless ($baris->ditemukan)
                            <span class="badge">tidak ditemukan di data anomali</span>
                        @endunless
                    </td>
                    <td>{{ $baris->daftar_kecamatan ?: '-' }}</td>
                    <td class="angka">{{ number_format($baris->ditindaklanjuti_sudah) }}</td>
                    <td class="angka">{{ number_format($baris->ditindaklanjuti_belum) }}</td>
                    <td class="angka">{{ number_format($baris->dikerjakan) }}</td>
                    <td class="angka tebal">{{ number_format($baris->sisa_belum_dikerjakan) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding: 18px; color:#6b7280;">
                        Belum ada data penugasan PJ.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($rekap->count() > 0)
        <tfoot>
            <tr>
                <td colspan="3">TOTAL</td>
                <td class="angka">{{ number_format($totalRekap->ditindaklanjuti_sudah) }}</td>
                <td class="angka">{{ number_format($totalRekap->ditindaklanjuti_belum) }}</td>
                <td class="angka">{{ number_format($totalRekap->dikerjakan) }}</td>
                <td class="angka">{{ number_format($totalRekap->sisa_belum_dikerjakan) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>