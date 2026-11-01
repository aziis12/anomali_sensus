{{--
    Komponen progress bar unggah. Dipakai DI DALAM <form x-data="formUnggah">,
    jadi semua variabel (status, persen, persenProses, sisaLabel, pesan, errors)
    dibaca dari komponen Alpine "formUnggah" di view dashboard superadmin.

    Simpan sebagai: resources/views/components/progress-unggah.blade.php
    Dipakai dengan: <x-progress-unggah />
--}}
<div x-show="status !== 'idle'" x-cloak class="space-y-2" aria-live="polite">

    {{-- sedang mengunggah / memproses --}}
    <div x-show="status === 'mengunggah' || status === 'memproses'">
        <div class="flex justify-between text-xs text-ios-secondary mb-1">
            <span x-text="status === 'mengunggah' ? 'Mengunggah file…' : 'Memproses data di server…'"></span>
            <span x-text="status === 'mengunggah' ? persen + '%' : sisaLabel"></span>
        </div>

        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-ios-blue rounded-full transition-all duration-500 ease-linear"
                 :style="'width:' + (status === 'memproses' ? persenProses : persen) + '%'"></div>
        </div>

        <p x-show="status === 'memproses'" class="text-xs text-ios-secondary mt-1">
            File sudah terkirim. Waktu di atas hanya perkiraan (berdasarkan ukuran file dan proses sebelumnya),
            bisa lebih cepat atau lebih lama. Jangan tutup atau muat ulang halaman ini.
        </p>
    </div>

    {{-- berhasil --}}
    <div x-show="status === 'selesai'"
         class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
        <p x-text="pesan"></p>
    </div>

    {{-- gagal --}}
    <div x-show="status === 'gagal'"
         class="bg-ios-red/10 border border-ios-red/20 text-red-800 rounded-ios p-4 text-sm space-y-1">
        <template x-for="e in errors" :key="e">
            <p x-text="e"></p>
        </template>
    </div>
</div>