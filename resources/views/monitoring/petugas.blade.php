<x-app-layout>
    <x-slot name="header">Progress Saya</x-slot>

    <div class="max-w-2xl space-y-4">

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4 flex items-center justify-between">
            @if ($namaPml)
                <p class="text-sm text-ios-secondary">Mengerjakan data atas nama <span class="font-medium text-ios-label">{{ $namaPml }}</span></p>
            @else
                <p class="text-sm text-ios-secondary">Kamu belum memilih kategori PML.</p>
            @endif
            <a href="{{ route('data-anomali.index') }}" class="text-ios-blue text-sm font-medium hover:underline shrink-0">
                {{ $namaPml ? 'Ganti PML' : 'Pilih PML' }}
            </a>
        </div>

        @if (! $namaPml)
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-10 text-center text-ios-secondary">
                Pilih kategori PML dulu buat lihat progress kamu.
            </div>
        @elseif (! $ringkasan)
            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-ios p-4">
                PML <span class="font-medium">{{ $namaPml }}</span> belum ada di data Penugasan PJ. Hubungi superadmin buat cek kembali.
            </div>
        @else
            @unless ($ringkasan->ditemukan)
                <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-ios p-4">
                    Kecamatan yang dipegang PML ini belum ketemu di data anomali.
                </div>
            @endunless

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                    <p class="text-xs text-ios-secondary mb-1">Ditindaklanjuti &middot; Sudah</p>
                    <p class="text-2xl font-display font-semibold text-ios-green">{{ number_format($ringkasan->ditindaklanjuti_sudah) }}</p>
                </div>
                <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                    <p class="text-xs text-ios-secondary mb-1">Ditindaklanjuti &middot; Belum</p>
                    <p class="text-2xl font-display font-semibold text-ios-amber">{{ number_format($ringkasan->ditindaklanjuti_belum) }}</p>
                </div>
                <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                    <p class="text-xs text-ios-secondary mb-1">Anomali Dikerjakan</p>
                    <p class="text-2xl font-display font-semibold text-ios-label">{{ number_format($ringkasan->dikerjakan) }}</p>
                </div>
                <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-5">
                    <p class="text-xs text-ios-secondary mb-1">Sisa Belum Dikerjakan</p>
                    <p class="text-2xl font-display font-semibold text-ios-label">{{ number_format($ringkasan->sisa_belum_dikerjakan) }}</p>
                </div>
            </div>

            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
                <div class="p-5 border-b border-ios-border">
                    <h3 class="text-base font-semibold text-ios-label">Kecamatan yang Dipegang</h3>
                    <p class="text-sm text-ios-secondary">Rincian per kecamatan sesuai data Penugasan PJ untuk PML {{ $namaPml }}.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-ios-secondary text-xs uppercase tracking-wide">
                                <th class="px-4 py-3">Kecamatan</th>
                                <th class="px-4 py-3">PJ</th>
                                <th class="px-4 py-3">Ditindaklanjuti Sudah</th>
                                <th class="px-4 py-3">Ditindaklanjuti Belum</th>
                                <th class="px-4 py-3">Dikerjakan</th>
                                <th class="px-4 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perKecamatan as $baris)
                                <tr class="border-t border-ios-border {{ $baris->ditemukan ? '' : 'bg-amber-50' }}">
                                    <td class="px-4 py-3 text-ios-label">
                                        {{ $baris->kecamatan ?: '-' }}
                                        @unless ($baris->ditemukan)
                                            <span class="ml-1 inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 align-middle">
                                                tidak ditemukan di data anomali
                                            </span>
                                        @endunless
                                    </td>
                                    <td class="px-4 py-3 text-ios-label">{{ $baris->pj ?: '-' }}</td>
                                    <td class="px-4 py-3 text-ios-green font-medium">{{ $baris->ditindaklanjuti_sudah }}</td>
                                    <td class="px-4 py-3 text-ios-amber font-medium">{{ $baris->ditindaklanjuti_belum }}</td>
                                    <td class="px-4 py-3 text-ios-label">{{ $baris->dikerjakan }}</td>
                                    <td class="px-4 py-3 text-ios-label font-semibold">{{ $baris->sisa_belum_dikerjakan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-ios-secondary">Belum ada kecamatan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>