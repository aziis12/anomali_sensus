<x-app-layout>
    <x-slot name="header">
        Rekap Monitoring per PPL
    </x-slot>

    <div class="space-y-6">

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-ios p-4">
                {{ session('status') }}
            </div>
        @endif

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
                <h3 class="text-base font-semibold text-ios-label">Rekap per PPL</h3>
                <p class="text-sm text-ios-secondary mb-4">Baris kuning = 1 PML (jumlah gabungan semua PPL-nya). Baris di bawahnya = PPL yang dia pegang, dengan angka masing-masing.</p>

                <div class="flex flex-wrap items-center gap-3">
                    <input type="text" id="filter-cari" placeholder="Cari nama PML atau PPL..."
                           class="text-sm rounded-ios border-ios-border focus:border-ios-blue focus:ring-ios-blue w-full sm:w-64" />
                    <span id="filter-info" class="text-xs text-ios-secondary"></span>

                    <a href="{{ route('monitoring-tindak-lanjut.per-ppl.pdf') }}"
                       class="ml-auto inline-flex items-center gap-2 bg-ios-blue text-white text-sm font-medium px-4 py-2 rounded-ios hover:opacity-90 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh PDF
                    </a>
                </div>
            </div>

            <div class="overflow-auto max-h-[70vh]">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-20 bg-gray-50">
                        <tr class="bg-gray-50 text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">PML</th>
                            <th class="px-4 py-3 text-left">PPL</th>
                            <th class="px-4 py-3 text-right">Ditindaklanjuti Sudah</th>
                            <th class="px-4 py-3 text-right">Ditindaklanjuti Belum</th>
                            <th class="px-4 py-3 text-right">Anomali Dikerjakan</th>
                            <th class="px-4 py-3 text-right">Sisa Belum Dikerjakan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rekap" class="divide-y divide-ios-border">
                        {{-- diisi oleh JS --}}
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        (function () {
            // data dari server, 2 tingkat: PML -> daftar_ppl, masing-masing PPL bawa
            // angkanya sendiri (sudah diagregasi di controller)
            const semuaGrupPml = @json($rekap);

            const tbody = document.getElementById('tbody-rekap');
            const inputCari = document.getElementById('filter-cari');
            const filterInfo = document.getElementById('filter-info');

            function escapeHtml(teks) {
                const div = document.createElement('div');
                div.textContent = teks ?? '';
                return div.innerHTML;
            }

            function formatAngka(angka) {
                return new Intl.NumberFormat('id-ID').format(angka ?? 0);
            }

            function pplCocok(ppl, namaPml, kataKunci) {
                if (! kataKunci) return true;
                return namaPml.toLowerCase().includes(kataKunci)
                    || (ppl.nama || '').toLowerCase().includes(kataKunci);
            }

            function ambilGrupTerfilter() {
                const kataKunci = inputCari.value.trim().toLowerCase();

                return semuaGrupPml
                    .map(function (grupPml) {
                        const pplTerfilter = grupPml.daftar_ppl.filter(function (ppl) {
                            return pplCocok(ppl, grupPml.nama, kataKunci);
                        });

                        return Object.assign({}, grupPml, { daftar_ppl: pplTerfilter });
                    })
                    .filter(function (grupPml) {
                        return grupPml.daftar_ppl.length > 0;
                    });
            }

            function buatBarisPml(grupPml) {
                const tr = document.createElement('tr');
                tr.className = 'bg-amber-100';

                tr.innerHTML =
                    '<td class="px-4 py-2.5 font-semibold text-ios-label">' + escapeHtml(grupPml.nama) + '</td>' +
                    '<td class="px-4 py-2.5"></td>' +
                    '<td class="px-4 py-2.5 text-right font-semibold">' + formatAngka(grupPml.ditindaklanjuti_sudah) + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-semibold">' + formatAngka(grupPml.ditindaklanjuti_belum) + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-semibold">' + formatAngka(grupPml.dikerjakan) + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-semibold">' + formatAngka(grupPml.sisa_belum_dikerjakan) + '</td>';

                return tr;
            }

            function buatBarisPpl(ppl) {
                const tr = document.createElement('tr');

                tr.innerHTML =
                    '<td class="px-4 py-2"></td>' +
                    '<td class="px-4 py-2 text-ios-label">' + escapeHtml(ppl.nama) + '</td>' +
                    '<td class="px-4 py-2 text-right text-ios-green">' + formatAngka(ppl.ditindaklanjuti_sudah) + '</td>' +
                    '<td class="px-4 py-2 text-right text-ios-amber">' + formatAngka(ppl.ditindaklanjuti_belum) + '</td>' +
                    '<td class="px-4 py-2 text-right text-ios-label">' + formatAngka(ppl.dikerjakan) + '</td>' +
                    '<td class="px-4 py-2 text-right text-ios-label">' + formatAngka(ppl.sisa_belum_dikerjakan) + '</td>';

                return tr;
            }

            function bangunTabel() {
                const daftarGrup = ambilGrupTerfilter();
                tbody.innerHTML = '';

                const totalPpl = daftarGrup.reduce(function (jumlah, grupPml) { return jumlah + grupPml.daftar_ppl.length; }, 0);
                filterInfo.textContent = daftarGrup.length + ' PML, ' + totalPpl + ' PPL ditampilkan';

                if (daftarGrup.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="6" class="px-4 py-10 text-center text-ios-secondary">Tidak ada yang cocok dengan pencarian.</td>';
                    tbody.appendChild(tr);
                    return;
                }

                daftarGrup.forEach(function (grupPml) {
                    tbody.appendChild(buatBarisPml(grupPml));
                    grupPml.daftar_ppl.forEach(function (ppl) {
                        tbody.appendChild(buatBarisPpl(ppl));
                    });
                });
            }

            inputCari.addEventListener('input', bangunTabel);

            bangunTabel();
        })();
    </script>
</x-app-layout>