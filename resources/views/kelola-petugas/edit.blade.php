<x-app-layout>
    <x-slot name="header">Edit Petugas</x-slot>

    <div class="max-w-lg space-y-4">
        <x-back-button href="{{ route('kelola-petugas.index') }}">Kembali</x-back-button>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <form method="POST" action="{{ route('kelola-petugas.update', $petugas) }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $petugas->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="kategori_petugas" value="Kategori" />
                    <select id="kategori_petugas" name="kategori_petugas" class="block mt-1 w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue" required>
                        <option value="pml" {{ old('kategori_petugas', $petugas->kategori_petugas) === 'pml' ? 'selected' : '' }}>PML</option>
                        <option value="ppl" {{ old('kategori_petugas', $petugas->kategori_petugas) === 'ppl' ? 'selected' : '' }}>PPL</option>
                    </select>
                    <x-input-error :messages="$errors->get('kategori_petugas')" class="mt-2" />
                </div>

                <div class="bg-gray-50 rounded-xl p-3 text-xs text-ios-secondary">
                    Email: {{ $petugas->email }} (tidak bisa diubah dari sini)
                </div>

                <x-primary-button>Simpan perubahan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>