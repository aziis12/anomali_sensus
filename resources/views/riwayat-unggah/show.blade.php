<x-app-layout>
    <x-slot name="header">Detail Riwayat Unggah</x-slot>

    <div class="space-y-4">
        <x-back-button href="{{ route('riwayat-unggah.index') }}">Kembali</x-back-button>

        @if ($errors->has('hapus'))
            <div class="bg-ios-amber/10 border border-ios-amber/30 text-amber-800 rounded-ios p-4 text-sm space-y-3">
                <p>{{ $errors->first('hapus') }}</p>
                <form method="POST" action="{{ route('riwayat-unggah.destroy', $riwayat) }}">
                    @csrf @method('DELETE')
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="konfirmasi_hapus_paksa" value="1" class="rounded border-ios-border text-ios-red focus:ring-ios-red">
                        Saya paham dan tetap ingin menghapus semua data ini permanen
                    </label>
                    <button type="submit" class="mt-3 bg-ios-red text-white text-sm px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition-colors">
                        Hapus paksa sekarang
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6 grid grid-cols-2 sm:grid-cols-5 gap-4 text-sm">
            <div>
                <p class="text-ios-secondary">Tanggal</p>
                <p class="font-medium text-ios-label">{{ $riwayat->created_at->format('d-m-Y H:i') }}</p>
            </div>
            <div>
                <p class="text-ios-secondary">Diunggah oleh</p>
                <p class="font-medium text-ios-label">{{ $riwayat->pengunggah->name }}</p>
            </div>
            <div>
                <p class="text-ios-secondary">Baru</p>
                <p class="font-medium text-ios-green">{{ $riwayat->jumlah_baru }}</p>
            </div>
            <div>
                <p class="text-ios-secondary">Diperbarui</p>
                <p class="font-medium text-ios-blue">{{ $riwayat->jumlah_diperbarui }}</p>
            </div>
            <div>
                <p class="text-ios-secondary">Sudah dikerjakan</p>
                <p class="font-medium {{ $jumlahSudahDikerjakan > 0 ? 'text-ios-amber' : 'text-ios-secondary' }}">{{ $jumlahSudahDikerjakan }}</p>
            </div>
        </div>

        @if (! $errors->has('hapus'))
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4">
                <form method="POST" action="{{ route('riwayat-unggah.destroy', $riwayat) }}" onsubmit="return confirm('Yakin hapus riwayat ini beserta seluruh data anomali di dalamnya secara permanen?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-ios-red text-sm font-medium hover:underline">
                        Hapus riwayat ini beserta semua datanya
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">IDSUBSLS</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">PML</th>
                            <th class="px-4 py-3">PPL</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $baris)
                            <tr class="border-t border-ios-border">
                                <td class="px-4 py-3 font-medium text-ios-label">{{ $baris->nama_entitas }}</td>
                                <td class="px-4 py-3 text-ios-secondary">{{ $baris->idsubsls }}</td>
                                <td class="px-4 py-3 text-ios-label capitalize">{{ $baris->kategori }}</td>
                                <td class="px-4 py-3 text-ios-label">{{ $baris->pml }}</td>
                                <td class="px-4 py-3 text-ios-label">{{ $baris->ppl }}</td>
                                <td class="px-4 py-3 capitalize text-ios-label">{{ $baris->status_assignment }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-ios-secondary">Tidak ada data pada riwayat ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $data->links() }}
    </div>
</x-app-layout>   