<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'HRIS') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            * {
                font-family: 'DM Sans', sans-serif;
            }
            :root {
                --c-navy: #2F4156;
                --c-teal: #567C8D;
                --c-sky: #C8D9E6;
                --c-sky-light: #EAF1F6;
                --c-beige: #F5EFEB;
            }
        </style>
    </head>
    <body class="bg-[#F5EFEB] text-[#2F4156]">
        @php
            $user = Auth::guard('admin')->user();
            $role = $user?->role;
        @endphp

        {{-- SIDEBAR --}}
        <aside class="fixed left-0 top-0 bottom-0 w-[220px] bg-[#EAF1F6] border-r border-[#C8D9E6] flex flex-col">
            {{-- Logo Area --}}
            <div class="flex items-center gap-2.5 px-4 py-5 border-b border-[#C8D9E6]">
                <div class="flex items-center justify-center w-7 h-7 bg-[#2F4156] rounded-lg">
                    <span class="text-white text-xs font-bold">W</span>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-[#2F4156]">PT WAJ HRIS</div>
                </div>
            </div>

            {{-- Nav Items --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                {{-- MAIN Section --}}
                <div class="space-y-1 mb-6">
                    <div class="px-3 py-2 text-[10px] font-semibold text-[#8BAFC4] uppercase tracking-wide">Main</div>
                    
                    {{-- Dashboard --}}
                    <a href="{{ route('dashboard') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- Karyawan Section --}}
                    @if ($role && in_array($role, ['admin', 'hrd', 'payroll'], true))
                        <div class="space-y-1 mb-6">
                            <div class="px-3 py-2 text-[10px] font-semibold text-[#8BAFC4] uppercase tracking-wide">Karyawan</div>

                            @if (in_array($role, ['admin', 'hrd'], true))
                                <a href="{{ route('karyawan.index') }}" 
                                    class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('karyawan.*') && !request()->routeIs('karyawan.bank.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span>Karyawan</span>
                                </a>
                            @endif

                            @if (in_array($role, ['admin', 'hrd'], true))
                                <a href="{{ route('tl-bawahan.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('tl-bawahan.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span>Daftar TL & Bawahan</span>
                                </a>
                            @endif

                            @if (in_array($role, ['admin', 'payroll'], true))
                                <a href="{{ route('karyawan.bank.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('karyawan.bank.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M4 7h16"/><path d="M4 11h16"/><path d="M4 15h16"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                    <span>Data Bank Karyawan</span>
                                </a>
                            @endif

                            @if (in_array($role, ['admin', 'hrd', 'payroll'], true))
                                <a href="{{ route('id-card.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('id-card.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 14.25a2.25 2.25 0 0 1 2.25-2.25h1.5A2.25 2.25 0 0 1 15 14.25v.75"/><circle cx="12" cy="8.25" r="2.25"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
                                    <span>Export ID Card</span>
                                </a>
                            @endif

                            @if (in_array($role, ['admin', 'hrd', 'ga'], true))
                                <a href="{{ route('pkwt.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('pkwt.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    <span>Export PKWT</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Absensi --}}
                    @if (in_array($role, ['admin', 'hrd', 'ga'], true))
                        <a href="{{ route('absensi.index') }}" 
                            class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('absensi.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
                            <span>Absensi</span>
                        </a>
                    @endif
                </div>

                {{-- PAYROLL Section --}}
                @if ($role && in_array($role, ['admin', 'payroll', 'hrd'], true))
                <div class="space-y-1 mb-6">
                    <div class="px-3 py-2 text-[10px] font-semibold text-[#8BAFC4] uppercase tracking-wide">Payroll</div>
                    
                    {{-- Payroll Harian --}}
                    <a href="{{ route('payroll.index') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('payroll.*') && !request()->routeIs('payroll.show') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>Payroll Harian</span>
                    </a>

                    {{-- Borongan --}}
                    <a href="{{ route('borongan.index') }}"
                        class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('borongan.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Borongan</span>
                        <span class="ml-auto text-[10px] bg-[#2F4156] text-white px-1.5 py-0.5 rounded-full">3</span>
                    </a>
                </div>
                @endif

                {{-- SETTING Section --}}
                @if ($role === 'admin')
                <div class="space-y-1">
                    <div class="px-3 py-2 text-[10px] font-semibold text-[#8BAFC4] uppercase tracking-wide">Setting</div>
                    
                    <a href="{{ route('setting.index') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-[12.5px] rounded-lg transition {{ request()->routeIs('setting.*') ? 'bg-[#2F4156] text-white font-medium' : 'text-[#567C8D] hover:bg-[#D6E5EF]' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 flex-shrink-0"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Pengaturan</span>
                    </a>
                </div>
                @endif
            </nav>

            {{-- User Chip --}}
            @if ($user)
                <div class="border-t border-[#C8D9E6] p-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center justify-center w-8 h-8 bg-[#2F4156] rounded-full">
                            <span class="text-[#C8D9E6] text-xs font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-[#2F4156] truncate">{{ $user->name }}</p>
                            <p class="text-[10px] text-[#567C8D] truncate">{{ strtoupper($user->role) }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </aside>

        {{-- TOPBAR --}}
        <header class="fixed left-[220px] right-0 top-0 h-[58px] bg-white border-b border-[#C8D9E6] flex items-center justify-between px-6 z-10">
            <div id="topbar-title">
                {{-- Page title will be filled by pages --}}
            </div>
            <div id="topbar-actions" class="flex items-center gap-3">
                {{-- Action buttons will be injected here --}}
                @if ($user)
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="pbtn pbtn-ghost pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </span>
                            <span>Logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </header>

        {{-- Flash Messages --}}
        <div class="fixed left-[220px] right-0 top-[70px] z-30 flex flex-col gap-3 px-6 max-w-2xl pointer-events-none">
            @foreach (['success' => '#E0F2EA|#1B7A4A', 'error' => '#FEE2E2|#B91C1C', 'warning' => '#FFF3DC|#9A6200'] as $type => $colors)
                @if(session($type))
                    @php [$bg, $text] = explode('|', $colors); @endphp
                    <div id="flash-{{ $type }}" class="rounded-lg border px-4 py-3 text-sm shadow-sm flex items-start justify-between gap-4 pointer-events-auto" style="background-color: {{ $bg }}; border-color: {{ $text }}; color: {{ $text }};">
                        <div>{{ session($type) }}</div>
                        <button type="button" onclick="this.closest('[id^=\"flash-\"]')?.remove()" class="font-semibold hover:opacity-70">×</button>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Main Content --}}
        <main class="ml-[220px] mt-[58px] p-6 bg-[#F5EFEB] min-h-screen">
            @yield('content')
        </main>

        <script>
            setTimeout(function() {
                document.querySelectorAll('[id^="flash-"]').forEach(el => {
                    el.style.transition = 'opacity 0.5s ease';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 500);
                });
            }, 4000);
        </script>
        @stack('scripts')
    </body>
</html>