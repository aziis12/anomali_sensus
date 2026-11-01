<x-app-layout>
    <x-slot name="header">Impor Data Lama</x-slot>

    <div class="max-w-2xl space-y-5">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('ada_hasil_detail'))
    <div class="mt-2">
        <a href="{{ route('impor-data-lama.hasil') }}" target="_blank"
           class="inline-flex items-center gap-1 text-ios-blue text-sm font-medium hover:underline">
            Lihat daftar nama lengkap (diisi/dilewati/tidak ketemu) →
        </a>
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
            <h3 class="text-base font-semibold text-ios-label mb-1">Impor tanggapan dari file Excel lama</h3>
            <p class="text-sm text-ios-secondary mb-4">
                Dipakai satu kali sebelum web mulai dipakai, buat mindahin isian (Status Assignment, Perlu Konfirm, Penyelesaian, Tanggapan) dari file Excel manual lama ke data yang sudah ada di web sekarang. Dicocokkan berdasarkan Nama Anomali + Link Fasih. Kolom yang di web sudah terisi tidak akan ditimpa.
            </p>

            <form method="POST" action="{{ route('impor-data-lama.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="file_lama" value="File Excel lama (sudah ada isian tanggapan)" />
                    <input id="file_lama" name="file_lama" type="file" accept=".xlsx" required
                        class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                </div>

                <x-primary-button>Impor sekarang</x-primary-button>
            </form>
        </div>

    </div>
</x-app-layout>