<x-guest-layout>
    <div class="text-center mb-4">
        <h2 class="text-lg font-semibold text-ios-label">Pilih nama PML kamu</h2>
        <p class="text-sm text-ios-secondary mt-1">Akun petugas ini dipakai bareng-bareng. Pilih dulu kamu mengerjakan data atas nama siapa.</p>
    </div>

    <form method="POST" action="{{ route('pilih-pml.store') }}">
        @csrf

        <x-select-custom
            name="nama_pml"
            :options="$daftarPml->mapWithKeys(fn ($nama) => [$nama => $nama])->all()"
            :selected="$pmlAktif"
            label="Nama PML"
            placeholder="Pilih nama"
            :auto-submit="false"
            width="w-full"
        />

        <div class="mt-4">
            <x-primary-button class="w-full justify-center">Lanjutkan</x-primary-button>
        </div>
    </form>
</x-guest-layout>