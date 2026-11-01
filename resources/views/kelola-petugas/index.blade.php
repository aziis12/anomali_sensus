<x-app-layout>
    <x-slot name="header">Kelola Petugas</x-slot>

    <div class="space-y-4">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-4">Buat akun petugas baru</h3>

            <form method="POST" action="{{ route('kelola-petugas.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Kata sandi" />
                    <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <x-primary-button>Buat akun</x-primary-button>
            </form>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm text-ios-secondary mb-1">Cari nama</label>
                    <input type="text" name="cari" value="{{ $kataCari }}" class="w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue" placeholder="Ketik lalu enter...">
                </div>

                <button type="submit" class="bg-ios-blue text-white text-sm px-5 py-2.5 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors">Terapkan</button>
            </form>
        </div>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[720px] w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-ios-secondary text-xs uppercase tracking-wide">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Data tanggung jawab</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($petugas as $satu)
                            <tr class="border-t border-ios-border">
                                <td class="px-4 py-3 font-medium text-ios-label">{{ $satu->name }}</td>
                                <td class="px-4 py-3 text-ios-secondary">{{ $satu->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ios-blue/15 text-ios-blue">{{ strtoupper($satu->kategori_petugas) }}</span>
                                </td>
                                <td class="px-4 py-3 text-ios-secondary">{{ $satu->jumlah_data }} baris</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $satu->status === 'aktif' ? 'bg-ios-green/15 text-ios-green' : 'bg-gray-100 text-ios-secondary' }}">
                                        {{ ucfirst($satu->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('kelola-petugas.edit', $satu) }}" class="text-ios-blue font-medium hover:underline">Edit</a>

                                        @if ($satu->status === 'aktif')
                                            <form method="POST" action="{{ route('kelola-petugas.nonaktifkan', $satu) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-ios-amber font-medium hover:underline">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('kelola-petugas.aktifkan', $satu) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-ios-green font-medium hover:underline">Aktifkan</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('kelola-petugas.destroy', $satu) }}" onsubmit="return confirm('Yakin hapus akun petugas ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-ios-red font-medium hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-ios-secondary">Belum ada akun petugas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $petugas->links() }}

    </div>
</x-app-layout>