<x-app-layout>
    <x-slot name="header">Riwayat Unggah</x-slot>

    <div class="space-y-4">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-ios-secondary text-xs uppercase tracking-wide">
                    <th class="px-4 py-3 min-w-[110px]">Tanggal</th>
                    <th class="px-4 py-3 min-w-[110px]">Diunggah oleh</th>
                    <th class="px-4 py-3 min-w-[160px]">File usaha</th>
                    <th class="px-4 py-3 min-w-[160px]">File keluarga</th>
                    <th class="px-4 py-3 min-w-[70px]">Baru</th>
                    <th class="px-4 py-3 min-w-[90px]">Diperbarui</th>
                    <th class="px-4 py-3 min-w-[100px]">Selesai di fasih</th>
                    <th class="px-4 py-3 min-w-[130px]">Aksi</th>
                </tr>
            </thead>
            <tbody>
                    @forelse ($riwayat as $satu)
                        <tr class="border-t border-ios-border">
                            <td class="px-4 py-3 text-ios-secondary truncate">{{ $satu->tanggal_data->format('d-m-Y') }}</td>
                            <td class="px-4 py-3 font-medium text-ios-label truncate" title="{{ $satu->pengunggah->name }}">{{ $satu->pengunggah->name }}</td>
                            <td class="px-4 py-3 text-ios-secondary truncate" title="{{ $satu->nama_file_usaha }}">{{ $satu->nama_file_usaha }}</td>
                            <td class="px-4 py-3 text-ios-secondary truncate" title="{{ $satu->nama_file_keluarga }}">{{ $satu->nama_file_keluarga }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ios-green/15 text-ios-green">{{ $satu->jumlah_baru }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ios-blue/15 text-ios-blue">{{ $satu->jumlah_diperbarui }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-ios-secondary">{{ $satu->jumlah_selesai_di_fasih }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('riwayat-unggah.show', $satu) }}" class="text-ios-blue font-medium hover:underline">Lihat</a>
                                    <form method="POST" action="{{ route('riwayat-unggah.destroy', $satu) }}" onsubmit="return confirm('Yakin mau hapus riwayat unggah ini? Data yang sudah masuk lewat unggahan ini bisa ikut kehapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-ios-red font-medium hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-ios-secondary">Belum ada riwayat unggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $riwayat->links() }}

    </div>
</x-app-layout>