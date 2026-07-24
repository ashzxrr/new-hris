<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cek Kehadiran — HRIS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2F4156">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        body { background: #F5EFEB; }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-white border-b border-[#E5E7EB] shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-[#2F4156] rounded-lg">
                        <span class="text-white text-sm font-bold">W</span>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#2F4156]">PT WAJ HRIS</div>
                        <div class="text-[11px] text-slate-400">Cek Kehadiran Karyawan</div>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="text-xs text-[#567C8D] hover:text-[#2F4156] transition">Login</a>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#2F4156]">Cek Kehadiran</h1>
                <p class="text-sm text-slate-500 mt-1">Cari data absensi berdasarkan nama, NIP, atau bagian</p>
            </div>

            {{-- Form Pencarian --}}
            <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-sm p-6 mb-6">
                <form id="formCari" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        {{-- Keyword --}}
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Nama / NIP / Bagian</label>
                            <input type="text" name="keyword" id="keyword" required minlength="2"
                                placeholder="Cari nama, NIP, atau bagian..."
                                class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        </div>

                        {{-- Tanggal Dari --}}
                        <div>
                            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Dari</label>
                            <input type="date" name="tanggal_dari" id="tanggal_dari" value="{{ date('Y-m-01') }}"
                                class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                        </div>

                        {{-- Tanggal Sampai --}}
                        <div>
                            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Sampai</label>
                            <input type="date" name="tanggal_sampai" id="tanggal_sampai" value="{{ date('Y-m-d') }}"
                                class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                        </div>
                    </div>

                    {{-- Quick Range --}}
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <button type="button" onclick="setRange('today')" class="pbtn pbtn-secondary pbtn-sm">Hari Ini</button>
                        <button type="button" onclick="setRange('yesterday')" class="pbtn pbtn-secondary pbtn-sm">Kemarin</button>
                        <button type="button" onclick="setRange('this_month')" class="pbtn pbtn-secondary pbtn-sm">Bulan Ini</button>
                        <button type="button" onclick="setRange('last_month')" class="pbtn pbtn-secondary pbtn-sm">Bulan Lalu</button>
                        <button type="submit" class="pbtn pbtn-primary pbtn-sm ml-auto">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <span>Cari</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Hasil --}}
            <div id="hasilContainer"></div>

            {{-- Loading --}}
            <div id="loading" class="hidden text-center py-12">
                <div class="inline-block w-6 h-6 border-2 border-[#567C8D] border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-400 mt-2">Mencari data...</p>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-[#E5E7EB] py-4 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} PT Walet Abdillah Jabli
        </footer>
    </div>

    <script>
        const form = document.getElementById('formCari');
        const hasilContainer = document.getElementById('hasilContainer');
        const loading = document.getElementById('loading');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const keyword = document.getElementById('keyword').value.trim();
            if (keyword.length < 2) return;

            loading.classList.remove('hidden');
            hasilContainer.innerHTML = '';

            const formData = new FormData(form);

            fetch('{{ route('absensi.publik.cari') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(res => res.json())
            .then(data => {
                hasilContainer.innerHTML = data.html;
            })
            .catch(err => {
                hasilContainer.innerHTML = '<div class="text-center py-12 text-red-500 text-sm">Terjadi kesalahan. Silakan coba lagi.</div>';
            })
            .finally(() => {
                loading.classList.add('hidden');
            });
        });

        function formatLocalDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        // Quick range
        function setRange(mode) {
            const dari = document.getElementById('tanggal_dari');
            const sampai = document.getElementById('tanggal_sampai');
            const today = new Date();

            switch (mode) {
                case 'today':
                    dari.value = sampai.value = formatLocalDate(today);
                    break;
                case 'yesterday':
                    const yes = new Date(today);
                    yes.setDate(yes.getDate() - 1);
                    dari.value = sampai.value = formatLocalDate(yes);
                    break;
                case 'this_month':
                    dari.value = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-01';
                    sampai.value = formatLocalDate(today);
                    break;
                case 'last_month':
                    const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const last = new Date(today.getFullYear(), today.getMonth(), 0);
                    dari.value = formatLocalDate(first);
                    sampai.value = formatLocalDate(last);
                    break;
            }
        }
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>