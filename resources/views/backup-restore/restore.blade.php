<x-app-layout>
    <x-slot name="header">Backup &amp; Restore Data</x-slot>

    <div class="max-w-2xl space-y-5">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-ios-red/10 border border-ios-red/20 text-red-800 rounded-ios p-4 text-sm space-y-1">
                @foreach ($errors->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-1">Unduh backup</h3>
            <p class="text-sm text-ios-secondary mb-4">
                Berisi seluruh data anomali beserta tanggapan petugas, status penyelesaian, dan status assignment fasih.
                Simpan file ini di tempat terpisah dari server (laptop, Google Drive, dsb) secara berkala.
            </p>
            <a href="{{ route('backup.unduh') }}" class="inline-flex items-center bg-ios-blue text-white text-sm px-5 py-2.5 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors">
                Unduh Backup Sekarang
            </a>
            <a href="{{ route('backup.otomatis.daftar') }}" class="inline-flex items-center text-sm text-ios-blue hover:underline ml-3">
                Lihat backup otomatis tersimpan di server &rarr;
            </a>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-red/30 p-6">
            <h3 class="text-base font-semibold text-ios-red mb-1">Restore dari backup</h3>
            <p class="text-sm text-ios-secondary mb-4">
                <strong>Peringatan:</strong> proses ini akan menimpa data yang sudah ada (dicocokkan lewat id) dan
                membuat ulang data yang hilang. Cuma dipakai kalau memang ada kehilangan data (server bermasalah, dsb).
                <strong>Jangan</strong> upload file "Ekspor Excel" biasa atau file dari pusat di sini — hanya file
                hasil "Unduh Backup" di atas yang valid.
            </p>

            <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="file_backup" value="File backup (.xlsx)" />
                    <input id="file_backup" name="file_backup" type="file" accept=".xlsx" required
                        class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-red/10 file:text-ios-red file:text-sm">
                </div>

                <div>
                    <x-input-label for="konfirmasi" value='Ketik "PULIHKAN" untuk konfirmasi' />
                    <input id="konfirmasi" name="konfirmasi" type="text" required
                        placeholder="PULIHKAN"
                        class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5">
                </div>

                <button type="submit" class="bg-ios-red text-white text-sm px-5 py-2.5 rounded-xl font-medium hover:opacity-90 transition-colors">
                    Pulihkan Data dari Backup
                </button>
            </form>
        </div>

    </div>
</x-app-layout>