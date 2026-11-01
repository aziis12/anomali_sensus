<x-app-layout>
    <x-slot name="header">Detail Anomali</x-slot>

    <div class="max-w-4xl space-y-4">
        <x-back-button href="{{ route('data-anomali.index') }}">Kembali ke daftar</x-back-button>

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="text-xs text-ios-secondary uppercase tracking-wide mb-1">{{ ucfirst($dataAnomali->kategori) }}</p>
                    <h3 class="text-lg font-display font-semibold text-ios-label">{{ $dataAnomali->nama_entitas }}</h3>
                </div>
                @if ($user->isPegawai())
    <a href="{{ $dataAnomali->link_fasih }}" target="_blank" class="inline-flex items-center gap-2 bg-ios-blue text-white text-sm px-4 py-2 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors shrink-0">
        Buka Link Fasih
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
    </a>
@endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm border-t border-ios-border pt-4">
                <div>
                    <p class="text-ios-secondary text-xs mb-0.5">IDSUBSLS</p>
                    <p class="text-ios-label font-medium">{{ $dataAnomali->idsubsls }}</p>
                </div>
                <div>
                    <p class="text-ios-secondary text-xs mb-0.5">PML</p>
                    <p class="text-ios-label font-medium">{{ $dataAnomali->pml ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-ios-secondary text-xs mb-0.5">PPL</p>
                    <p class="text-ios-label font-medium">{{ $dataAnomali->ppl ?: '—' }}</p>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <p class="text-ios-secondary text-xs mb-0.5">Nama Anomali</p>
                    <p class="text-ios-label font-medium">{{ $dataAnomali->nama_anomali_fasih ?: '—' }}</p>
                </div>
            </div>
        </div>

        @if ($dataAnomali->data_spesifik)
            <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
                <h4 class="text-sm font-semibold text-ios-label mb-3">Data tambahan dari fasih</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    @foreach ($dataAnomali->data_spesifik as $kunci => $nilai)
                        <div>
                            <p class="text-ios-secondary text-xs mb-0.5">{{ ucfirst($kunci) }}</p>
                            <p class="text-ios-label">{{ $nilai }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h4 class="text-sm font-semibold text-ios-label mb-4">Status penanganan</h4>

            <form method="POST" action="{{ route('data-anomali.update', $dataAnomali) }}" class="space-y-4">
                @csrf
                @method('PUT')

                @if ($user->isPegawai())
                    <div>
                        <x-input-label for="status_assignment_fasih" value="Status assignment dalam fasih" />
                        <input type="text" id="status_assignment_fasih" name="status_assignment_fasih" value="{{ old('status_assignment_fasih', $dataAnomali->status_assignment_fasih) }}" class="block mt-1 w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue">
                    </div>

                    <div>
                        <x-input-label for="perlu_konfirm_petugas" value="Perlu konfirm petugas" />
                        <textarea id="perlu_konfirm_petugas" name="perlu_konfirm_petugas" rows="3" class="block mt-1 w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue">{{ old('perlu_konfirm_petugas', $dataAnomali->perlu_konfirm_petugas) }}</textarea>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 text-sm">
                        <p class="text-ios-secondary text-xs mb-1">Tanggapan dari petugas</p>
                        <p class="text-ios-label">{{ $dataAnomali->tanggapan_petugas ?: 'Belum ada tanggapan.' }}</p>
                    </div>

                    <div>
                        <x-input-label for="status_assignment" value="Penyelesaian anomali" />
                        <select id="status_assignment" name="status_assignment" class="block mt-1 w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue" required>
                            <option value="belum" {{ old('status_assignment', $dataAnomali->status_assignment) === 'belum' ? 'selected' : '' }}>Belum</option>
                            <option value="sudah" {{ old('status_assignment', $dataAnomali->status_assignment) === 'sudah' ? 'selected' : '' }}>Sudah</option>
                        </select>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-xl p-4 text-sm">
                        <p class="text-ios-secondary text-xs mb-1">Perlu konfirm dari pegawai</p>
                        <p class="text-ios-label">{{ $dataAnomali->perlu_konfirm_petugas ?: 'Belum ada catatan dari pegawai.' }}</p>
                    </div>

                    <div>
                        <x-input-label for="tanggapan_petugas" value="Tanggapan dari petugas" />
                        <textarea id="tanggapan_petugas" name="tanggapan_petugas" rows="5" class="block mt-1 w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue">{{ old('tanggapan_petugas', $dataAnomali->tanggapan_petugas) }}</textarea>
                    </div>
                @endif

                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>