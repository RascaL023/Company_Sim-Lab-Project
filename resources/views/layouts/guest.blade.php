<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') · SIMLab</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fafafa] antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        {{-- Brand panel --}}
        <div class="relative hidden overflow-hidden bg-brand-950 lg:flex lg:flex-col lg:justify-between lg:p-14">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-[28rem] w-[28rem] translate-x-1/3 translate-y-1/3 rounded-full bg-violet-400/15 blur-3xl"></div>
                <div class="absolute left-1/3 top-1/2 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"></div>
            </div>

            <div class="relative flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 font-display text-lg font-bold text-white ring-1 ring-white/15">S</div>
                <div>
                    <p class="font-display text-lg font-bold leading-tight text-white">SIMLab</p>
                    <p class="text-xs text-brand-200/70">Sistem Manajemen Laboratorium</p>
                </div>
            </div>

            <div class="relative">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('images/login-1.jpeg') }}" alt="Foto laboratorium" class="relative z-0 h-40 w-44 -mr-20 rounded-2xl object-cover shadow-xl ring-1 ring-white/15" />
                    <img src="{{ asset('images/login-3.jpeg') }}" alt="Foto laboratorium" class="relative z-10 h-48 w-52 rounded-2xl object-cover shadow-2xl ring-4 ring-brand-950/50" />
                    <img src="{{ asset('images/login-2.jpeg') }}" alt="Foto laboratorium" class="relative z-0 h-40 w-44 -ml-20 rounded-2xl object-cover shadow-xl ring-1 ring-white/15" />
                </div>

                <div class="mt-10">
                    <p class="font-display text-4xl font-bold leading-tight tracking-tight text-white">
                        Inventarisasi, peminjaman,<br />
                        dan mutasi alat lab<br />
                        <span class="bg-gradient-to-r from-brand-200 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">dalam satu tempat.</span>
                    </p>
                    <p class="mt-5 max-w-md text-[15px] leading-relaxed text-brand-100/70">
                        SIMLab mencatat seluruh siklus hidup aset laboratorium — dari katalog, kalibrasi, pemakaian, hingga audit trail — sesuai standar GLP.
                    </p>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
