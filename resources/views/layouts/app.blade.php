<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'HRIS') }}</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            * {
                font-family: 'DM Sans', sans-serif;
            }
        </style>
    </head>
    <body class="bg-[#F8FAFC] text-slate-900">
        @php
            $user = Auth::guard('admin')->user();
            $role = $user?->role;
        @endphp

        <header class="fixed left-0 right-0 top-0 z-20 bg-white border-b border-[#E5E7EB] h-14 px-6 flex items-center justify-between">
            <div>
                <div class="text-[#111827] font-bold text-lg">HRIS</div>
                <div class="text-xs text-slate-400">PT Walet Abdillah Jabji</div>
            </div>
            <div class="flex items-center gap-4">
                @if ($user)
                    <div class="text-right">
                        <p class="text-sm font-medium text-slate-800">{{ $user->name }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ strtoupper($user->role) }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-lg bg-[#4F46E5] px-4 py-2 text-sm font-medium text-white hover:bg-[#4338CA] transition">Logout</button>
                    </form>
                @endif
            </div>
        </header>

        <aside class="fixed left-0 top-14 bottom-0 w-56 bg-[#111827] border-r border-slate-200 pt-6">
            <nav class="space-y-1 px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/10' }}">
                    <span>🏠</span>
                    <span>Dashboard</span>
                </a>

                @if (in_array($role, ['admin', 'hrd'], true))
                    <a href="{{ route('karyawan.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs('karyawan.*') ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/10' }}">
                        <span>👥</span>
                        <span>Karyawan</span>
                    </a>
                @endif

                @if (in_array($role, ['admin', 'hrd', 'ga'], true))
                    <a href="{{ route('absensi.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs('absensi.*') ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/10' }}">
                        <span>🗓️</span>
                        <span>Absensi</span>
                    </a>
                @endif

                @if ($role === 'admin')
                    <a href="{{ route('setting.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs('setting.*') ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/10' }}">
                        <span>⚙️</span>
                        <span>Setting</span>
                    </a>
                @endif
            </nav>
        </aside>

        <div class="fixed inset-x-56 top-20 z-30 flex flex-col gap-3 px-4">
            @foreach (['success' => 'green', 'error' => 'red', 'warning' => 'yellow'] as $type => $color)
                @if(session($type))
                    <div id="flash-{{ $type }}" class="rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-800 shadow-sm flex items-start justify-between gap-4 max-w-2xl">
                        <div>{{ session($type) }}</div>
                        <button type="button" onclick="this.closest('[id^=\"flash-\"]')?.remove()" class="text-{{ $color }}-700 font-semibold">×</button>
                    </div>
                @endif
            @endforeach
        </div>

        <main class="ml-56 mt-14 p-8 bg-[#F8FAFC] min-h-screen">
            @yield('content')
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[id^="flash-"]').forEach(function(el) {
                    setTimeout(function() {
                        el.style.transition = 'opacity 0.5s';
                        el.style.opacity = '0';
                        setTimeout(function() { el.remove(); }, 500);
                    }, 3000);
                });
            });

            function showFlash(message, type = 'success') {
                const colors = { success: 'green', error: 'red', warning: 'yellow' };
                const c = colors[type] || 'green';
                const el = document.createElement('div');
                el.className = `fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg bg-${c}-100 border border-${c}-300 text-${c}-800 text-sm max-w-sm`;
                el.innerHTML = `${message} <button type="button" onclick="this.closest('div')?.remove()" class="font-semibold">×</button>`;
                document.body.appendChild(el);
                setTimeout(() => {
                    el.style.transition='opacity 0.5s';
                    el.style.opacity='0';
                    setTimeout(()=>el.remove(),500);
                }, 3000);
            }
        </script>
    </body>
</html>
