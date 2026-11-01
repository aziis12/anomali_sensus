<x-app-layout>
    <x-slot name="header">Dashboard Superadmin</x-slot>

    <div class="max-w-3xl space-y-5">

        @if (session('status'))
            <div class="bg-ios-green/10 border border-ios-green/20 text-green-800 rounded-ios p-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-ios-red/10 border border-ios-red/20 text-red-800 rounded-ios p-4 text-sm space-y-1">
                @foreach ($errors->all() as $pesan)
                    <p>{{ $pesan }}</p>
                @endforeach
            </div>
        @endif

        {{--
            Dua format upload dipertahankan berdampingan (bukan saling menggantikan):
            - Format BARU (1 file gabungan) ditaruh di atas karena ini yang sekarang
              dipakai rutin oleh pusat. Sekarang diproses BERTAHAP (lihat atribut
              data-bertahap di form-nya & fungsi kirimBertahap di script bawah).
            - Format LAMA (2 file usaha + keluarga) tetap ada untuk jaga-jaga kalau
              pusat balik lagi ke format lama (masih kirim sekali jalan seperti biasa).
            Toggle pakai Alpine x-data biar gak perlu reload halaman & gak nambah
            dependency baru (project ini sudah pakai Alpine di halaman lain).

            PROGRESS BAR: kedua form dikirim lewat XMLHttpRequest (komponen Alpine
            "formUnggah" di bagian <script> paling bawah) supaya persen unggah file
            bisa ditampilkan. Kalau JavaScript mati, form tetap jalan sebagai submit
            biasa (tanpa progress bar) karena action/method/enctype tetap ada.
        --}}
        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6" x-data="{ tabAktif: 'gabungan' }">
            <h3 class="text-base font-semibold text-ios-label mb-1">Unggah data anomali</h3>
            <p class="text-sm text-ios-secondary mb-4">Sistem akan memproses referensi PML/PPL terlebih dulu (kalau diisi), baru data anomalinya.</p>

            <div class="flex gap-2 mb-5 border-b border-ios-border">
                <button type="button"
                    @click="tabAktif = 'gabungan'"
                    :class="tabAktif === 'gabungan' ? 'border-ios-blue text-ios-blue' : 'border-transparent text-ios-secondary'"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                    Format baru (1 file gabungan)
                </button>
                <button type="button"
                    @click="tabAktif = 'lama'"
                    :class="tabAktif === 'lama' ? 'border-ios-blue text-ios-blue' : 'border-transparent text-ios-secondary'"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                    Format lama (2 file terpisah)
                </button>
            </div>

            {{-- ====== FORMAT BARU: 1 file gabungan (BERTAHAP) ====== --}}
            <div x-show="tabAktif === 'gabungan'" x-cloak>
                <form x-data="formUnggah" @submit.prevent="kirim($event)"
                    method="POST" action="{{ route('unggah.excel.gabungan') }}" enctype="multipart/form-data" class="space-y-4"
                    data-bertahap="1"
                    data-url-mulai="{{ route('unggah.excel.gabungan.mulai') }}"
                    data-url-proses="{{ route('unggah.excel.gabungan.proses') }}"
                    data-url-selesai="{{ route('unggah.excel.gabungan.selesai') }}">
                    @csrf

                    <p class="text-xs text-ios-secondary -mt-1">
                        Dipakai kalau file dari pusat sudah dalam format gabungan: usaha & keluarga jadi satu file,
                        satu baris per link fasih dengan kolom "Daftar Anomali" berisi semua anomalinya sekaligus.
                    </p>

                    <div>
                        <x-input-label for="tanggal_data_gabungan" value="Tanggal data" />
                        <input id="tanggal_data_gabungan" name="tanggal_data" type="date" required
                            value="{{ old('tanggal_data', now()->format('Y-m-d')) }}"
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5">
                        <p class="text-xs text-ios-secondary mt-1">Pilih tanggal periode data ini. Tidak otomatis ikut tanggal hari kamu unggah.</p>
                    </div>

                    <div>
                        <x-input-label for="file_gabungan" value="File Excel gabungan (usaha & keluarga)" />
                        <input id="file_gabungan" name="file_gabungan" type="file" accept=".xlsx" required
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                    </div>

                    <div>
                        <x-input-label for="file_referensi_gabungan" value="File referensi PML/PPL/Nama SLS (opsional)" />
                        <input id="file_referensi_gabungan" name="file_referensi" type="file" accept=".xlsx"
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                        <p class="text-xs text-ios-secondary mt-1">
                            Isi cuma kalau ada data PML/PPL/SLS yang baru atau berubah. Kolom wajib kalau diisi: idsub, pml, ppl, nama sls (header di baris pertama).
                            Kalau dikosongin, sistem tetap proses data anomalinya dan pakai referensi yang sudah ada dari unggahan referensi sebelumnya.
                        </p>
                    </div>

                    <x-progress-unggah />

                    <x-primary-button x-bind:disabled="sedangProses"
                        x-bind:class="sedangProses ? 'opacity-50 cursor-not-allowed' : ''">
                        <span x-text="sedangProses ? 'Sedang diproses…' : 'Proses file gabungan'">Proses file gabungan</span>
                    </x-primary-button>
                </form>
            </div>

            {{-- ====== FORMAT LAMA: 2 file terpisah ====== --}}
            <div x-show="tabAktif === 'lama'" x-cloak>
                <form x-data="formUnggah" @submit.prevent="kirim($event)"
                    method="POST" action="{{ route('unggah.excel') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <p class="text-xs text-ios-secondary -mt-1">
                        Dipakai kalau file dari pusat masih dalam format lama: data usaha dan data keluarga
                        dikirim sebagai dua file Excel terpisah.
                    </p>

                    <div>
                        <x-input-label for="tanggal_data" value="Tanggal data" />
                        <input id="tanggal_data" name="tanggal_data" type="date" required
                            value="{{ old('tanggal_data', now()->format('Y-m-d')) }}"
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5">
                        <p class="text-xs text-ios-secondary mt-1">Pilih tanggal periode data ini. Tidak otomatis ikut tanggal hari kamu unggah.</p>
                    </div>

                    <div>
                        <x-input-label for="file_usaha" value="File data mikro anomali usaha" />
                        <input id="file_usaha" name="file_usaha" type="file" accept=".xlsx" required
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                    </div>

                    <div>
                        <x-input-label for="file_keluarga" value="File data mikro anomali keluarga" />
                        <input id="file_keluarga" name="file_keluarga" type="file" accept=".xlsx" required
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                    </div>

                    <div>
                        <x-input-label for="file_referensi" value="File referensi PML/PPL/Nama SLS (opsional)" />
                        <input id="file_referensi" name="file_referensi" type="file" accept=".xlsx"
                            class="block mt-1 w-full text-sm text-ios-label border border-ios-border rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-ios-blue/10 file:text-ios-blue file:text-sm">
                        <p class="text-xs text-ios-secondary mt-1">
                            Isi cuma kalau ada data PML/PPL/SLS yang baru atau berubah. Kolom wajib kalau diisi: idsub, pml, ppl, nama sls (header di baris pertama).
                            Kalau dikosongin, sistem tetap proses data anomalinya dan pakai referensi yang sudah ada dari unggahan referensi sebelumnya.
                        </p>
                    </div>

                    <x-progress-unggah />

                    <x-primary-button x-bind:disabled="sedangProses"
                        x-bind:class="sedangProses ? 'opacity-50 cursor-not-allowed' : ''">
                        <span x-text="sedangProses ? 'Sedang diproses…' : 'Proses dan unggah'">Proses dan unggah</span>
                    </x-primary-button>
                </form>
            </div>
        </div>

        {{--
            Form Penugasan PJ (dulu duplikat di halaman Rekap per-PML DAN per-PJ)
            sekarang cuma ada di sini, satu tempat, biar gak ada 2 sumber kebenaran
            soal "file penugasan mana yang terakhir diupload". Halaman Rekap per-PML/PJ
            sekarang murni tampilan, tinggal baca data yang sudah diupload di sini.
        --}}
        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-1">Unggah Data Penugasan PJ</h3>
            <p class="text-sm text-ios-secondary mb-4">
                File excel berisi kolom: No, Email PML, PML, Email PJ, PJ, Kec. Dipakai untuk Rekap Monitoring per PML &amp; per PJ.
                Setiap upload akan menggantikan seluruh data penugasan sebelumnya.
            </p>

            <form action="{{ route('monitoring-tindak-lanjut.unggah-pj') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                @csrf
                <input type="file" name="file_pj" accept=".xlsx,.xls" required
                       class="text-sm text-ios-secondary file:mr-3 file:py-2 file:px-4 file:rounded-ios file:border-0 file:bg-ios-blue file:text-white file:text-sm file:font-medium hover:file:opacity-90" />
                <button type="submit" class="shrink-0 bg-ios-blue text-white text-sm font-medium px-4 py-2 rounded-ios hover:opacity-90">
                    Unggah &amp; Ganti Data
                </button>
            </form>

            @error('file_pj')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{--
            Backup & Restore data (lihat App\Http\Controllers\BackupRestoreController).
            Ditaruh di kartu sendiri, terpisah dari form upload di atas, supaya gak
            ketuker sama proses upload rutin dari pusat -- ini murni buat jaga-jaga
            kalau ada kehilangan data di server.
        --}}
        <div class="bg-ios-card rounded-ios shadow-sm border border-ios-border p-6">
            <h3 class="text-base font-semibold text-ios-label mb-1">Backup &amp; Restore Data</h3>
            <p class="text-sm text-ios-secondary mb-4">
                Unduh cadangan seluruh data anomali (termasuk tanggapan &amp; status kerjaan), atau pulihkan
                data dari file backup kalau terjadi masalah di server.
            </p>
            <a href="{{ route('backup.restore.form') }}" class="inline-flex items-center gap-2 bg-ios-card border border-ios-border text-ios-label text-sm px-4 py-2 rounded-ios font-medium hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Buka Halaman Backup &amp; Restore
            </a>
        </div>

    </div>

    {{--
        Komponen Alpine "formUnggah": kirim form lewat XMLHttpRequest supaya bisa
        baca progress unggah (xhr.upload.onprogress). Tahap:
          idle -> mengunggah (persen nyata 0-100)
               -> memproses (server baca Excel; persen TIDAK diketahui, jadi bar diisi
                  berdasarkan PERKIRAAN waktu + hitung mundur sisa waktu)
               -> selesai / gagal.

        MODE BERTAHAP (form dengan atribut data-bertahap, yaitu format gabungan):
          1. POST ke data-url-mulai: kirim file (persen unggah nyata), server baca header
             & buat riwayat.
          2. POST ke data-url-proses berulang: tiap request memproses sekitar 150 baris
             Excel. Di tahap ini persen "memproses" NYATA (bukan perkiraan waktu).
          3. POST ke data-url-selesai: tandai data yang tidak muncul lagi, sinkronisasi,
             hitung ringkasan.
        Tiap request pendek, jadi tidak kena batas waktu/memori hosting.

        Cara perkiraan waktu "memproses" (mode biasa / format lama):
          estimasi detik = ukuran total file (MB) x detik-per-MB.
          detik-per-MB awalnya tebakan (DETIK_PER_MB_AWAL), lalu otomatis dipelajari
          dari proses yang sukses sebelumnya (disimpan di localStorage browser, per form).
          Bar naik linear sampai 90% di titik estimasi, lalu merayap pelan ke maksimal 99%
          kalau ternyata lebih lama (tidak pernah penuh sebelum server benar-benar selesai).

        Dibungkus blok verbatim supaya Blade tidak menafsirkan kurung kurawal di dalam JS.
        Didaftarkan lewat event alpine:init, yang jalan sebelum Alpine.start().
    --}}
    @verbatim
    <script>
        document.addEventListener('alpine:init', () => {
            const DETIK_PER_MB_AWAL = 20; // tebakan awal sebelum ada data hasil belajar

            // kirim satu request XHR, balikannya Promise<json>. Error dilempar sebagai Error
            // dengan pesan yang sudah siap ditampilkan.
            function kirimXhr(url, formData, onUploadProgress, onUploadSelesai) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', url);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.timeout = 0;

                    if (onUploadProgress) {
                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) onUploadProgress(Math.round((e.loaded / e.total) * 100));
                        };
                    }
                    if (onUploadSelesai) xhr.upload.onload = onUploadSelesai;

                    xhr.onload = () => {
                        let json = null;
                        try { json = JSON.parse(xhr.responseText); } catch (e) {}

                        if (xhr.status >= 200 && xhr.status < 300) {
                            return resolve(json || {});
                        }

                        if (xhr.status === 422 && json) {
                            let daftar = Object.values(json.errors || {}).flat();
                            if (!daftar.length && json.message) daftar = [json.message];
                            return reject(new Error(daftar.join(' | ')));
                        }
                        if (xhr.status === 413) {
                            return reject(new Error('File terlalu besar, melebihi batas unggah server (upload_max_filesize / post_max_size).'));
                        }
                        if (xhr.status === 419) {
                            return reject(new Error('Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.'));
                        }
                        if ([502, 503, 504, 524].includes(xhr.status)) {
                            return reject(new Error('Server timeout (kode ' + xhr.status + ') sebelum proses selesai.'));
                        }
                        reject(new Error((json && json.message) ? json.message : 'Terjadi kesalahan di server (kode ' + xhr.status + ').'));
                    };

                    xhr.onerror = () => reject(new Error('Koneksi terputus saat mengunggah. Periksa jaringan lalu coba lagi.'));

                    xhr.send(formData);
                });
            }

            Alpine.data('formUnggah', () => ({
                status: 'idle', // idle | mengunggah | memproses | selesai | gagal
                persen: 0,          // persen unggah file (nyata)
                persenProses: 0,    // persen tahap memproses (perkiraan, atau nyata di mode bertahap)
                sisaLabel: '',
                pesan: '',
                errors: [],
                _timer: null,
                _mulai: 0,
                _estimasi: 60,
                _ukuranMb: 0.1,
                _kunciBelajar: '',
                _penjaga: null,

                get sedangProses() {
                    return this.status === 'mengunggah' || this.status === 'memproses';
                },

                formatWaktu(detik) {
                    const d = Math.max(0, Math.round(detik));
                    const mm = String(Math.floor(d / 60)).padStart(2, '0');
                    const ss = String(d % 60).padStart(2, '0');
                    return mm + ':' + ss;
                },

                // hitung perkiraan lama proses dari ukuran file + hasil belajar sebelumnya
                hitungEstimasi(form) {
                    let byte = 0;
                    form.querySelectorAll('input[type=file]').forEach((el) => {
                        for (const f of el.files) byte += f.size;
                    });
                    this._ukuranMb = Math.max(byte / 1048576, 0.1);
                    this._kunciBelajar = 'unggah_detik_per_mb:' + form.getAttribute('action');

                    let perMb = DETIK_PER_MB_AWAL;
                    try {
                        const tersimpan = parseFloat(localStorage.getItem(this._kunciBelajar));
                        if (tersimpan > 0) perMb = tersimpan;
                    } catch (e) {}

                    this._estimasi = Math.max(10, Math.round(this._ukuranMb * perMb));
                },

                // mulai hitung mundur + isi bar bertahap (dipanggil setelah file selesai terkirim)
                mulaiEstimasi() {
                    this._mulai = Date.now();
                    this.persenProses = 0;
                    this.sisaLabel = 'Perkiraan sisa ± ' + this.formatWaktu(this._estimasi);

                    this._timer = setInterval(() => {
                        const detik = (Date.now() - this._mulai) / 1000;
                        const rasio = detik / this._estimasi;

                        if (rasio < 1) {
                            this.persenProses = rasio * 90;
                            this.sisaLabel = 'Perkiraan sisa ± ' + this.formatWaktu(Math.ceil(this._estimasi - detik));
                        } else {
                            // lebih lama dari perkiraan: merayap pelan ke 99%, jangan pernah penuh
                            this.persenProses = 90 + 9 * (1 - Math.exp(-(rasio - 1) * 2));
                            this.sisaLabel = 'Hampir selesai…';
                        }
                    }, 500);
                },

                // simpan detik-per-MB dari proses yang sukses, buat perkiraan berikutnya
                simpanBelajar() {
                    if (!this._mulai) return;
                    const detik = (Date.now() - this._mulai) / 1000;
                    if (detik < 1) return;
                    try {
                        localStorage.setItem(this._kunciBelajar, String(detik / this._ukuranMb));
                    } catch (e) {}
                },

                selesaiProses() {
                    clearInterval(this._timer);
                    if (this._penjaga) {
                        window.removeEventListener('beforeunload', this._penjaga);
                        this._penjaga = null;
                    }
                },

                gagal(daftarPesan) {
                    this.status = 'gagal';
                    this.errors = daftarPesan;
                    this.selesaiProses();
                },

                // ====== MODE BERTAHAP (format gabungan) ======
                async kirimBertahap(form) {
                    const token = form.querySelector('input[name=_token]').value;

                    try {
                        // 1) kirim file (persen unggah nyata)
                        const fd = new FormData(form);
                        const mulai = await kirimXhr(
                            form.dataset.urlMulai,
                            fd,
                            (p) => { this.persen = p; },
                            () => {
                                this.persen = 100;
                                this.status = 'memproses';
                                this.persenProses = 0;
                                this.sisaLabel = 'Membaca file…';
                            }
                        );

                        this.status = 'memproses';
                        this.persen = 100;

                        // 2) proses per potongan baris, persen NYATA
                        const total = mulai.total_baris;
                        const awalData = mulai.awal;
                        const jumlah = Math.max(total - awalData + 1, 1);
                        let awal = awalData;
                        const waktuMulai = Date.now();

                        while (awal <= total) {
                            const f = new FormData();
                            f.append('_token', token);
                            f.append('riwayat_id', mulai.riwayat_id);
                            f.append('awal', awal);

                            const r = await kirimXhr(form.dataset.urlProses, f);
                            awal = r.berikutnya;

                            const selesaiBaris = Math.min(awal - awalData, jumlah);
                            this.persenProses = Math.min(99, (selesaiBaris / jumlah) * 100);

                            const detik = (Date.now() - waktuMulai) / 1000;
                            const sisaDetik = selesaiBaris > 0 ? (detik / selesaiBaris) * (jumlah - selesaiBaris) : 0;
                            this.sisaLabel = 'Baris ' + Math.min(awal - 1, total) + ' dari ' + total +
                                ' · sisa ± ' + this.formatWaktu(sisaDetik);
                        }

                        // 3) finalisasi
                        this.sisaLabel = 'Menyelesaikan…';
                        const f = new FormData();
                        f.append('_token', token);
                        f.append('riwayat_id', mulai.riwayat_id);
                        const hasil = await kirimXhr(form.dataset.urlSelesai, f);

                        this.status = 'selesai';
                        this.persenProses = 100;
                        this.pesan = hasil.message || 'Berhasil diproses.';
                        this.selesaiProses();
                        form.reset();
                    } catch (e) {
                        this.gagal([e.message || 'Terjadi kesalahan saat memproses.']);
                    }
                },

                kirim(event) {
                    if (this.sedangProses) return;

                    const form = event.target;

                    this.status = 'mengunggah';
                    this.persen = 0;
                    this.persenProses = 0;
                    this.pesan = '';
                    this.errors = [];
                    this._mulai = 0;

                    // peringatan kalau user coba tutup/refresh tab selagi proses jalan
                    this._penjaga = (e) => { e.preventDefault(); e.returnValue = ''; };
                    window.addEventListener('beforeunload', this._penjaga);

                    // form format gabungan -> proses bertahap
                    if (form.dataset.bertahap) {
                        return this.kirimBertahap(form);
                    }

                    // ====== cara biasa (format lama) ======
                    const xhr = new XMLHttpRequest();
                    this.hitungEstimasi(form);

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.timeout = 0; // tanpa batas waktu di sisi browser

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            this.persen = Math.round((e.loaded / e.total) * 100);
                        }
                    };

                    // file sudah terkirim semua -> server mulai memproses
                    xhr.upload.onload = () => {
                        this.persen = 100;
                        this.status = 'memproses';
                        this.mulaiEstimasi();
                    };

                    xhr.onload = () => {
                        let json = null;
                        try { json = JSON.parse(xhr.responseText); } catch (e) {}

                        if (xhr.status >= 200 && xhr.status < 300) {
                            this.status = 'selesai';
                            this.persenProses = 100;
                            this.pesan = (json && json.message) ? json.message : 'Berhasil diproses.';
                            this.simpanBelajar();
                            this.selesaiProses();
                            form.reset();
                            return;
                        }

                        if (xhr.status === 422 && json) {
                            let daftar = Object.values(json.errors || {}).flat();
                            if (!daftar.length && json.message) daftar = [json.message];
                            return this.gagal(daftar);
                        }

                        if (xhr.status === 413) {
                            return this.gagal(['File terlalu besar, melebihi batas unggah server (upload_max_filesize / post_max_size).']);
                        }

                        if (xhr.status === 419) {
                            return this.gagal(['Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.']);
                        }

                        if ([502, 503, 504, 524].includes(xhr.status)) {
                            return this.gagal([
                                'Server timeout (kode ' + xhr.status + ') sebelum proses selesai. ' +
                                'Proses mungkin masih berjalan di server, cek halaman Riwayat Unggah sebelum mengunggah ulang.'
                            ]);
                        }

                        this.gagal([
                            (json && json.message) ? json.message : 'Terjadi kesalahan di server (kode ' + xhr.status + ').'
                        ]);
                    };

                    xhr.onerror = () => {
                        this.gagal(['Koneksi terputus saat mengunggah. Periksa jaringan lalu coba lagi.']);
                    };

                    xhr.send(new FormData(form));
                },
            }));
        });
    </script>
    @endverbatim
</x-app-layout>