<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Anomali Sensus') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html, body { height: 100%; overflow: hidden; }
        </style>
    </head>
    <body class="font-sans antialiased">

        {{-- Full-bleed layout: foto jadi background PENUH 1920x1080 di
             lapisan paling belakang. Panel form krem ada di lapisan atas
             dengan clip-path berbentuk wave di sisi kirinya, jadi foto
             "terlihat" mengikuti bentuk lengkung itu — bukan di-crop
             kotak setengah layar seperti sebelumnya. --}}
        <div class="relative h-screen w-full overflow-hidden bg-[#FDFBF6]">

            {{-- ===== LAYER 1: foto full-bleed, utuh 1 layar penuh ===== --}}
           <picture class="hidden lg:block absolute inset-0 w-full h-full">
    <source srcset="{{ asset('images/login-bg.webp') }}" type="image/webp">
    <img src="{{ asset('images/login-bg.png') }}"
         alt="Sistem Anomali Sensus - BPS Kota Kediri"
         class="w-full h-full object-cover object-center select-none pointer-events-none"
         fetchpriority="high" decoding="async">
</picture>

            {{-- ===== definisi clip-path wave (tak kasat mata) =====
                 Dibuat lebih landai: hanya 2 lengkungan besar (bukan 5
                 lengkungan kecil yang keriting), jadi kesannya lebih
                 elegan dan "mengalir". --}}
            <svg width="0" height="0" class="absolute">
                <defs>
                    <clipPath id="loginWaveClip" clipPathUnits="objectBoundingBox">
                        <path d="
                            M0.58,0
                            C0.66,0.16 0.46,0.28 0.54,0.44
                            C0.60,0.56 0.46,0.68 0.52,0.82
                            C0.56,0.90 0.50,0.95 0.58,1
                            L1,1 L1,0 Z"/>
                    </clipPath>
                </defs>
            </svg>

            {{-- ===== LAYER 2: panel krem, di-clip mengikuti wave, TIMPA foto ===== --}}
            <div class="absolute inset-0 w-full h-full bg-[#FDFBF6] lg:[clip-path:url(#loginWaveClip)]"></div>

            {{-- ===== garis putus-putus PERSIS di batas clip (hiasan seam) ===== --}}
            <svg viewBox="0 0 100 100" preserveAspectRatio="none"
                 class="hidden lg:block absolute inset-0 w-full h-full pointer-events-none z-10">
                <path d="
                    M58,0
                    C66,16 46,28 54,44
                    C60,56 46,68 52,82
                    C56,90 50,95 58,100"
                    fill="none" stroke="#E7DCC6" stroke-width="0.18"
                    stroke-dasharray="0.6 0.7" vector-effect="non-scaling-stroke"/>
            </svg>

            {{-- ===== LAYER 2b: elemen dekoratif di area lengkungan (di atas
                 foto, di bawah panel form) supaya ruang kosong di sisi kiri
                 lengkungan tidak polos ===== --}}
            <div class="hidden lg:block absolute inset-0 w-full h-full pointer-events-none select-none z-10">
                {{-- paw print besar, transparan, mengambang di tengah lengkungan --}}
                <svg viewBox="0 0 64 64" fill="#FDFBF6" class="absolute top-[18%] left-[42%] w-14 h-14 opacity-30 -rotate-12">
                    <ellipse cx="32" cy="42" rx="16" ry="14"/>
                    <ellipse cx="14" cy="20" rx="7" ry="9"/>
                    <ellipse cx="30" cy="10" rx="7" ry="9"/>
                    <ellipse cx="48" cy="14" rx="7" ry="9"/>
                    <ellipse cx="54" cy="30" rx="6" ry="8"/>
                </svg>

                {{-- paw print kecil kedua, lebih bawah --}}
                <svg viewBox="0 0 64 64" fill="#FDFBF6" class="absolute top-[62%] left-[36%] w-9 h-9 opacity-25 rotate-[18deg]">
                    <ellipse cx="32" cy="42" rx="16" ry="14"/>
                    <ellipse cx="14" cy="20" rx="7" ry="9"/>
                    <ellipse cx="30" cy="10" rx="7" ry="9"/>
                    <ellipse cx="48" cy="14" rx="7" ry="9"/>
                    <ellipse cx="54" cy="30" rx="6" ry="8"/>
                </svg>

                {{-- ranting daun mengambang di lengkungan atas --}}
                <svg viewBox="0 0 24 24" fill="none" stroke="#F6EEDF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="absolute top-[8%] left-[48%] w-8 h-8 opacity-40">
                    <path d="M11 20A7 7 0 0 1 4 13c0-5 4-11 11-13 0 7-3 12-4 20Z"/>
                    <path d="M4 13c5 0 7-2 9-6"/>
                </svg>

                {{-- titik-titik kecil dekoratif menyebar di sepanjang lengkungan --}}
                <div class="absolute top-[30%] left-[50%] w-1.5 h-1.5 rounded-full bg-[#FDFBF6] opacity-40"></div>
                <div class="absolute top-[38%] left-[45%] w-1 h-1 rounded-full bg-[#FDFBF6] opacity-30"></div>
                <div class="absolute top-[72%] left-[48%] w-1.5 h-1.5 rounded-full bg-[#FDFBF6] opacity-40"></div>
                <div class="absolute top-[80%] left-[53%] w-1 h-1 rounded-full bg-[#FDFBF6] opacity-30"></div>
            </div>

            {{-- ===== LAYER 3: konten form, duduk di area panel (digeser
                 lebih ke kiri dibanding versi sebelumnya) ===== --}}
            <div class="relative z-20 h-full w-full flex items-center justify-center lg:justify-end">
                <div class="w-full max-w-sm px-6 sm:px-12 lg:px-0 lg:mr-[12%] xl:mr-[14%] py-8 overflow-y-auto max-h-full">

                    {{-- dekorasi paw watermark pojok kanan atas --}}
                    <svg viewBox="0 0 64 64" fill="#EADFC7" class="hidden sm:block absolute -top-2 right-2 w-24 h-24 opacity-70 pointer-events-none select-none">
                        <ellipse cx="32" cy="42" rx="16" ry="14"/>
                        <ellipse cx="14" cy="20" rx="7" ry="9"/>
                        <ellipse cx="30" cy="10" rx="7" ry="9"/>
                        <ellipse cx="48" cy="14" rx="7" ry="9"/>
                        <ellipse cx="54" cy="30" rx="6" ry="8"/>
                    </svg>

                    {{-- dekorasi daun kecil tengah-kanan --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="#CFE0C7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden lg:block absolute top-[48%] -right-8 w-10 h-10 opacity-80 pointer-events-none select-none">
                        <path d="M11 20A7 7 0 0 1 4 13c0-5 4-11 11-13 0 7-3 12-4 20Z"/>
                        <path d="M4 13c5 0 7-2 9-6"/>
                    </svg>

                    <div class="w-full relative">

                        {{-- Heading --}}
                        <div class="flex items-center justify-center gap-3">
                            <svg viewBox="0 0 20 20" fill="none" stroke="#2F6844" stroke-width="2.4" stroke-linecap="round" class="w-4 h-5 shrink-0">
                                <path d="M3 5 L8 8"/>
                                <path d="M2 10 L8 10"/>
                                <path d="M3 15 L8 12"/>
                            </svg>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 whitespace-nowrap">Selamat Datang!</h1>
                            <svg viewBox="0 0 20 20" fill="none" stroke="#2F6844" stroke-width="2.4" stroke-linecap="round" class="w-4 h-5 shrink-0 -scale-x-100">
                                <path d="M3 5 L8 8"/>
                                <path d="M2 10 L8 10"/>
                                <path d="M3 15 L8 12"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 mt-1 mb-8 text-center">Silakan masuk untuk melanjutkan</p>

                        <x-auth-session-status class="mb-4 text-sm" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-bold text-gray-900 mb-1.5">Email</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-green-800 pointer-events-none">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                                            <path d="m22 6-10 7L2 6"/>
                                        </svg>
                                    </span>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                                        placeholder="Masukkan email Anda"
                                        required autofocus autocomplete="username"
                                        class="block w-full rounded-full border border-green-700/50 bg-white pl-11 pr-4 py-3 text-sm text-gray-700 placeholder-gray-400 shadow-sm focus:border-green-700 focus:ring-2 focus:ring-green-700/20 focus:outline-none">
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                            </div>

                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-sm font-bold text-gray-900 mb-1.5">Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-green-800 pointer-events-none">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                        </svg>
                                    </span>
                                    <input id="password" type="password" name="password"
                                        placeholder="Masukkan password Anda"
                                        required autocomplete="current-password"
                                        class="block w-full rounded-full border border-green-700/50 bg-white pl-11 pr-11 py-3 text-sm text-gray-700 placeholder-gray-400 shadow-sm focus:border-green-700 focus:ring-2 focus:ring-green-700/20 focus:outline-none">
                                    <button type="button"
                                        onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';"
                                        class="absolute inset-y-0 right-4 flex items-center text-green-800">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                            </div>

                            {{-- Forgot password + submit --}}
                            <div class="flex items-center justify-between gap-4 pt-1">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-green-800 underline hover:text-green-900">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @else
                                    <span></span>
                                @endif

                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-full bg-[#a5651f] hover:bg-[#8f5419] px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors duration-200 whitespace-nowrap">
                                    {{ __('Log in') }}
                                    <svg viewBox="0 0 64 64" fill="currentColor" class="w-3.5 h-3.5">
                                        <ellipse cx="32" cy="42" rx="16" ry="14"/>
                                        <ellipse cx="14" cy="20" rx="7" ry="9"/>
                                        <ellipse cx="30" cy="10" rx="7" ry="9"/>
                                        <ellipse cx="48" cy="14" rx="7" ry="9"/>
                                        <ellipse cx="54" cy="30" rx="6" ry="8"/>
                                    </svg>
                                </button>
                            </div>
                        </form>

                        {{-- Divider putus-putus dengan ikon paw --}}
                        <div class="flex items-center gap-3 my-7">
                            <div class="flex-1 border-t-2 border-dashed border-green-700/40"></div>
                            <svg viewBox="0 0 64 64" fill="#2F6844" class="w-4 h-4 shrink-0">
                                <ellipse cx="32" cy="42" rx="16" ry="14"/>
                                <ellipse cx="14" cy="20" rx="7" ry="9"/>
                                <ellipse cx="30" cy="10" rx="7" ry="9"/>
                                <ellipse cx="48" cy="14" rx="7" ry="9"/>
                                <ellipse cx="54" cy="30" rx="6" ry="8"/>
                            </svg>
                            <div class="flex-1 border-t-2 border-dashed border-green-700/40"></div>
                        </div>

                        {{-- Badge fitur --}}
                        <div class="grid grid-cols-3 rounded-2xl bg-[#F6EEDF] divide-x divide-[#E7DBC2] py-4">
                            <div class="flex flex-col items-center text-center px-1.5">
                                <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#2F6844" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                                        <path d="m9 12 2 2 4-4"/>
                                    </svg>
                                </div>
                                <span class="text-[11px] sm:text-xs font-bold text-gray-800 leading-tight">Data<br>Terpercaya</span>
                            </div>
                            <div class="flex flex-col items-center text-center px-1.5">
                                <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#2F6844" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                        <circle cx="10" cy="10" r="7"/>
                                        <path d="m21 21-4.3-4.3"/>
                                        <path d="M6.5 11l1.8-1.8 1.7 1.7 2.5-2.6"/>
                                    </svg>
                                </div>
                                <span class="text-[11px] sm:text-xs font-bold text-gray-800 leading-tight">Deteksi Anomali<br>Lebih Cepat</span>
                            </div>
                            <div class="flex flex-col items-center text-center px-1.5">
                                <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#2F6844" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                        <path d="M3 3v18h18"/>
                                        <path d="M7 15l3-3 3 3 5-6"/>
                                    </svg>
                                </div>
                                <span class="text-[11px] sm:text-xs font-bold text-gray-800 leading-tight">Keputusan<br>Lebih Tepat</span>
                            </div>
                        </div>

                        {{-- Footer URL pill --}}
                        <div class="mt-7 flex items-center justify-center gap-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9CB98C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden sm:block w-5 h-5 shrink-0 opacity-70">
                                <path d="M11 20A7 7 0 0 1 4 13c0-5 4-11 11-13 0 7-3 12-4 20Z"/>
                                <path d="M4 13c5 0 7-2 9-6"/>
                            </svg>
                            <div class="flex items-center gap-2 bg-green-800 text-white text-[11px] sm:text-sm font-medium rounded-full pl-4 pr-3 py-2.5 shadow-sm max-w-full">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20"/>
                                    <path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20Z"/>
                                </svg>
                                <span class="truncate">https://statkdr.my.id/anomali3571</span>
                                <svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 shrink-0">
                                    <path d="M4 4l7.07 17 2.51-7.39L21 11.07Z"/>
                                </svg>
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9CB98C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden sm:block w-5 h-5 shrink-0 opacity-70 -scale-x-100">
                                <path d="M11 20A7 7 0 0 1 4 13c0-5 4-11 11-13 0 7-3 12-4 20Z"/>
                                <path d="M4 13c5 0 7-2 9-6"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>