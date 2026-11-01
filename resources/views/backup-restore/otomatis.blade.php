<x-app-layout>
    <x-slot name="header">Backup Otomatis (Tersimpan di Server)</x-slot>

    <div class="max-w-2xl space-y-5">

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <p class="text-sm text-ios-secondary mb-4">
                Ini daftar backup yang dibuat otomatis oleh sistem (dari scheduler harian atau trigger cron),
                tersimpan di server. Beda dari tombol "Unduh Backup Sekarang" yang langsung ke laptop kamu —
                file di sini perlu di-download manual dulu dari sini kalau mau dipakai untuk restore atau
                disimpan di tempat lain.
            </p>

            <a href="{{ route('backup.restore.form') }}" class="text-sm text-ios-blue hover:underline">&larr; Kembali ke halaman Backup &amp; Restore</a>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            @forelse ($daftarFile as $file)
                <div class="flex items-center justify-between gap-3 p-4 {{ ! $loop->last ? 'border-b border-ios-border' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ios-label truncate">{{ $file['nama'] }}</p>
                        <p class="text-xs text-ios-secondary">{{ $file['dibuat']->format('d M Y, H:i') }} &middot; {{ $file['ukuran_kb'] }} KB</p>
                    </div>
                    <a href="{{ route('backup.otomatis.unduh', $file['nama']) }}" class="shrink-0 inline-flex items-center gap-1.5 bg-ios-blue text-white text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-ios-blue-hover transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh
                    </a>
                </div>
            @empty
                <div class="p-10 text-center text-ios-secondary text-sm">
                    Belum ada backup otomatis. Pastikan scheduler/cron sudah berjalan.
                </div>
            @endforelse
        </div>

    </div>
</x-app-layout>