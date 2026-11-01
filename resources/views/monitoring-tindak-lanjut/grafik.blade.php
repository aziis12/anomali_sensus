<x-app-layout>
    <x-slot name="header">
        Status Tindak Lanjut Pusat
    </x-slot>

    <div class="space-y-6">
{{-- 
        <a href="{{ route('monitoring-tindak-lanjut.per-pml') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-ios bg-ios-card border border-ios-border shadow-sm text-sm font-semibold text-ios-blue hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
            </svg>
            Kembali ke Rekap PML/PJ
        </a> --}}

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <p class="text-sm text-ios-secondary mb-1">Total keseluruhan data anomali</p>
            <div class="flex items-end gap-3">
                <p class="text-3xl font-display font-semibold text-ios-label">{{ number_format($total) }}</p>
                @if (! is_null($selisihTotal) && $selisihTotal != 0)
                    <span class="mb-1 text-sm font-semibold px-2 py-0.5 rounded-full {{ $selisihTotal > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $selisihTotal > 0 ? '+' : '' }}{{ number_format($selisihTotal) }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-ios-secondary mt-1">
                Data ini diambil langsung dari kolom Tindak Lanjut pada file excel FASIH, otomatis diperbarui setiap kali superadmin mengunggah data baru.
                @if ($riwayatSebelumnya)
                    Dibandingkan unggahan sebelumnya ({{ $riwayatSebelumnya->tanggal_data->translatedFormat('d M Y') }}).
                @endif
            </p>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="p-5 border-b border-ios-border">
                <h3 class="text-base font-semibold text-ios-label">Riwayat Unggah</h3>
                <p class="text-sm text-ios-secondary">Total data, breakdown sudah/belum ditindaklanjuti, & selisih setiap kali superadmin mengunggah file excel dari pusat. Terbaru di atas.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="px-4 py-2 text-left">Tanggal Unggah</th>
                            <th class="px-4 py-2 text-right">Total Data</th>
                            <th class="px-4 py-2 text-right">Selisih</th>
                            <th class="px-4 py-2 text-right">Sudah Ditindaklanjuti</th>
                            <th class="px-4 py-2 text-right">Selisih</th>
                            <th class="px-4 py-2 text-right">Belum Ditindaklanjuti</th>
                            <th class="px-4 py-2 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ios-border">
                        @forelse ($riwayatTabel as $satu)
                            <tr>
                                <td class="px-4 py-3 text-ios-label">{{ $satu->tanggal->translatedFormat('d M Y H:i') }}</td>

                                <td class="px-4 py-3 text-right text-ios-label font-medium">{{ number_format($satu->total) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (is_null($satu->selisih_total))
                                        <span class="text-ios-secondary">&mdash;</span>
                                    @elseif ($satu->selisih_total == 0)
                                        <span class="text-ios-secondary">0</span>
                                    @else
                                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $satu->selisih_total > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $satu->selisih_total > 0 ? '+' : '' }}{{ number_format($satu->selisih_total) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-ios-green font-medium">{{ number_format($satu->sudah) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (is_null($satu->selisih_sudah))
                                        <span class="text-ios-secondary">&mdash;</span>
                                    @elseif ($satu->selisih_sudah == 0)
                                        <span class="text-ios-secondary">0</span>
                                    @else
                                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $satu->selisih_sudah > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $satu->selisih_sudah > 0 ? '+' : '' }}{{ number_format($satu->selisih_sudah) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-ios-amber font-medium">{{ number_format($satu->belum) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (is_null($satu->selisih_belum))
                                        <span class="text-ios-secondary">&mdash;</span>
                                    @elseif ($satu->selisih_belum == 0)
                                        <span class="text-ios-secondary">0</span>
                                    @else
                                        {{-- untuk "belum", naik = merah (makin banyak nunggak), turun = hijau (makin sedikit) --}}
                                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $satu->selisih_belum > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $satu->selisih_belum > 0 ? '+' : '' }}{{ number_format($satu->selisih_belum) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-ios-secondary">Belum ada riwayat unggah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="p-5 border-b border-ios-border">
                <h3 class="text-base font-semibold text-ios-label">Breakdown per kategori</h3>
                <p class="text-sm text-ios-secondary">Jumlah data untuk setiap kategori status tindak lanjut yang tercatat dari pusat.</p>
            </div>

            <div class="divide-y divide-ios-border">
                @forelse ($perKategori as $satu)
                    @php
                        $isiBelum = str_contains(strtolower($satu->kategori), 'belum');
                    @endphp
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-4 mb-2">
                            <p class="text-sm font-medium text-ios-label">{{ $satu->kategori }}</p>
                            <div class="flex items-center gap-2 shrink-0">
                                @if (! is_null($satu->selisih) && $satu->selisih != 0)
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $satu->selisih > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $satu->selisih > 0 ? '+' : '' }}{{ number_format($satu->selisih) }}
                                    </span>
                                @endif
                                <p class="text-sm text-ios-secondary">{{ number_format($satu->jumlah) }} data &middot; {{ $satu->persentase }}%</p>
                            </div>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $isiBelum ? 'bg-ios-amber' : 'bg-ios-green' }}" style="width: {{ $satu->persentase }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-ios-secondary">Belum ada data.</div>
                @endforelse

                @if ($tanpaKategori > 0)
                    <div class="p-5 bg-gray-50">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-medium text-ios-secondary">Tanpa kategori tercatat</p>
                            <p class="text-sm text-ios-secondary">{{ number_format($tanpaKategori) }} data</p>
                        </div>
                        <p class="text-xs text-ios-secondary mt-1">Baris ini kolom Tindak Lanjut-nya kosong di excel, kemungkinan belum diproses oleh FASIH.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>