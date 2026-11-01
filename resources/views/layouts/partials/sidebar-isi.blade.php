@php $user = auth()->user(); @endphp

<div class="h-16 flex items-center px-5 border-b border-ios-border">
    <span class="font-semibold text-base tracking-tight text-ios-label">Anomali Sensus</span>
</div>

<nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.125 1.125 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
        </svg>
        Dashboard
    </a>
    

    @if ($user->isSuperadmin())
    <a href="{{ route('impor-data-lama.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('impor-data-lama.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
    </svg>
    Impor Data Lama
</a>

    {{-- Menu Monitoring Tindak Lanjut: bisa di-expand, isinya sub-halaman rekap
         (per PJ, per PML & per PPL) + grafik. Otomatis kebuka (open) kalau salah satu
         sub-halamannya lagi aktif, biar user gak bingung "kok nyasar ke sini". --}}
    @php $tlAktif = request()->routeIs('monitoring-tindak-lanjut.*'); @endphp
    <div>
        <button type="button" onclick="toggleSubmenuSidebar('submenu-monitoring-tl', this)"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ $tlAktif ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="flex-1 text-left">Monitoring Progres</span>
            <svg data-submenu-chevron class="w-4 h-4 shrink-0 transition-transform duration-150 {{ $tlAktif ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        <div id="submenu-monitoring-tl" class="{{ $tlAktif ? '' : 'hidden' }} mt-1 ml-4 pl-4 border-l border-ios-border space-y-1">
            <a href="{{ route('monitoring-tindak-lanjut.per-pj') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('monitoring-tindak-lanjut.per-pj') ? 'text-ios-blue' : 'text-ios-secondary hover:bg-gray-50' }}">
                Rekap per PJ
            </a>
            <a href="{{ route('monitoring-tindak-lanjut.per-pml') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('monitoring-tindak-lanjut.per-pml') ? 'text-ios-blue' : 'text-ios-secondary hover:bg-gray-50' }}">
                Rekap per PML
            </a>
            <a href="{{ route('monitoring-tindak-lanjut.per-ppl') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('monitoring-tindak-lanjut.per-ppl') ? 'text-ios-blue' : 'text-ios-secondary hover:bg-gray-50' }}">
                Rekap per PPL
            </a>
            <a href="{{ route('monitoring-tindak-lanjut.grafik') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('monitoring-tindak-lanjut.grafik') ? 'text-ios-blue' : 'text-ios-secondary hover:bg-gray-50' }}">
                Grafik &amp; Breakdown
            </a>
        </div>
    </div>

        {{-- <a href="{{ route('monitoring.progress-petugas') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('monitoring.progress-petugas') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            Progress Petugas
        </a>
        <a href="{{ route('monitoring.progress-pegawai') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('monitoring.progress-pegawai') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Progress Pegawai
        </a> --}}

        <a href="{{ route('kelola-petugas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('kelola-petugas.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Kelola Akun Petugas
        </a>

        <a href="{{ route('kelola-pegawai.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('kelola-pegawai.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            Kelola Akun Pegawai
        </a>
        

        <a href="{{ route('riwayat-unggah.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('riwayat-unggah.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Riwayat Unggah
        </a>

        <a href="{{ route('data-anomali.ekspor') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-ios-secondary hover:bg-gray-50">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Ekspor Data Anomali
        </a>

        {{-- Backup & Restore Data (lihat App\Http\Controllers\BackupRestoreController) --}}
        <a href="{{ route('backup.restore.form') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('backup.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
            Backup &amp; Restore
        </a>

        
    @endif

    @if ($user->isPegawai() || $user->isPetugas())
    <a href="{{ route('data-anomali.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('data-anomali.*') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM3.75 9h16.5M9 20.25V9" />
            </svg>
            Data Anomali
        </a>

        <a href="{{ route('monitoring.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('monitoring.index') ? 'bg-ios-blue text-white' : 'text-ios-secondary hover:bg-gray-50' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            Progress
        </a>
    @endif
</nav>

<div class="p-3 border-t border-ios-border space-y-1">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-ios-secondary hover:bg-gray-50">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
        Profil
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-ios-red hover:bg-red-50">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            Keluar
        </button>
    </form>
</div>

<script>
    // toggle sederhana buat submenu sidebar (vanilla JS, gak pakai Alpine karena
    // project ini gak load Alpine.js). Panah (chevron) ikut rotasi 90 derajat
    // pas submenu-nya dibuka.
    function toggleSubmenuSidebar(idSubmenu, tombol) {
        const submenu = document.getElementById(idSubmenu);
        if (!submenu) return;

        submenu.classList.toggle('hidden');

        const chevron = tombol.querySelector('[data-submenu-chevron]');
        if (chevron) {
            chevron.classList.toggle('rotate-90');
        }
    }
</script>