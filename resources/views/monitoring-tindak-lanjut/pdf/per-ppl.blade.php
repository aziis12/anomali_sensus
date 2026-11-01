<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1c1c1e; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.sub { color: #666; margin-top: 0; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e9dcc9; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #bd5d34; color: #fff; text-align: center; }
        td.angka { text-align: right; }
        tr.baris-pml td { background: #f3e3d3; font-weight: bold; }
        tfoot td { font-weight: bold; background: #e9dcc9; }
        col.col-nama { width: 20%; }
        col.col-angka { width: 15%; }
    </style>
</head>
<body>
    <h1>Rekap Monitoring per PPL</h1>
    <p class="sub">Dicetak: {{ $tanggalCetak->translatedFormat('d F Y H:i') }}</p>

    <table>
        <colgroup>
            <col class="col-nama"><col class="col-nama">
            <col class="col-angka"><col class="col-angka"><col class="col-angka"><col class="col-angka">
        </colgroup>
        <thead>
            <tr>
                <th>PML</th>
                <th>PPL</th>
                <th>Ditindaklanjuti Sudah</th>
                <th>Ditindaklanjuti Belum</th>
                <th>Anomali Dikerjakan</th>
                <th>Sisa Belum Dikerjakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $grupPml)
                <tr class="baris-pml">
                    <td>{{ $grupPml->nama }}</td>
                    <td></td>
                    <td class="angka">{{ number_format($grupPml->ditindaklanjuti_sudah) }}</td>
                    <td class="angka">{{ number_format($grupPml->ditindaklanjuti_belum) }}</td>
                    <td class="angka">{{ number_format($grupPml->dikerjakan) }}</td>
                    <td class="angka">{{ number_format($grupPml->sisa_belum_dikerjakan) }}</td>
                </tr>
                @foreach ($grupPml->daftar_ppl as $ppl)
                    <tr>
                        <td></td>
                        <td>{{ $ppl->nama }}</td>
                        <td class="angka">{{ number_format($ppl->ditindaklanjuti_sudah) }}</td>
                        <td class="angka">{{ number_format($ppl->ditindaklanjuti_belum) }}</td>
                        <td class="angka">{{ number_format($ppl->dikerjakan) }}</td>
                        <td class="angka">{{ number_format($ppl->sisa_belum_dikerjakan) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" style="text-align:center;">Belum ada data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="angka">{{ number_format($totalRekap->ditindaklanjuti_sudah) }}</td>
                <td class="angka">{{ number_format($totalRekap->ditindaklanjuti_belum) }}</td>
                <td class="angka">{{ number_format($totalRekap->dikerjakan) }}</td>
                <td class="angka">{{ number_format($totalRekap->sisa_belum_dikerjakan) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>