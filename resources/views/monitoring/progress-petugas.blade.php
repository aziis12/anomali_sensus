<x-app-layout>
    <x-slot name="header">
        Progress per PML
    </x-slot>

    <div class="space-y-6">

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
                <h3 class="text-base font-semibold text-ios-label">Progress per PML</h3>
                <p class="text-sm text-ios-secondary mb-4">1 baris = 1 PML, sudah digabung dari semua kecamatan yang dia pegang. Klik header kolom buat urutin.</p>

                <div class="flex flex-wrap items-center gap-3">
                    <input type="text" id="filter-cari" placeholder="Cari nama PML atau PJ..."
                           class="text-sm rounded-ios border-ios-border focus:border-ios-blue focus:ring-ios-blue w-full sm:w-64" />
                    <span id="filter-info" class="text-xs text-ios-secondary ml-auto"></span>
                </div>
            </div>

            <div class="overflow-auto max-h-[70vh]">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-20 bg-gray-50">
                        <tr class="bg-gray-50 text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">PML</th>
                            <th class="px-4 py-3 text-left">PJ</th>
                            <th class="px-4 py-3 text-left">Kecamatan</th>
                            <th data-sort="ditindaklanjuti_sudah" class="px-4 py-3 text-right cursor-pointer select-none hover:text-ios-label">Ditindaklanjuti Sudah</th>
                            <th data-sort="ditindaklanjuti_belum" class="px-4 py-3 text-right cursor-pointer select-none hover:text-ios-label">Ditindaklanjuti Belum</th>
                            <th data-sort="dikerjakan" class="px-4 py-3 text-right cursor-pointer select-none hover:text-ios-label">Anomali Dikerjakan</th>
                            <th data-sort="sisa_belum_dikerjakan" class="px-4 py-3 text-right cursor-pointer select-none hover:text-ios-label">Sisa Belum Dikerjakan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rekap" class="divide-y divide-ios-border">
                        {{-- diisi oleh JS --}}
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @if (! ($adaPenugasan ?? false))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('tbody-rekap').innerHTML =
                    '<tr><td colspan="8" class="px-4 py-10 text-center text-ios-secondary">Belum ada data penugasan PJ. Hubungi superadmin buat unggah file-nya dulu.</td></tr>';
            });
        </script>
    @else
        <script>
            (function () {
                // data dari server, 1 baris = 1 PML (sudah diagregasi di controller,
                // pakai perhitungan yang sama dengan rekap superadmin)
                const semuaBarisRekap = @json($rekap);

                const tbody = document.getElementById('tbody-rekap');
                const inputCari = document.getElementById('filter-cari');
                const filterInfo = document.getElementById('filter-info');
                const headerKolom = document.querySelectorAll('th[data-sort]');

                let urutanKolom = 'sisa_belum_dikerjakan';
                let urutanArah = 'desc'; // default: PML yang sisanya paling banyak ditaruh paling atas

                function escapeHtml(teks) {
                    const div = document.createElement('div');
                    div.textContent = teks ?? '';
                    return div.innerHTML;
                }

                function formatAngka(angka) {
                    return new Intl.NumberFormat('id-ID').format(angka ?? 0);
                }

                function ambilBarisTerfilter() {
                    const kataKunci = inputCari.value.trim().toLowerCase();

                    let hasil = semuaBarisRekap.filter(function (baris) {
                        if (! kataKunci) return true;
                        return (baris.nama || '').toLowerCase().includes(kataKunci)
                            || (baris.pj_terkait || '').toLowerCase().includes(kataKunci);
                    });

                    hasil = hasil.slice().sort(function (a, b) {
                        let nilaiA = a[urutanKolom] ?? 0;
                        let nilaiB = b[urutanKolom] ?? 0;
                        return urutanArah === 'asc' ? nilaiA - nilaiB : nilaiB - nilaiA;
                    });

                    return hasil;
                }

                function buatBarisData(baris, nomor) {
                    const tr = document.createElement('tr');
                    tr.className = baris.ditemukan ? '' : 'bg-amber-50';

                    const badge = baris.ditemukan
                        ? ''
                        : '<span class="ml-1 inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 align-middle">tidak ditemukan di data anomali</span>';

                    tr.innerHTML =
                        '<td class="px-4 py-3 text-ios-secondary">' + nomor + '</td>' +
                        '<td class="px-4 py-3 font-medium text-ios-label">' + escapeHtml(baris.nama) + badge + '</td>' +
                        '<td class="px-4 py-3 text-ios-label">' + escapeHtml(baris.pj_terkait || '-') + '</td>' +
                        '<td class="px-4 py-3 text-ios-label">' + escapeHtml(baris.daftar_kecamatan || '-') + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-green font-medium">' + formatAngka(baris.ditindaklanjuti_sudah) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-amber font-medium">' + formatAngka(baris.ditindaklanjuti_belum) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-label">' + formatAngka(baris.dikerjakan) + '</td>' +
                        '<td class="px-4 py-3 text-right text-ios-label font-semibold">' + formatAngka(baris.sisa_belum_dikerjakan) + '</td>';

                    return tr;
                }

                function bangunTabel() {
                    const daftarBaris = ambilBarisTerfilter();
                    tbody.innerHTML = '';

                    filterInfo.textContent = daftarBaris.length + ' dari ' + semuaBarisRekap.length + ' PML ditampilkan';

                    if (daftarBaris.length === 0) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td colspan="8" class="px-4 py-10 text-center text-ios-secondary">Tidak ada PML yang cocok dengan pencarian.</td>';
                        tbody.appendChild(tr);
                        return;
                    }

                    daftarBaris.forEach(function (baris, indeks) {
                        tbody.appendChild(buatBarisData(baris, indeks + 1));
                    });
                }

                inputCari.addEventListener('input', bangunTabel);

                headerKolom.forEach(function (th) {
                    th.addEventListener('click', function () {
                        const kolom = th.dataset.sort;
                        if (urutanKolom === kolom) {
                            urutanArah = urutanArah === 'asc' ? 'desc' : 'asc';
                        } else {
                            urutanKolom = kolom;
                            urutanArah = 'desc';
                        }
                        bangunTabel();
                    });
                });

                bangunTabel();
            })();
        </script>
    @endif
</x-app-layout>