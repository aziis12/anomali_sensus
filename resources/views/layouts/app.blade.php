<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistem Anomali Sensus') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-ios-bg text-ios-label"
      x-data="{
          sidebarMobile: false,
          sidebarTerbuka: localStorage.getItem('sidebarTerbuka') !== 'false'
      }"
      x-init="$watch('sidebarTerbuka', v => localStorage.setItem('sidebarTerbuka', v))">

<div class="flex h-screen overflow-hidden">

    {{-- sidebar desktop, lebarnya berubah tergantung sidebarTerbuka --}}
    <aside class="h-screen shrink-0 bg-white border-r border-ios-border hidden lg:flex lg:flex-col transition-all duration-200 overflow-hidden"
           :class="sidebarTerbuka ? 'w-60' : 'w-0 border-r-0'">
        <div class="w-60 h-full flex flex-col">
            @include('layouts.partials.sidebar-isi')
        </div>
    </aside>

    {{-- sidebar mobile, overlay --}}
    <div x-show="sidebarMobile" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div class="absolute inset-0 bg-black/30" @click="sidebarMobile = false"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-64 bg-white flex flex-col">
            @include('layouts.partials.sidebar-isi')
        </aside>
    </div>

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-16 bg-white/80 backdrop-blur-xl border-b border-ios-border flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarMobile = true" class="lg:hidden text-ios-secondary shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <button @click="sidebarTerbuka = !sidebarTerbuka" class="hidden lg:flex text-ios-secondary hover:text-ios-label shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M3.75 15h16.5M3.75 5.25h16.5M3.75 18.75h16.5" />
                    </svg>
                </button>

                <div class="font-semibold text-lg text-ios-label truncate">{{ $header ?? '' }}</div>
            </div>

            <div class="relative shrink-0" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
    <button @click="profileOpen = !profileOpen"
            class="flex items-center gap-2 text-sm text-ios-secondary hover:text-ios-label transition">
        <div class="w-8 h-8 rounded-full bg-ios-blue/10 text-ios-blue flex items-center justify-center font-semibold text-xs">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
        <svg class="w-4 h-4 text-ios-secondary hidden sm:inline transition-transform"
             :class="profileOpen ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="profileOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-ios-border py-1 z-50 origin-top-right">

        <div class="px-4 py-2 border-b border-ios-border">
            <div class="font-medium text-sm text-ios-label truncate">{{ auth()->user()->name }}</div>
            <div class="text-xs text-ios-secondary truncate">{{ auth()->user()->email }}</div>
        </div>

        <a href="{{ route('profile.edit') }}"
           class="block px-4 py-2 text-sm text-ios-label hover:bg-ios-bg transition">
            {{ __('Profile') }}
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-ios-bg transition">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')

</body>
</html>