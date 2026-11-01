<x-app-layout>
    <x-slot name="header">Monitoring Progress</x-slot>

    <div class="space-y-6">

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border overflow-hidden">
            <div class="p-5 border-b border-ios-border">
                <h3 class="text-base font-semibold text-ios-label">Tugas yang Kamu Kerjakan</h3>
                <p class="text-sm text-ios-secondary">Kombinasi PML/PPL yang pernah kamu proses, otomatis terekam saat kamu menyimpan data.</p>
            </div>

            @forelse ($kombinasiDikerjakan as $kombo)
                <div class="p-5 border-b border-ios-border last:border-b-0">
                    <p class="text-sm text-ios-secondary mb-1">
                        {{ $kombo->pml ? 'PML: '.$kombo->pml : '' }}
                        {{ $kombo->pml && $kombo->ppl ? ' — ' : '' }}
                        {{ $kombo->ppl ? 'PPL: '.$kombo->ppl : '' }}
                    </p>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-ios-blue rounded-full" style="width: {{ $kombo->persentase }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-ios-label w-12">{{ $kombo->persentase }}%</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xl font-display font-semibold text-ios-label">{{ $kombo->total }}</p>
                            <p class="text-xs text-ios-secondary mt-1">Total</p>
                        </div>
                        <div class="bg-ios-green/10 rounded-xl p-3">
                            <p class="text-xl font-display font-semibold text-ios-green">{{ $kombo->selesai }}</p>
                            <p class="text-xs text-ios-secondary mt-1">Selesai</p>
                        </div>
                        <div class="bg-ios-red/10 rounded-xl p-3">
                            <p class="text-xl font-display font-semibold text-ios-red">{{ $kombo->belum }}</p>
                            <p class="text-xs text-ios-secondary mt-1">Belum</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-ios-secondary">
                    Kamu belum pernah memproses data apa pun.
                </div>
            @endforelse
        </div>

        {{-- <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-1">Cek Kombinasi Lain</h3>
            <p class="text-sm text-ios-secondary mb-4">Mau intip progress kombinasi PML+PPL lain (belum tentu punyamu).</p>

            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-ios-secondary mb-1">PML</label>
                    <select name="pml" onchange="this.form.submit()" class="border-ios-border rounded-xl text-sm text-ios-label focus:border-ios-blue focus:ring-ios-blue min-w-[180px]">
                        <option value="">Semua PML</option>
                        @foreach ($daftarPml as $nama)
                            <option value="{{ $nama }}" {{ $pmlAktif === $nama ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-ios-secondary mb-1">PPL</label>
                    <select name="ppl" onchange="this.form.submit()" class="border-ios-border rounded-xl text-sm text-ios-label focus:border-ios-blue focus:ring-ios-blue min-w-[180px]">
                        <option value="">Semua PPL</option>
                        @foreach ($daftarPpl as $nama)
                            <option value="{{ $nama }}" {{ $pplAktif === $nama ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div> --}}

        @if ($progress)
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
                <p class="text-sm text-ios-secondary mb-1">
                    {{ $progress->pml ? 'PML: '.$progress->pml : 'Semua PML' }}
                    &mdash;
                    {{ $progress->ppl ? 'PPL: '.$progress->ppl : 'Semua PPL' }}
                </p>
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-ios-blue rounded-full" style="width: {{ $progress->persentase }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-ios-label w-12">{{ $progress->persentase }}%</span>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-2xl font-display font-semibold text-ios-label">{{ $progress->total }}</p>
                        <p class="text-xs text-ios-secondary mt-1">Total</p>
                    </div>
                    <div class="bg-ios-green/10 rounded-xl p-4">
                        <p class="text-2xl font-display font-semibold text-ios-green">{{ $progress->selesai }}</p>
                        <p class="text-xs text-ios-secondary mt-1">Sudah selesai</p>
                    </div>
                    <div class="bg-ios-red/10 rounded-xl p-4">
                        <p class="text-2xl font-display font-semibold text-ios-red">{{ $progress->belum }}</p>
                        <p class="text-xs text-ios-secondary mt-1">Belum selesai</p>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>