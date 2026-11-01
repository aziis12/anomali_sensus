<x-app-layout>
    <x-slot name="header">
        Rekap Monitoring PML / PJ
    </x-slot>

    <div class="space-y-6">

        <a href="{{ route('monitoring-tindak-lanjut.grafik') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-ios bg-ios-blue text-white shadow-sm text-sm font-semibold hover:opacity-90">
            Lihat Grafik &amp; Breakdown
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
            </svg>
        </a>

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-ios p-4">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-1">Unggah Data Penugasan PJ</h3>
            <p class="text-sm text-ios-secondary mb-4">
                File excel berisi kolom: No, Email PML, PML, Email PJ, PJ, Kec. Setiap upload akan menggantikan seluruh data penugasan sebelumnya.
            </p>

            <form action="{{ route('monitoring-tindak-lanjut.unggah-pj') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                @csrf
                <input type="file" name="file_pj" accept=".xlsx,.xls" required
                       class="text-sm text-ios-secondary file:mr-3 file:py-2 file:px-4 file:rounded-ios file:border-0 file:bg-ios-blue file:text-white file:text-sm file:font-medium hover:file:opacity-90" />
                <button type="submit" class="shrink-0 bg-ios-blue text-white text-sm font-medium px-4 py-2 rounded-ios hover:opacity-90">
                    Unggah &amp; Ganti Data
                </button>
            </form>

            @error('file_pj')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                <p class="text-xs text-ios-secondary mb-1">Ditindaklanjuti &middot; Sudah</p>
                <p class="text-2xl font-display font-semibold text-ios-green">{{ number_format($totalRekap->ditindaklanjuti_sudah) }}</p>
            </div>
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                <p class="text-xs text-ios-secondary mb-1">Ditindaklanjuti &middot; Belum</p>
                <p class="text-2xl font-display font-semibold text-ios-amber">{{ number_format($totalRekap->ditindaklanjuti_belum) }}</p>
            </div>
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                <p class="text-xs text-ios-secondary mb-1">Anomali Dikerjakan</p>
                <p class="text-2xl font-display font-semibold text-ios-label">{{ number_format($totalRekap->dikerjakan) }}</p>
            </div>
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                <p class="text-xs text-ios-secondary mb-1">Sisa Belum Dikerjakan</p>
                <p class="text-2xl font-display font-semibold text-ios-label">{{ number_format($totalRekap->sisa_belum_dikerjakan) }}</p>
            </div>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="p-5 border-b border-ios-border">
                <h3 class="text-base font-semibold text-ios-label">Rekap per PJ &amp; PML</h3>
                <p class="text-sm text-ios-secondary mb-4">Progres tindak lanjut (dari pusat) dan pengerjaan (oleh pegawai) untuk tiap kombinasi PML + Kecamatan, dikelompokkan per PJ.</p>

                {{-- Filter: PML, Kecamatan, PJ. Diproses murni di JS (lihat script di bawah), --}}
                {{-- gak perlu reload halaman tiap ganti filter. --}}
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <label for="filter-pj" class="text-xs font-medium text-ios-secondary">PJ</label>
                        <select id="filter-pj" class="text-sm rounded-ios border-ios-border focus:border-ios-blue focus:ring-ios-blue">
                            <option value="">Semua PJ</option>
                            @foreach ($daftarPj as $satuPj)
                                <option value="{{ $satuPj }}">{{ $satuPj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="filter-pml" class="text-xs font-medium text-ios-secondary">PML</label>
                        <select id="filter-pml" class="text-sm rounded-ios border-ios-border focus:border-ios-blue focus:ring-ios-blue">
                            <option value="">Semua PML</option>
                            @foreach ($daftarPml as $satuPml)
                                <option value="{{ $satuPml }}">{{ $satuPml }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="filter-kec" class="text-xs font-medium text-ios-secondary">Kecamatan</label>
                        <select id="filter-kec" class="text-sm rounded-ios border-ios-border focus:border-ios-blue focus:ring-ios-blue">
                            <option value="">Semua Kecamatan</option>
                            @foreach ($daftarKecamatan as $satuKec)
                                <option value="{{ $satuKec }}">{{ $satuKec }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="filter-reset"
                            class="text-sm font-medium text-ios-secondary px-3 py-2 rounded-ios border border-ios-border hover:bg-gray-50">
                        Reset Filter
                    </button>
                    <span id="filter-info" class="text-xs text-ios-secondary ml-auto"></span>
                </div>
            </div>

            {{-- Wrapper ini yang bikin header freeze: overflow-y-auto + max-height bikin dia
                 jadi "panel" scroll sendiri, sehingga posisi sticky di header ikut ke-lock
                 pas isinya di-scroll (kalau cuma overflow-x-auto tanpa max-height, sticky-nya
                 gak akan kepakai karena gak ada area scroll vertikal). --}}
            <div class="overflow-auto max-h-[70vh]">
                <table class="min-w-full text-sm">
                    {{-- Sticky ditaro di <thead> langsung (bukan per <th> dengan offset px
                         manual) -- browser modern nempelin seluruh thead (2 baris header)
                         sebagai satu kesatuan pas di-scroll, jadi gak ada celah/bolong di
                         antara baris header pertama & kedua kayak sebelumnya. --}}
                    <thead class="sticky top-0 z-20 bg-gray-50">
                        <tr class="bg-gray-50 text-ios-secondary text-xs uppercase tracking-wide">
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-left align-bottom">No</th>
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-left align-bottom">PJ</th>
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-left align-bottom">PML</th>
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-left align-bottom">Kec</th>
                            <th colspan="2" class="bg-gray-50 px-4 py-2 text-center border-b border-ios-border">Anomali Ditindaklanjuti</th>
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-right align-bottom">Anomali Dikerjakan</th>
                            <th rowspan="2" class="bg-gray-50 px-4 py-3 text-right align-bottom">Sisa Belum Dikerjakan</th>
                            <th colspan="2" class="bg-gray-50 px-4 py-2 text-center border-b border-ios-border border-l border-ios-border">Total PJ</th>
                        </tr>
                        <tr class="bg-gray-50 text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="bg-gray-50 px-4 py-2 text-right">Sudah</th>
                            <th class="bg-gray-50 px-4 py-2 text-right">Belum</th>
                            <th class="bg-gray-50 px-4 py-2 text-right border-l border-ios-border">Sisa</th>
                            <th class="bg-gray-50 px-4 py-2 text-right">Dikerjakan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rekap" class="divide-y divide-ios-border">
                        {{-- diisi oleh JS (bangunTabel) berdasarkan data di semuaBarisRekap --}}
                    </tbody>
                </table>
            </div>
        </div>

    </div>


    @if (! ($adaPenugasan ?? false))
        <script>
            // gak ada data penugasan sama sekali -> langsung render pesan kosong, gak perlu
            // nunggu JS filter jalan
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('tbody-rekap').innerHTML =
                    '<tr><td colspan="10" class="px-4 py-10 text-center text-ios-secondary">Belum ada data penugasan PJ. Unggah file excel di atas dulu.</td></tr>';
            });
        </script>
    @else
        <script>
            (function () {
                // data mentah dari server, satu baris = satu kombinasi PML+Kecamatan.
                // Grouping per PJ (rowspan) dan subtotal dihitung ulang di sini tiap kali
                // filter berubah, supaya rowspan-nya selalu sesuai jumlah baris yang lolos filter.
                const semuaBarisRekap = @json($rekap);

                const tbody = document.getElementById('tbody-rekap');
                const filterPj = document.getElementById('filter-pj');
                const filterPml = document.getElementById('filter-pml');
                const filterKec = document.getElementById('filter-kec');
                const filterInfo = document.getElementById('filter-info');
                const tombolReset = document.getElementById('filter-reset');

                function escapeHtml(teks) {
                    const div = document.createElement('div');
                    div.textContent = teks ?? '';
                    return div.innerHTML;
                }

                function formatAngka(angka) {
                    return new Intl.NumberFormat('id-ID').format(angka ?? 0);
                }

                function ambilBarisTerfilter() {
                    const nilaiPj = filterPj.value;
                    const nilaiPml = filterPml.value;
                    const nilaiKec = filterKec.value;

                    return semuaBarisRekap.filter(function (baris) {
                        if (nilaiPj && baris.pj !== nilaiPj) return false;
                        if (nilaiPml && baris.pml !== nilaiPml) return false;
                        if (nilaiKec && baris.kecamatan !== nilaiKec) return false;
                        return true;
                    });
                }

                // kelompokkan baris (yang sudah difilter) per PJ, urutannya ngikut urutan
                // kemunculan pertama tiap PJ di data (data dari server sudah diurutkan per PJ)
                function kelompokkanPerPj(daftarBaris) {
                    const grupList = [];
                    const grupMap = new Map();

                    daftarBaris.forEach(function (baris) {
                        const kunciPj = baris.pj || '';
                        if (! grupMap.has(kunciPj)) {
                            const grupBaru = { pj: baris.pj, baris: [] };
                            grupMap.set(kunciPj, grupBaru);
                            grupList.push(grupBaru);
                        }
                        grupMap.get(kunciPj).baris.push(baris);
                    });

                    return grupList;
                }

                // Warna gantian per grup PJ (pastel, kontras cukup buat dibaca tapi gak
                // norak), di-cycle berdasarkan urutan grup. Baris yang "tidak ditemukan di
                // data anomali" tetap didahulukan pakai bg-amber (biar peringatannya gak
                // ketutup warna grup).
                const paletWarnaGrup = [
                    'bg-blue-50',
                    'bg-emerald-50',
                    'bg-violet-50',
                    'bg-pink-50',
                    'bg-cyan-50',
                    'bg-orange-50',
                    'bg-lime-50',
                    'bg-fuchsia-50',
                ];

                function hitungSubtotal(daftarBaris) {
                    return daftarBaris.reduce(function (akumulasi, baris) {
                        akumulasi.ditindaklanjuti_sudah += baris.ditindaklanjuti_sudah;
                        akumulasi.ditindaklanjuti_belum += baris.ditindaklanjuti_belum;
                        akumulasi.dikerjakan += baris.dikerjakan;
                        akumulasi.sisa_belum_dikerjakan += baris.sisa_belum_dikerjakan;
                        return akumulasi;
                    }, {
                        ditindaklanjuti_sudah: 0,
                        ditindaklanjuti_belum: 0,
                        dikerjakan: 0,
                        sisa_belum_dikerjakan: 0,
                    });
                }

                function buatBarisData(baris, nomor, infoSelPj, warnaGrup) {
                    const tr = document.createElement('tr');
                    // baris "tidak ditemukan" tetap amber (prioritas peringatan), sisanya
                    // ikut warna grup PJ-nya
                    tr.className = baris.ditemukan ? warnaGrup : 'bg-amber-50';

                    let selPj = '';
                    let selTotalSisa = '';
                    let selTotalDikerjakan = '';

                    if (infoSelPj) {
                        const rs = infoSelPj.jumlahBaris;
                        selPj = '<td rowspan="' + rs + '" class="px-4 py-3 font-medium text-ios-label align-top border-r border-ios-border">' + escapeHtml(baris.pj || '-') + '</td>';

                        if (infoSelPj.subtotal) {
                            selTotalSisa = '<td rowspan="' + rs + '" class="px-4 py-3 text-right align-top text-ios-label font-semibold border-l border-ios-border">' + formatAngka(infoSelPj.subtotal.sisa_belum_dikerjakan) + '</td>';
                            selTotalDikerjakan = '<td rowspan="' + rs + '" class="px-4 py-3 text-right align-top text-ios-label font-semibold">' + formatAngka(infoSelPj.subtotal.dikerjakan) + '</td>';
                        } else {
                            // PJ cuma pegang 1 kecamatan -> gak ada yang perlu dijumlah,
                            // ditampilin strip aja biar gak keliatan seperti data ilang
                            selTotalSisa = '<td rowspan="' + rs + '" class="px-4 py-3 text-right align-top text-ios-secondary border-l border-ios-border">&mdash;</td>';
                            selTotalDikerjakan = '<td rowspan="' + rs + '" class="px-4 py-3 text-right align-top text-ios-secondary">&mdash;</td>';
                        }
                    }

                    const badge = baris.ditemukan
                        ? ''
                        : '<span class="ml-1 inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 align-middle">tidak ditemukan di data anomali</span>';

                    tr.innerHTML =
                        '<td class="px-4 py-3 text-ios-secondary">' + nomor + '</td>' +
                        selPj +
                        '<td class="px-4 py-3 font-medium text-ios-label">' + escapeHtml(baris.pml) + badge + '</td>' +
                        '<td class="px-4 py-3 text-ios-label">' + escapeHtml(baris.kecamatan) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-green font-medium">' + formatAngka(baris.ditindaklanjuti_sudah) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-amber font-medium">' + formatAngka(baris.ditindaklanjuti_belum) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-label">' + formatAngka(baris.dikerjakan) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-label">' + formatAngka(baris.sisa_belum_dikerjakan) + '</td>' +
                        selTotalSisa +
                        selTotalDikerjakan;

                    return tr;
                }

                function bangunTabel() {
                    const daftarBaris = ambilBarisTerfilter();
                    tbody.innerHTML = '';

                    filterInfo.textContent = daftarBaris.length + ' dari ' + semuaBarisRekap.length + ' baris ditampilkan';

                    if (daftarBaris.length === 0) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td colspan="10" class="px-4 py-10 text-center text-ios-secondary">Tidak ada data yang cocok dengan filter.</td>';
                        tbody.appendChild(tr);
                        return;
                    }

                    const grupList = kelompokkanPerPj(daftarBaris);
                    let nomor = 1;

                    grupList.forEach(function (grup, indeksGrup) {
                        const warnaGrup = paletWarnaGrup[indeksGrup % paletWarnaGrup.length];
                        const infoSelPj = {
                            jumlahBaris: grup.baris.length,
                            // subtotal cuma ditampilkan kalau PJ-nya pegang lebih dari 1
                            // kecamatan, biar gak diulang percuma buat yang cuma 1
                            subtotal: grup.baris.length > 1 ? hitungSubtotal(grup.baris) : null,
                        };

                        grup.baris.forEach(function (baris, indeks) {
                            tbody.appendChild(buatBarisData(baris, nomor, indeks === 0 ? infoSelPj : null, warnaGrup));
                            nomor++;
                        });
                    });
                }

                filterPj.addEventListener('change', bangunTabel);
                filterPml.addEventListener('change', bangunTabel);
                filterKec.addEventListener('change', bangunTabel);
                tombolReset.addEventListener('click', function () {
                    filterPj.value = '';
                    filterPml.value = '';
                    filterKec.value = '';
                    bangunTabel();
                });

                bangunTabel();
            })();
        </script>
    @endif
</x-app-layout>