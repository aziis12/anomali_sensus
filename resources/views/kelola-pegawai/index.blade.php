<x-app-layout>
    <x-slot name="header">Kelola Pegawai</x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Buat akun pegawai baru</h3>

                <form method="POST" action="{{ route('kelola-pegawai.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
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

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-[640px] w-full text-sm">
            <thead>
                <tr class="bg-gray-100 text-left text-gray-600">
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                        @forelse ($pegawai as $satu)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $satu->name }}</td>
                                <td class="px-4 py-2">{{ $satu->email }}</td>
                                <td class="px-4 py-2 capitalize">{{ $satu->status }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        @if ($satu->status === 'aktif')
                                            <form method="POST" action="{{ route('kelola-pegawai.nonaktifkan', $satu) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-red-600 underline">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('kelola-pegawai.aktifkan', $satu) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-green-600 underline">Aktifkan</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('kelola-pegawai.destroy', $satu) }}" onsubmit="return confirm('Yakin mau hapus akun {{ $satu->name }}? Tindakan ini gak bisa dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada akun pegawai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>