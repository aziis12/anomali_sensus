<x-app-layout>
    <x-slot name="header">Data Anomali</x-slot>

    <div class="space-y-4">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($user->isPetugas() && ! $pmlDikerjakanPetugas)
            <div class="bg-ios-amber/10 border border-ios-amber/20 text-amber-800 rounded-ios p-4 text-sm">
                Kamu belum memilih kategori PML. Pilih di filter <strong>"Kategori PML"</strong> di bawah supaya bisa mulai mengisi tanggapan pada baris data milikmu. Kamu tetap bisa lihat semua data lain sebagai referensi.
            </div>
        @elseif ($user->isPetugas() && $pmlDikerjakanPetugas)
            <div class="bg-ios-blue/10 border border-ios-blue/20 text-blue-800 rounded-ios p-4 text-sm">
                Kamu sedang mengerjakan data atas nama PML <strong>{{ $pmlDikerjakanPetugas }}</strong>. Ganti lewat filter "Kategori PML" di bawah kalau mau pindah.
            </div>
        @endif

        @php
            $jumlahFilterAktif = collect(request()->except(['page', 'per_halaman']))
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->count();
        @endphp

        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-4 overflow-visible"
             x-data="{ filterTerbuka: {{ $jumlahFilterAktif }} > 0 }">

            <button type="button"
                @click="filterTerbuka = !filterTerbuka"
                class="lg:hidden w-full flex items-center justify-between text-sm font-medium text-ios-label">
                <span class="flex items-center gap-2">
                    Filter
                    @if ($jumlahFilterAktif > 0)
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold bg-ios-blue text-white">
                            {{ $jumlahFilterAktif }}
                        </span>
                    @endif
                </span>
                <svg class="w-5 h-5 text-ios-secondary transition-transform" :class="filterTerbuka && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <form method="GET"
                x-show="filterTerbuka"
                x-cloak
                x-transition
                class="relative z-20 grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3 mt-4 lg:!flex lg:mt-0">

                @if ($user->isPegawai() || $user->isPetugas())
                    <x-select-custom
                        name="pml"
                        label="{{ $user->isPetugas() ? 'Kategori PML (data saya)' : 'PML' }}"
                        :selected="$pmlAktif"
                        placeholder="Semua"
                        :options="collect($daftarPml)->mapWithKeys(fn ($nama) => [$nama => $nama])->prepend('Semua', '')->toArray()"
                    />
                @endif

                @if ($user->isPegawai())
                    <x-select-custom
                        name="ppl"
                        label="PPL"
                        :selected="$pplAktif"
                        placeholder="Semua"
                        :options="collect($daftarPpl)->mapWithKeys(fn ($nama) => [$nama => $nama])->prepend('Semua', '')->toArray()"
                    />
                @endif

                <x-select-custom
                    name="unggahan"
                    label="Periode"
                    :selected="$unggahanAktif"
                    placeholder="Semua periode"
                    width="sm:w-56"
                    :options="collect($daftarUnggahan)->mapWithKeys(fn ($satu) => [$satu->id => ($satu->tanggal_data?->format('d M Y') ?? $satu->created_at->format('d M Y, H:i'))])->prepend('Semua periode', '')->toArray()"
                />

                <x-select-custom
                    name="kecamatan"
                    label="Kecamatan"
                    :selected="$kecamatanAktif"
                    placeholder="Semua"
                    :options="collect($daftarKecamatan)->mapWithKeys(fn ($nama) => [$nama => $nama])->prepend('Semua', '')->toArray()"
                />

                <x-select-custom
                    name="desa"
                    label="Desa/Kel"
                    :selected="$desaAktif"
                    placeholder="Semua"
                    :options="collect($daftarDesa)->mapWithKeys(fn ($nama) => [$nama => $nama])->prepend('Semua', '')->toArray()"
                />

                <x-select-custom
                    name="tindak_lanjut"
                    label="Tindak Lanjut"
                    :selected="$tindakLanjutAktif"
                    placeholder="Belum ditindaklanjuti"
                    :options="collect($daftarTindakLanjut)->mapWithKeys(fn ($nilai) => [$nilai => $nilai])->prepend('Semua', 'semua')->prepend('Belum ditindaklanjuti', 'belum')->toArray()"
                />

                <x-select-custom
                    name="kategori_anomali"
                    label="Kategori Anomali"
                    :selected="request('kategori_anomali')"
                    placeholder="Semua"
                    :options="collect($daftarKategoriAnomali)->mapWithKeys(fn ($nilai) => [$nilai => $nilai])->prepend('Semua', '')->toArray()"
                />

                <x-select-custom
                    name="penyelesaian"
                    label="Penyelesaian"
                    :selected="$penyelesaianAktif"
                    placeholder="Semua"
                    :options="[
                        '' => 'Semua',
                        'belum' => 'Belum',
                        'sudah' => 'Sudah',
                    ]"
                />

                <x-select-custom
                    name="per_halaman"
                    label="Per halaman"
                    :selected="(string) request('per_halaman', 25)"
                    placeholder="25"
                    :options="['25' => '25', '50' => '50', '100' => '100']"
                />

                <div class="col-span-2 sm:col-span-1 sm:flex-1 min-w-0 sm:min-w-[160px]">
                    <label class="block text-xs text-ios-secondary mb-1">Cari</label>
                    <input type="text" name="cari" value="{{ $kataCari }}" class="w-full border-ios-border rounded-xl text-sm focus:border-ios-blue focus:ring-ios-blue" placeholder="Nama, idsubsls...">
                </div>

                <div class="col-span-2 sm:col-span-1 flex gap-2">
                    <button type="submit" class="flex-1 sm:flex-none bg-ios-blue text-white text-sm px-5 py-2.5 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors">Terapkan</button>

                    <a href="{{ route('data-anomali.ekspor', request()->query()) }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-white border border-ios-border text-ios-label text-sm px-5 py-2.5 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                        Ekspor Excel
                    </a>
                </div>
            </form>
        </div>

        <p class="text-sm text-ios-secondary px-1">
            Menampilkan {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data
        </p>

        @php
            $warnaSorot = false;
            $nomorUrutTampil = 0;
        @endphp

        <div class="space-y-4">
            @forelse ($grup as $linkFasih => $kelompokKelompok)
                @php
                    $warnaSorot = ! $warnaSorot;
                    $kelasBg = $warnaSorot ? 'bg-ios-blue/10 border-ios-blue/40' : 'bg-ios-amber/10 border-ios-amber/40';
                    $kelasAksen = $warnaSorot ? 'border-l-ios-blue' : 'border-l-ios-amber';
                    $kelasDivider = $warnaSorot ? 'border-ios-blue/30' : 'border-ios-amber/30';
                    $kelasDivideWarna = $warnaSorot ? 'divide-ios-blue/30' : 'divide-ios-amber/30';
                    $kelasLabelGrup = $warnaSorot ? 'bg-ios-blue text-white' : 'bg-ios-amber text-white';

                    $lebihDariSatuKelompok = $kelompokKelompok->count() > 1;

                    $wakilPerKelompok = $kelompokKelompok->map(fn ($c) => $c->first());
                    $lokasiSama = $lebihDariSatuKelompok
                        && $wakilPerKelompok->pluck('nama_kecamatan')->unique()->count() <= 1
                        && $wakilPerKelompok->pluck('nama_desa')->unique()->count() <= 1;
                    $pmlPplSama = $lebihDariSatuKelompok
                        && $wakilPerKelompok->pluck('pml')->unique()->count() <= 1
                        && $wakilPerKelompok->pluck('ppl')->unique()->count() <= 1;
                    $wakilBox = $wakilPerKelompok->first();
                @endphp

                <div class="rounded-ios shadow-sm border border-l-4 {{ $kelasBg }} {{ $kelasAksen }} p-3">

                    @if ($lebihDariSatuKelompok)
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $kelasLabelGrup }}">
                                {{ $kelompokKelompok->count() }} baris data &middot; Link Fasih sama
                            </span>

                            @if ($user->isPegawai())
                                <a href="{{ $linkFasih }}" target="_blank" class="inline-flex items-center gap-1 text-ios-blue text-xs font-medium hover:underline shrink-0 bg-white px-2 py-1 rounded-lg border border-ios-border">
                                    Link Fasih
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($lokasiSama || $pmlPplSama)
                        <div class="flex flex-wrap items-center gap-1 mb-2 pb-2 border-b {{ $kelasDivider }}">
                            @if ($lokasiSama)
                                <span class="text-xs text-ios-secondary">{{ $wakilBox->nama_kecamatan }}, {{ $wakilBox->nama_desa }}</span>
                            @endif
                            @if ($pmlPplSama && $wakilBox->pml)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-ios-amber/15 text-ios-amber">PML: {{ $wakilBox->pml }}</span>
                            @endif
                            @if ($pmlPplSama && $wakilBox->ppl)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-ios-blue/15 text-ios-blue">PPL: {{ $wakilBox->ppl }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="space-y-3 divide-y {{ $kelasDivideWarna }}">
                        @foreach ($kelompokKelompok as $kelompokBarisExcel => $daftarAnomali)
                            @php
                                $nomorUrutTampil++;

                                $satuPertama = $daftarAnomali->first();
                                $jumlahEntitas = $daftarAnomali->pluck('nama_entitas')->unique()->count();

                                $kategoriUnik = $daftarAnomali->pluck('kategori')->unique();
                                if ($kategoriUnik->count() > 1) {
                                    $namaGabungan = $daftarAnomali->groupBy('kategori')
                                        ->map(fn ($g, $kategori) => '['.ucfirst($kategori).': '.$g->pluck('nama_entitas')->unique()->implode(', ').']')
                                        ->implode(' ');
                                } else {
                                    $namaGabungan = $daftarAnomali->pluck('nama_entitas')->unique()->implode(', ');
                                }

                                $pmlGrup = $satuPertama->pml;
                                $bisaDiisiPetugas = $user->isPetugas() && $pmlGrup === $pmlDikerjakanPetugas;

                                $tanggapanPetugasAwal = $daftarAnomali->pluck('tanggapan_petugas')->filter()->first();

                                $semuaSudahSelesai = $daftarAnomali->every(fn ($b) => $b->status_assignment === 'sudah');

                                $semuaBelumTindakLanjut = $daftarAnomali->every(
                                    fn ($b) => empty($b->tindak_lanjut_fasih) || str_contains(strtolower($b->tindak_lanjut_fasih), 'belum')
                                );
                                $semuaSudahTindakLanjut = $daftarAnomali->every(
                                    fn ($b) => ! empty($b->tindak_lanjut_fasih) && ! str_contains(strtolower($b->tindak_lanjut_fasih), 'belum')
                                );
                                if ($semuaSudahTindakLanjut) {
                                    $statusTindakLanjutGrup = ['label' => 'Sudah ditindaklanjuti', 'kelas' => 'bg-ios-green text-white'];
                                } elseif ($semuaBelumTindakLanjut) {
                                    $statusTindakLanjutGrup = ['label' => 'Belum ditindaklanjuti', 'kelas' => 'bg-ios-amber text-white'];
                                } else {
                                    $statusTindakLanjutGrup = ['label' => 'Sebagian ditindaklanjuti', 'kelas' => 'bg-ios-blue text-white'];
                                }

                                $tanggalResponGrup = $daftarAnomali->pluck('tanggal_respon')->filter()->sortDesc()->first();

                                $semuaSudahDisetujuiPegawai = $daftarAnomali->every(fn ($b) => $b->status_assignment === 'sudah');
                            @endphp

                            {{-- data-kelompok-id: penanda batas "kartu" ini, dipakai JS untuk tahu elemen
                                 mana saja yang harus diupdate setelah simpan sukses (lihat script di bawah) --}}
                            <div class="pt-3 first:pt-0" data-kelompok-id="{{ $kelompokBarisExcel }}">

                                <div class="flex items-start justify-between gap-3 pb-2 mb-2 border-b {{ $kelasDivider }}">

                                    <div class="min-w-0 flex-1">

                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] font-bold {{ $kelasLabelGrup }} shrink-0">
                                                {{ $nomorUrutTampil }}
                                            </span>
                                            @if ($satuPertama->idsubsls)
                                                <span class="text-xs text-ios-secondary">{{ $satuPertama->idsubsls }}</span>
                                            @endif
                                        </div>

                                        <p class="font-semibold text-ios-label text-sm mb-1">{{ $namaGabungan }}</p>

                                        @unless ($lokasiSama)
                                            <p class="text-xs text-ios-secondary mb-2">
                                                {{ $satuPertama->nama_kecamatan }}, {{ $satuPertama->nama_desa }}
                                            </p>
                                        @endunless

                                        {{-- Badge "Penyelesaian" atas: class js-status-top + teks dibungkus
                                             span.js-teks supaya bisa diupdate JS setelah simpan sukses --}}
                                        <div class="mb-2">
                                            <span class="js-status-top inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $semuaSudahDisetujuiPegawai ? 'border-ios-green/40 text-ios-green bg-ios-green/15' : 'border-ios-border text-ios-secondary bg-white' }}">
                                                Penyelesaian: <span class="js-teks">{{ $semuaSudahDisetujuiPegawai ? 'Sudah' : 'Belum' }}</span>
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-1">
                                            @unless ($pmlPplSama)
                                                @if ($satuPertama->pml)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-ios-amber/15 text-ios-amber">PML: {{ $satuPertama->pml }}</span>
                                                @endif
                                                @if ($satuPertama->ppl)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-ios-blue/15 text-ios-blue">PPL: {{ $satuPertama->ppl }}</span>
                                                @endif
                                            @endunless
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-white text-ios-secondary border border-ios-border">
                                                {{ $jumlahEntitas }} entitas &middot; {{ $daftarAnomali->count() }} anomali
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 flex flex-col items-end gap-1.5 text-right">
                                        @if (! $lebihDariSatuKelompok && $user->isPegawai())
                                            <a href="{{ $satuPertama->link_fasih }}" target="_blank" class="inline-flex items-center gap-1 text-ios-blue text-xs font-medium hover:underline shrink-0 bg-white px-2 py-1 rounded-lg border border-ios-border whitespace-nowrap">
                                                Link Fasih
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                        @endif

                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold whitespace-nowrap {{ $statusTindakLanjutGrup['kelas'] }}">
                                            @if ($semuaSudahTindakLanjut)
                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5v4.5l3 1.5" />
                                                </svg>
                                            @endif
                                            {{ $statusTindakLanjutGrup['label'] }}
                                        </span>

                                        {{-- Selalu dirender (bukan @if) supaya JS tinggal show/update, gak perlu
                                             bikin elemen baru dari nol setelah simpan. Disembunyikan lewat
                                             style="display:none" kalau memang belum pernah direspon. --}}
                                        <span class="js-direspon-top text-[11px] text-ios-secondary" style="{{ $tanggalResponGrup ? '' : 'display:none' }}">
                                            Direspon <span class="js-teks">{{ $tanggalResponGrup?->format('d M Y') }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="relative bg-white rounded-lg border border-ios-border overflow-visible">

                                    <div class="p-2.5 border-b border-ios-border">
                                        <p class="text-[11px] font-medium text-ios-secondary mb-1">Daftar Anomali</p>
                                        <p class="text-sm text-ios-label leading-snug">
                                            {{ $daftarAnomali->pluck('nama_anomali_fasih')->implode(', ') }}
                                        </p>
                                    </div>

                                    @if ($user->isPegawai())
                                        <form method="POST" action="{{ route('data-anomali.update-grup') }}" class="form-inline-simpan p-2.5 relative z-10">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="kelompok_baris_excel" value="{{ $kelompokBarisExcel }}">

                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-start">
                                                <x-select-custom
                                                    name="status_assignment_fasih"
                                                    label="Status assignment fasih"
                                                    :selected="$satuPertama->status_assignment_fasih"
                                                    placeholder="Belum dipilih"
                                                    :autoSubmit="false"
                                                    :options="[
                                                        '' => 'Belum dipilih',
                                                        'APPROVED BY PENGAWAS' => 'APPROVED BY PENGAWAS',
                                                        'SUBMITTED BY PENCACAH' => 'SUBMITTED BY PENCACAH',
                                                        'REJECTED BY PENGAWAS' => 'REJECTED BY PENGAWAS',
                                                        'COMPLETED BY ADMIN KABUPATEN' => 'COMPLETED BY ADMIN KABUPATEN',
                                                        'EDITED BY ADMIN KABUPATEN' => 'EDITED BY ADMIN KABUPATEN',
                                                        'REVOKED BY PENGAWAS' => 'REVOKED BY PENGAWAS',
                                                        'REJECTED BY ADMIN KABUPATEN' => 'REJECTED BY ADMIN KABUPATEN',
                                                    ]"
                                                    :optionTitles="[
                                                        '' => 'Kosongkan status assignment fasih.',
                                                        'APPROVED BY PENGAWAS' => 'Data telah disetujui oleh pengawas.',
                                                        'SUBMITTED BY PENCACAH' => 'Data telah dikirim oleh pencacah dan menunggu proses berikutnya.',
                                                        'REJECTED BY PENGAWAS' => 'Data ditolak oleh pengawas dan perlu diperbaiki.',
                                                        'COMPLETED BY ADMIN KABUPATEN' => 'Data telah diselesaikan/dituntaskan oleh Admin Kabupaten.',
                                                        'EDITED BY ADMIN KABUPATEN' => 'Data telah diedit oleh Admin Kabupaten.',
                                                        'REVOKED BY PENGAWAS' => 'Persetujuan sebelumnya dicabut oleh pengawas sehingga data kembali memerlukan tindak lanjut.',
                                                        'REJECTED BY ADMIN KABUPATEN' => 'Data ditolak oleh Admin Kabupaten dan memerlukan perbaikan.',
                                                    ]"
                                                />

                                                <div>
                                                    <label class="block text-xs font-medium text-ios-secondary mb-1">Perlu konfirm petugas</label>
                                                    <textarea name="perlu_konfirm_petugas" rows="2"
                                                        class="w-full bg-gray-50 border border-ios-border rounded-lg px-2.5 py-2 text-sm text-ios-label focus:bg-white focus:border-ios-blue focus:ring-1 focus:ring-ios-blue">{{ $satuPertama->perlu_konfirm_petugas }}</textarea>
                                                </div>

                                                <x-select-custom
                                                    name="status_assignment"
                                                    label="Penyelesaian anomali"
                                                    :selected="$satuPertama->status_assignment"
                                                    placeholder="Belum"
                                                    :autoSubmit="false"
                                                    :options="[
                                                        'belum' => 'Belum',
                                                        'sudah' => 'Sudah',
                                                    ]"
                                                    :colorMap="[
                                                        'belum' => 'bg-gray-50 text-ios-secondary border-ios-border',
                                                        'sudah' => 'bg-ios-green/15 text-ios-green border-ios-green/30',
                                                    ]"
                                                />
                                            </div>

                                            {{-- Tanggapan dari petugas: selalu merah, baik sudah ada tanggapan maupun belum --}}
                                            <div class="mt-2.5 bg-ios-red/5 border border-ios-red/20 rounded-lg p-2.5">
                                                <p class="text-xs font-medium text-ios-secondary mb-1">Tanggapan dari petugas</p>
                                                <p class="text-sm text-ios-red font-medium">{{ $tanggapanPetugasAwal ?: 'Belum ada tanggapan.' }}</p>
                                            </div>

                                            <div class="flex items-center gap-3 mt-2.5">
                                                <button type="submit" class="bg-ios-blue text-white text-sm px-5 py-2 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors">
                                                    Simpan
                                                </button>
                                                <span class="teks-status-simpan text-xs"></span>
                                            </div>
                                        </form>
                                    @else
                                        <div class="p-2.5">
                                            <p class="text-xs font-medium text-ios-secondary mb-1">Perlu konfirm dari pegawai</p>
                                            <p class="text-sm text-ios-red font-medium mb-2.5">{{ $satuPertama->perlu_konfirm_petugas ?: 'Belum ada catatan.' }}</p>

                                            @if ($bisaDiisiPetugas)
                                                <form method="POST" action="{{ route('data-anomali.update-grup') }}" class="form-inline-simpan">
                                                    @csrf @method('PUT')
                                                    <input type="hidden" name="kelompok_baris_excel" value="{{ $kelompokBarisExcel }}">

                                                    <label class="block text-xs font-medium text-ios-secondary mb-1">Tanggapan dari petugas</label>
                                                    <textarea name="tanggapan_petugas" rows="3"
                                                        class="w-full bg-gray-50 border border-ios-border rounded-lg px-3 py-2.5 text-sm text-ios-label focus:bg-white focus:border-ios-blue focus:ring-1 focus:ring-ios-blue">{{ $tanggapanPetugasAwal }}</textarea>

                                                    <div class="flex items-center gap-3 mt-2.5">
                                                        <button type="submit" class="bg-ios-blue text-white text-sm px-5 py-2 rounded-xl font-medium hover:bg-ios-blue-hover transition-colors">
                                                            Simpan
                                                        </button>
                                                        <span class="teks-status-simpan text-xs"></span>
                                                    </div>
                                                </form>
                                            @else
                                                {{-- Tanggapan dari petugas: selalu merah, baik sudah ada tanggapan maupun belum --}}
                                                <div class="bg-ios-red/5 border border-ios-red/20 rounded-lg p-2.5">
                                                    <p class="text-xs font-medium text-ios-secondary mb-1">Tanggapan dari petugas</p>
                                                    <p class="text-sm text-ios-red font-medium">{{ $tanggapanPetugasAwal ?: 'Belum ada tanggapan.' }}</p>
                                                    <p class="text-xs text-ios-amber mt-2">
                                                        @if ($pmlDikerjakanPetugas)
                                                            Ini data PML "{{ $pmlGrup }}", bukan "{{ $pmlDikerjakanPetugas }}" yang lagi kamu kerjakan. Ganti filter "Kategori PML" di atas kalau mau isi tanggapan di sini.
                                                        @else
                                                            Pilih dulu "Kategori PML" di filter atas biar bisa mengisi tanggapan.
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <details class="mt-2.5">
                                    <summary class="cursor-pointer text-xs font-medium text-ios-secondary">
                                        Lihat status &amp; data tambahan tiap anomali ({{ $daftarAnomali->count() }})
                                    </summary>
                                    <div class="space-y-2 divide-y divide-ios-border mt-2">
                                        @foreach ($daftarAnomali as $baris)
                                            <div class="{{ ! $loop->first ? 'pt-2' : '' }} text-xs">
                                                <div class="flex items-start gap-2 mb-1">
                                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[9px] font-bold shrink-0 mt-0.5 {{ $baris->kategori === 'usaha' ? 'bg-ios-amber/20 text-ios-amber' : 'bg-ios-blue/15 text-ios-blue' }}" title="{{ ucfirst($baris->kategori) }}">
                                                        {{ $baris->kategori === 'usaha' ? 'U' : 'K' }}
                                                    </span>
                                                    <p class="text-ios-label font-medium">{{ $baris->nama_anomali_fasih }}</p>
                                                </div>

                                                <div class="flex flex-wrap gap-1.5 mb-1.5 ml-6">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ str_contains(strtolower($baris->tindak_lanjut_fasih ?? ''), 'belum') ? 'bg-gray-100 text-ios-secondary' : 'bg-ios-green/15 text-ios-green' }}">
                                                        {{ $baris->tindak_lanjut_fasih ?: 'Tindak lanjut belum tercatat' }}
                                                    </span>

                                                    {{-- Selalu dirender, disembunyikan kalau belum ada tanggal, supaya JS
                                                         tinggal show + isi teks setelah simpan sukses --}}
                                                    <span class="js-direspon-baris inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-ios-green/15 text-ios-green" style="{{ $baris->tanggal_respon ? '' : 'display:none' }}">
                                                        <span class="js-teks">Direspon {{ $baris->tanggal_respon?->format('d M Y') }}</span>
                                                    </span>

                                                    @if ($user->isPetugas())
                                                        <span class="js-status-baris-petugas inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $baris->status_assignment === 'sudah' ? 'bg-ios-green/15 text-ios-green' : 'bg-gray-100 text-ios-secondary' }}">
                                                            <span class="js-teks">Penyelesaian: {{ $baris->status_assignment === 'sudah' ? 'Sudah' : 'Belum' }}</span>
                                                        </span>

                                                        @if ($baris->pml && $baris->pml !== $pmlDikerjakanPetugas)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-gray-100 text-ios-secondary border border-ios-border">Bukan PML yang kamu pilih</span>
                                                        @endif
                                                    @else
                                                        <span class="js-status-baris-pegawai inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $baris->status_assignment === 'sudah' ? 'bg-ios-green/15 text-ios-green' : 'bg-ios-amber/15 text-ios-amber' }}">
                                                            <span class="js-teks">{{ $baris->status_assignment === 'sudah' ? 'Selesai' : 'Belum selesai' }}</span>
                                                        </span>
                                                    @endif
                                                </div>

                                                @if (! empty($baris->data_spesifik))
                                                    <details class="ml-6">
                                                        <summary class="cursor-pointer text-ios-secondary font-medium">Data tambahan dari fasih</summary>
                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-1 mt-1.5">
                                                            @foreach ($baris->data_spesifik as $kunci => $nilai)
                                                                <div>
                                                                    <p class="text-ios-secondary">{{ ucfirst($kunci) }}</p>
                                                                    <p class="text-ios-label">{{ $nilai }}</p>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>

                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-10 text-center text-ios-secondary">
                    Tidak ada data untuk filter ini.
                </div>
            @endforelse
        </div>

        {{ $data->links() }}

    </div>

    @push('scripts')
    <script>
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (! form.classList.contains('form-inline-simpan')) return;

        e.preventDefault();

        const tombol = form.querySelector('button[type="submit"]');
        const teksStatus = form.querySelector('.teks-status-simpan');
        const teksAsli = tombol.textContent;

        tombol.disabled = true;
        tombol.textContent = 'Menyimpan...';
        teksStatus.textContent = '';
        teksStatus.classList.remove('text-ios-green', 'text-ios-red');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' },
            });

            if (! response.ok) throw new Error('Gagal menyimpan');

            teksStatus.textContent = 'Tersimpan.';
            teksStatus.classList.add('text-ios-green');

            // Update kartu ini di tempat, TANPA reload halaman. Karena updateGrup() di
            // controller mengubah SEMUA baris dalam satu kelompok_baris_excel jadi nilai
            // yang sama persis dengan yang barusan disubmit, kita tahu pasti hasil akhirnya
            // tanpa perlu fetch ulang data dari server -> posisi scroll & state filter
            // gak berubah sama sekali.
            perbaruiTampilanSetelahSimpan(form);
        } catch (err) {
            teksStatus.textContent = 'Gagal menyimpan, coba lagi.';
            teksStatus.classList.add('text-ios-red');
        } finally {
            tombol.disabled = false;
            tombol.textContent = teksAsli;

            setTimeout(() => { teksStatus.textContent = ''; }, 3000);
        }
    });

    function perbaruiTampilanSetelahSimpan(form) {
        const container = form.closest('[data-kelompok-id]');
        if (! container) return;

        // Form pegawai punya field status_assignment -> bisa ngubah badge
        // Penyelesaian & Direspon. Form petugas cuma punya tanggapan_petugas -> update
        // teks "Tanggapan dari petugas" di kartu pegawai supaya ke-refresh juga kalau
        // pegawai & petugas lihat kartu yang sama di sesi masing-masing (opsional,
        // aman kalau elemen gak ketemu, di-skip aja).
        const selectStatus = form.querySelector('[name="status_assignment"]');

        if (selectStatus) {
            const sudah = selectStatus.value === 'sudah';

            // format tanggal biar sama kayak Carbon 'd M Y', mis. "13 Aug 2026"
            const tanggalHariIni = new Date().toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric',
            }).replace(/ /g, ' ');

            // Badge "Penyelesaian" di header kartu (atas)
            container.querySelectorAll('.js-status-top').forEach(el => {
                el.classList.toggle('border-ios-green/40', sudah);
                el.classList.toggle('text-ios-green', sudah);
                el.classList.toggle('bg-ios-green/15', sudah);
                el.classList.toggle('border-ios-border', !sudah);
                el.classList.toggle('text-ios-secondary', !sudah);
                el.classList.toggle('bg-white', !sudah);
                el.querySelector('.js-teks').textContent = sudah ? 'Sudah' : 'Belum';
            });

            // "Direspon {tanggal}" di pojok kanan atas
            container.querySelectorAll('.js-direspon-top').forEach(el => {
                if (sudah) {
                    el.style.display = '';
                    el.querySelector('.js-teks').textContent = tanggalHariIni;
                } else {
                    el.style.display = 'none';
                }
            });

            // Badge per-baris (di dalam "Lihat status & data tambahan") - versi petugas
            container.querySelectorAll('.js-status-baris-petugas').forEach(el => {
                el.classList.toggle('bg-ios-green/15', sudah);
                el.classList.toggle('text-ios-green', sudah);
                el.classList.toggle('bg-gray-100', !sudah);
                el.classList.toggle('text-ios-secondary', !sudah);
                el.querySelector('.js-teks').textContent = 'Penyelesaian: ' + (sudah ? 'Sudah' : 'Belum');
            });

            // Badge per-baris - versi pegawai
            container.querySelectorAll('.js-status-baris-pegawai').forEach(el => {
                el.classList.toggle('bg-ios-green/15', sudah);
                el.classList.toggle('text-ios-green', sudah);
                el.classList.toggle('bg-ios-amber/15', !sudah);
                el.classList.toggle('text-ios-amber', !sudah);
                el.querySelector('.js-teks').textContent = sudah ? 'Selesai' : 'Belum selesai';
            });

            // "Direspon {tanggal}" per-baris
            container.querySelectorAll('.js-direspon-baris').forEach(el => {
                if (sudah) {
                    el.style.display = '';
                    el.querySelector('.js-teks').textContent = 'Direspon ' + tanggalHariIni;
                } else {
                    el.style.display = 'none';
                }
            });
        }

        // Update teks "Tanggapan dari petugas" (kalau ada di kartu ini) supaya
        // langsung kelihatan tanpa reload, baik yang diisi pegawai (readonly di
        // form pegawai) maupun petugas (readonly di form petugas).
        const textareaTanggapan = form.querySelector('[name="tanggapan_petugas"]');
        if (textareaTanggapan) {
            const nilaiBaru = textareaTanggapan.value.trim();
            container.querySelectorAll('.bg-ios-red\\/5 .text-ios-red').forEach(el => {
                el.textContent = nilaiBaru || 'Belum ada tanggapan.';
            });
        }
    }
    </script>
    @endpush
</x-app-layout>