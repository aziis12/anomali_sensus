<x-app-layout>
    <x-slot name="header">Hasil Impor Data Lama</x-slot>

    <div class="space-y-4">

        @if (! $hasil)
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-10 text-center text-ios-secondary">
                Belum ada hasil impor untuk ditampilkan. Silakan lakukan impor dulu dari halaman
                <a href="{{ route('impor-data-lama.create') }}" class="text-ios-blue underline">Impor Data Lama</a>.
            </div>
        @else
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4 text-sm">
                <p class="font-medium text-ios-label mb-1">Ringkasan (diimpor {{ $hasil['waktu'] }})</p>
                <p class="text-ios-secondary">{{ $hasil['ringkasan'] }}</p>
            </div>

            {{-- DIISI: nama + kolom apa saja yang diisi + nilainya --}}
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4">
                <p class="font-medium text-ios-label mb-3">
                    Data yang diisi ({{ count($hasil['diisi']) }})
                </p>
                @if (count($hasil['diisi']) === 0)
                    <p class="text-sm text-ios-secondary italic">Tidak ada.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($hasil['diisi'] as $baris)
                            <div class="border border-ios-border rounded-lg p-3">
                                <p class="font-medium text-ios-label text-sm mb-2">{{ $baris['nama'] }}</p>
                                <table class="w-full text-xs">
                                    <tbody>
                                        @foreach ($baris['kolom'] as $label => $nilai)
                                            <tr class="border-t border-ios-border/50">
                                                <td class="py-1 pr-3 text-ios-secondary w-1/3 align-top">{{ $label }}</td>
                                                <td class="py-1 text-ios-label">{{ $nilai }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- DILEWATI: cuma nama, karena memang tidak ada kolom yang diisi --}}
            {{-- DILEWATI: nama + kolom apa saja yang SUDAH ada isinya sebelum impor --}}
<div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4">
    <p class="font-medium text-ios-label mb-3">
        Data yang dilewati, sudah terisi sebelumnya ({{ count($hasil['dilewati']) }})
    </p>
    @if (count($hasil['dilewati']) === 0)
        <p class="text-sm text-ios-secondary italic">Tidak ada.</p>
    @else
        <div class="space-y-3">
            @foreach ($hasil['dilewati'] as $baris)
                <div class="border border-ios-border rounded-lg p-3">
                    <p class="font-medium text-ios-label text-sm mb-2">
                        {{ $loop->iteration }}. {{ $baris['nama'] }}
                    </p>
                    @if (empty($baris['kolom']))
                        <p class="text-xs text-ios-secondary italic">Tidak ada field lama yang tercatat.</p>
                    @else
                        <table class="w-full text-xs">
                            <tbody>
                                @foreach ($baris['kolom'] as $label => $nilai)
                                    <tr class="border-t border-ios-border/50">
                                        <td class="py-1 pr-3 text-ios-secondary w-1/3 align-top">{{ $label }}</td>
                                        <td class="py-1 text-ios-label">{{ $nilai }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

            {{-- TIDAK KETEMU --}}
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4">
                <p class="font-medium text-ios-label mb-3">
                    Tidak ketemu pasangannya ({{ count($hasil['tidak_ketemu']) }})
                </p>
                @if (count($hasil['tidak_ketemu']) === 0)
                    <p class="text-sm text-ios-secondary italic">Tidak ada.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-ios-secondary border-b border-ios-border">
                                <th class="py-1.5 pr-3">Nama</th>
                                <th class="py-1.5 pr-3">Kategori / Nama Anomali</th>
                                <th class="py-1.5">Link Fasih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hasil['tidak_ketemu'] as $baris)
                                <tr class="border-b border-ios-border/50">
                                    <td class="py-1.5 pr-3">{{ $baris['nama'] }}</td>
                                    <td class="py-1.5 pr-3">{{ $baris['kategori'] }}</td>
                                    <td class="py-1.5 truncate max-w-xs">
                                        <a href="{{ $baris['link'] }}" target="_blank" class="text-ios-blue underline">{{ $baris['link'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

    </div>
</x-app-layout>