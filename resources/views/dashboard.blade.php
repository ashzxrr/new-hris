@extends('layouts.app')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Halo, {{ $user->name }} 👋</h1>
        <p class="text-sm text-slate-400 mt-1 mb-8">{{ date('l, d F Y') }}</p>

        <style>
            @keyframes rocket-flight {
                0% {
                    transform: translate(0, 0) rotate(-10deg);
                    opacity: 1;
                }
                25% {
                    transform: translate(18vw, -10vh) rotate(-5deg);
                    opacity: 0.95;
                }
                50% {
                    transform: translate(36vw, -20vh) rotate(0deg);
                    opacity: 0.9;
                }
                75% {
                    transform: translate(54vw, -35vh) rotate(8deg);
                    opacity: 0.8;
                }
                100% {
                    transform: translate(72vw, -50vh) rotate(15deg);
                    opacity: 0.6;
                }
            }
            @keyframes rocket-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .rocket-flight {
                animation: rocket-flight 6s ease-in-out infinite;
            }
            .rocket-spin {
                animation: rocket-spin 2.5s linear infinite;
            }
        </style>

        <div id="syncLoadingOverlay" class="hidden fixed inset-0 z-50 bg-black/20 backdrop-blur-sm">
            <div class="absolute inset-0 overflow-hidden">
                <div class="rocket-flight absolute -left-20 bottom-6 text-6xl">🚀</div>
                <div class="h-full flex items-center justify-center p-6">
                    <div class="bg-white rounded-2xl p-6 shadow-xl flex items-center gap-4 max-w-sm mx-4">
                        <div class="relative h-20 w-20 flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full border border-slate-200 opacity-40 rocket-spin"></div>
                            <div class="relative h-16 w-16 rounded-full bg-slate-100 text-3xl flex items-center justify-center">
                                🚀
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Roket sinkronisasi sedang terbang...</p>
                            <p class="text-sm text-slate-500">Tunggu sebentar, data segera mendarat.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert sync --}}
        @if($needsSync)
        <div class="bg-[#F59E0B]/10 border border-[#F59E0B]/20 rounded-xl p-4 mb-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-[#F59E0B] text-xl">⚠️</span>
                <div>
                    <p class="text-sm font-medium text-amber-800">Data absensi perlu disinkronkan</p>
                    <p class="text-xs text-amber-600">
                        {{ $lastSync ? 'Terakhir sync: ' . \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Belum pernah sync hari ini' }}
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('dashboard.sync') }}" id="dashboardSyncFormAlert">
                @csrf
                <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600 transition">
                    🔄 Sync Sekarang
                </button>
            </form>
        </div>
        @endif

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Karyawan</span>
                    <span class="text-2xl">👥</span>
                </div>
                <div class="text-3xl font-bold text-slate-800">{{ $totalKaryawan }}</div>
                <div class="text-xs text-slate-400 mt-1">Karyawan aktif</div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Hadir Hari Ini</span>
                    <span class="text-2xl">✅</span>
                </div>
                <div class="text-3xl font-bold text-green-600">{{ $hadirHariIni }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ date('d M Y') }}</div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Tidak Hadir</span>
                    <span class="text-2xl">⚠️</span>
                </div>
                <div class="text-3xl font-bold text-amber-500">{{ $tidakHadir }}</div>
                <div class="text-xs text-slate-400 mt-1">Estimasi hari ini</div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Status Mesin</span>
                    <span class="text-2xl">🖥️</span>
                </div>
                @foreach($mesinStatus as $mesin)
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-slate-600 truncate max-w-[120px]">{{ $mesin['name'] }}</span>
                        @if($mesin['online'])
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">Online</span>
                        @else
                            <span class="text-xs bg-[#EF4444]/10 text-[#EF4444] px-2 py-0.5 rounded-full font-medium">Offline</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Info Sistem</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Database</span>
                        <span class="font-medium text-slate-700">hris_waj</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Total Log Absensi</span>
                        <span class="font-medium text-slate-700">{{ number_format(\App\Models\AttendanceLog::count()) }} records</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Terakhir Sync</span>
                        <span class="font-medium text-slate-700">
                            {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->format('d M Y H:i') : '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm mt-2">
                        <span class="text-slate-500">Sync Manual</span>
                        <form method="POST" action="{{ route('dashboard.sync') }}" id="dashboardSyncFormManual">
                            @csrf
                            <button type="submit" class="text-xs bg-[#4F46E5] text-white px-3 py-1 rounded-lg hover:bg-[#4338CA]">
                                🔄 Sync
                            </button>
                        </form>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Role Anda</span>
                        <span class="font-medium text-slate-700 uppercase">{{ $user->role }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E7EB] p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Akses Cepat</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('karyawan.index') }}"
                        class="flex flex-col items-center justify-center p-4 bg-[#F8FAFC] rounded-xl hover:bg-slate-100 transition text-center">
                        <span class="text-2xl mb-1">👥</span>
                        <span class="text-xs font-medium text-slate-600">Data Karyawan</span>
                    </a>
                    <a href="{{ route('karyawan.sync') }}"
                        class="flex flex-col items-center justify-center p-4 bg-[#F8FAFC] rounded-xl hover:bg-slate-100 transition text-center">
                        <span class="text-2xl mb-1">🔄</span>
                        <span class="text-xs font-medium text-slate-600">Sinkron Mesin</span>
                    </a>
                    <a href="{{ route('absensi.index') }}"
                        class="flex flex-col items-center justify-center p-4 bg-[#F8FAFC] rounded-xl hover:bg-slate-100 transition text-center">
                        <span class="text-2xl mb-1">📋</span>
                        <span class="text-xs font-medium text-slate-600">Data Absensi</span>
                    </a>
                    <a href="{{ route('setting.index') }}"
                        class="flex flex-col items-center justify-center p-4 bg-[#F8FAFC] rounded-xl hover:bg-slate-100 transition text-center">
                        <span class="text-2xl mb-1">⚙️</span>
                        <span class="text-xs font-medium text-slate-600">Setting</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSyncLoading() {
            const overlay = document.getElementById('syncLoadingOverlay');
            if (!overlay) return;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');

            document.querySelectorAll('#dashboardSyncFormAlert button, #dashboardSyncFormManual button').forEach(function(button) {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
            });
        }

        document.getElementById('dashboardSyncFormAlert')?.addEventListener('submit', function() {
            showSyncLoading();
        });

        document.getElementById('dashboardSyncFormManual')?.addEventListener('submit', function() {
            showSyncLoading();
        });
    </script>
@endsection
