@extends('layouts.app')

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Daftar TL & Bawahan</h1>
        <p class="text-xs text-slate-400 mt-1">Klik kartu TL untuk melihat & mengatur anggota. Drag & drop bawahan antar TL.</p>
    </div>

    @forelse($groupedTls as $group)
        <div class="mb-7">
            {{-- Section header --}}
            <div class="flex items-center gap-2 mb-3">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ $group['name'] }}</h2>
                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">{{ $group['tls']->count() }} TL</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($group['tls'] as $tl)
                    @php $countAnggota = $tl->anggota->count(); @endphp
                    <div class="tl-card bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden transition-shadow duration-200 hover:shadow-md">
                        {{-- HEADER --}}
                        <div class="tl-card-header flex flex-col bg-[#2F4156] text-white cursor-pointer select-none rounded-t-xl" role="button" tabindex="0">
                            <div class="flex items-center justify-between gap-2 px-3 pt-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="tl-chevron text-[10px] text-[#C8D9E6] transition-transform duration-200 shrink-0">▶</span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium truncate">{{ $tl->nama ?? 'Tanpa Nama' }}</div>
                                        <div class="text-[10px] text-[#C8D9E6] truncate">{{ $tl->nip ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-[10px] bg-white/15 px-2 py-0.5 rounded-full leading-tight">TL</span>
                                    @if($countAnggota > 0)
                                        <span class="tl-count text-[10px] bg-white/15 px-1.5 py-0.5 rounded-full leading-tight">{{ $countAnggota }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-3 pb-2.5 pt-1">
                                <button type="button" class="tl-paste-btn hidden inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-medium text-white/90 bg-white/10 hover:bg-white/20 hover:text-white hover:scale-105 active:scale-95 transition-all">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                    <span>Tempel</span>
                                </button>
                                <button type="button" class="tl-add-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[11px] font-semibold text-white shadow-sm bg-gradient-to-r from-[#1B7A4A] to-[#239F5E] hover:from-[#16693E] hover:to-[#1D8B50] hover:shadow-md hover:scale-105 active:scale-95 transition-all">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>

                        {{-- BODY (collapsed) --}}
                        <div class="tl-card-body transition-all duration-250 ease-in-out" style="max-height: 0; overflow: hidden; opacity: 0;">
                            <div class="sortable-list p-2.5 space-y-1.5 min-h-[60px]" data-tl-id="{{ $tl->id }}">
                                @forelse($tl->anggota as $anggota)
                                    <div class="sortable-item flex items-center justify-between gap-2 rounded-lg border border-[#E5E7EB] bg-[#F8FAFC] px-2.5 py-2 cursor-grab active:cursor-grabbing hover:bg-white transition-colors" data-user-id="{{ $anggota->id }}">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-slate-700 truncate">{{ $anggota->nama ?? 'Tanpa Nama' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $anggota->nip ?? '-' }}</div>
                                        </div>
                                        <span class="grip-icon text-[13px] text-[#C8D9E6] cursor-grab active:cursor-grabbing shrink-0 leading-none">⠿</span>
                                        <button type="button" class="copy-btn text-[11px] text-slate-400 hover:text-[#2F4156] cursor-pointer shrink-0 leading-none p-0.5 transition-colors" title="Copy" data-user-id="{{ $anggota->id }}">📋</button>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-[#C8D9E6] bg-[#F8FAFC] p-3 text-xs text-slate-400 text-center">
                                        Belum ada bawahan.
                                    </div>
                                @endforelse
                            </div>

                            {{-- Checker name untuk Cabut --}}
                            @if(isset($group['checkers'][$tl->id]))
                                <div class="px-2.5 pb-2 text-[10px] text-slate-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 rounded-full bg-slate-300"></span>
                                    Checker: {{ $group['checkers'][$tl->id] }}
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-[#C8D9E6] bg-white p-8 text-center text-sm text-slate-400">
            Belum ada data TL yang tersedia.
        </div>
    @endforelse
</div>

{{-- Modal Tambah Anggota --}}
<div id="addMemberModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40" role="dialog">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-800">Tambah Anggota</h3>
            <button type="button" class="modal-close text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
        </div>
        <div class="p-5 flex-1 overflow-y-auto">
            <input type="text" id="memberSearch" placeholder="Cari nama atau NIP..."
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 mb-4">
            <div id="searchResults" class="space-y-1 max-h-60 overflow-y-auto">
                <p class="text-xs text-slate-400 text-center py-6">Ketik minimal 2 karakter untuk mencari...</p>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-slate-200 text-xs text-slate-400 flex items-center justify-between">
            <span id="selectedCount">0 dipilih</span>
            <div class="flex gap-2">
                <button type="button" class="modal-close pbtn pbtn-secondary text-xs">Batal</button>
                <button type="button" id="confirmAddMember" class="pbtn pbtn-primary text-xs" disabled>Tambahkan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // ── Clipboard state ──
        let copiedUserId = null;
        const CLIPBOARD_TIMEOUT = 10000;

        // ── Collapse / Expand ──
        document.querySelectorAll('.tl-card-header').forEach(header => {
            const card = header.closest('.tl-card');
            const body = card.querySelector('.tl-card-body');
            const chevron = header.querySelector('.tl-chevron');
            let isOpen = false;

            const toggle = () => {
                isOpen = !isOpen;
                if (isOpen) {
                    body.style.maxHeight = body.scrollHeight + 'px';
                    body.style.opacity = '1';
                    chevron.style.transform = 'rotate(90deg)';
                } else {
                    body.style.maxHeight = '0';
                    body.style.opacity = '0';
                    chevron.style.transform = 'rotate(0deg)';
                }
            };

            const recalc = () => {
                if (isOpen) {
                    body.style.maxHeight = body.scrollHeight + 'px';
                }
            };

            header.addEventListener('click', toggle);
            header.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });

            card._recalcBody = recalc;
        });

        // ── Copy button ──
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                copiedUserId = this.dataset.userId;

                document.querySelectorAll('.copy-btn').forEach(b => {
                    b.textContent = '📋';
                    b.classList.remove('text-[#1B7A4A]');
                });
                this.textContent = '📋✓';
                this.classList.add('text-[#1B7A4A]');

                document.querySelectorAll('.tl-paste-btn').forEach(b => b.classList.remove('hidden'));

                setTimeout(() => {
                    if (copiedUserId) {
                        copiedUserId = null;
                        document.querySelectorAll('.copy-btn').forEach(b => {
                            b.textContent = '📋';
                            b.classList.remove('text-[#1B7A4A]');
                        });
                        document.querySelectorAll('.tl-paste-btn').forEach(b => b.classList.add('hidden'));
                    }
                }, CLIPBOARD_TIMEOUT);
            });
        });

        // ── Paste button ──
        document.querySelectorAll('.tl-paste-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                if (!copiedUserId) return;
                const targetTlId = this.closest('.tl-card').querySelector('.sortable-list').dataset.tlId;
                if (!targetTlId) return;

                try {
                    const response = await fetch('{{ route("tl-bawahan.update") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ user_id: copiedUserId, tl_id: targetTlId }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) { alert(result.message || 'Gagal menempel.'); return; }
                    location.reload();
                } catch (e) { alert('Gagal menempel.'); }
            });
        });

        // ── Add Member Modal ──
        let activeTlId = null;
        let selectedUserIds = new Set();
        let searchTimeout = null;

        const modal = document.getElementById('addMemberModal');
        const searchInput = document.getElementById('memberSearch');
        const resultsDiv = document.getElementById('searchResults');
        const confirmBtn = document.getElementById('confirmAddMember');
        const selectedCount = document.getElementById('selectedCount');

        document.querySelectorAll('.tl-add-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                activeTlId = this.closest('.tl-card').querySelector('.sortable-list').dataset.tlId;
                selectedUserIds.clear();
                updateSelectedCount();
                searchInput.value = '';
                resultsDiv.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">Ketik minimal 2 karakter untuk mencari...</p>';
                confirmBtn.disabled = true;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => searchInput.focus(), 100);
            });
        });

        function closeModal() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            activeTlId = null;
        }

        document.querySelectorAll('.modal-close').forEach(el => el.addEventListener('click', closeModal));
        modal.addEventListener('click', function (e) { if (e.target === this) closeModal(); });

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const q = this.value.trim();
            if (q.length < 2) {
                resultsDiv.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">Ketik minimal 2 karakter...</p>';
                confirmBtn.disabled = true;
                return;
            }
            searchTimeout = setTimeout(() => searchUsers(q), 300);
        });

        async function searchUsers(q) {
            try {
                const resp = await fetch(`{{ route("tl-bawahan.search-users") }}?q=${encodeURIComponent(q)}&exclude_tl_id=${activeTlId || ''}`);
                const users = await resp.json();
                if (!users.length) {
                    resultsDiv.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">Tidak ditemukan.</p>';
                    confirmBtn.disabled = true;
                    return;
                }
                resultsDiv.innerHTML = users.map(u => `
                    <div class="search-result-item flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 cursor-pointer hover:bg-slate-50 transition ${selectedUserIds.has(u.id) ? 'selected' : ''}" data-user-id="${u.id}">
                        <div>
                            <div class="text-sm font-medium text-slate-700">${u.nama}</div>
                            <div class="text-[10px] text-slate-400">${u.nip || '-'}${u.current_tl ? ' · TL saat ini: ' + u.current_tl : ''}</div>
                        </div>
                        <span class="text-xs ${selectedUserIds.has(u.id) ? 'text-[#1B7A4A]' : 'text-slate-300'}">${selectedUserIds.has(u.id) ? '✓' : '+ '}</span>
                    </div>
                `).join('');

                resultsDiv.querySelectorAll('.search-result-item').forEach(el => {
                    el.addEventListener('click', function () {
                        const uid = parseInt(this.dataset.userId);
                        if (selectedUserIds.has(uid)) selectedUserIds.delete(uid);
                        else selectedUserIds.add(uid);
                        this.classList.toggle('selected');
                        const sign = this.querySelector('span:last-child');
                        if (selectedUserIds.has(uid)) { sign.textContent = '✓'; sign.className = 'text-xs text-[#1B7A4A]'; }
                        else { sign.textContent = '+ '; sign.className = 'text-xs text-slate-300'; }
                        updateSelectedCount();
                    });
                });
                confirmBtn.disabled = selectedUserIds.size === 0;
            } catch (e) {
                resultsDiv.innerHTML = '<p class="text-xs text-red-400 text-center py-6">Gagal mencari.</p>';
            }
        }

        function updateSelectedCount() {
            selectedCount.textContent = selectedUserIds.size + ' dipilih';
            confirmBtn.disabled = selectedUserIds.size === 0;
        }

        confirmBtn.addEventListener('click', async function () {
            if (selectedUserIds.size === 0 || !activeTlId) return;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Menyimpan...';
            try {
                for (const uid of selectedUserIds) {
                    await fetch('{{ route("tl-bawahan.update") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ user_id: uid, tl_id: activeTlId }),
                    });
                }
                closeModal();
                location.reload();
            } catch (e) { alert('Gagal menambahkan anggota.'); confirmBtn.disabled = false; confirmBtn.textContent = 'Tambahkan'; }
        });

        // ── SortableJS ──
        document.querySelectorAll('.sortable-list').forEach(list => {
            new Sortable(list, {
                group: 'shared-tl',
                animation: 180,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                handle: '.sortable-item',
                draggable: '.sortable-item',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: async function (evt) {
                    const userId = evt.item?.dataset?.userId;
                    const targetTlId = evt.to?.dataset?.tlId;

                    if (!userId || !targetTlId) {
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("tl-bawahan.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ user_id: userId, tl_id: targetTlId }),
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            evt.from.appendChild(evt.item);
                            alert(result.message || 'Gagal memindahkan bawahan.');
                            return;
                        }

                        // ── Update counts & recalc heights ──
                        document.querySelectorAll('.tl-card').forEach(c => {
                            const items = c.querySelectorAll('.sortable-list > .sortable-item:not(.sortable-ghost)');
                            const badge = c.querySelector('.tl-count');
                            const headerEl = c.querySelector('.tl-card-header');

                            if (badge) {
                                if (items.length > 0) {
                                    badge.textContent = items.length;
                                    badge.style.display = '';
                                } else {
                                    badge.style.display = 'none';
                                }
                            } else if (items.length > 0) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'tl-count text-[10px] bg-white/15 px-1.5 py-0.5 rounded-full leading-tight';
                                newBadge.textContent = items.length;
                                headerEl.querySelector('.flex.items-center.gap-1\\.5')?.appendChild(newBadge);
                            }

                            if (c._recalcBody) c._recalcBody();
                        });

                        // ── Empty state untuk card asal ──
                        if (evt.from.querySelectorAll('.sortable-item').length === 0) {
                            const emptyDiv = document.createElement('div');
                            emptyDiv.className = 'rounded-lg border border-dashed border-[#C8D9E6] bg-[#F8FAFC] p-3 text-xs text-slate-400 text-center';
                            emptyDiv.textContent = 'Belum ada bawahan.';
                            evt.from.appendChild(emptyDiv);
                        }

                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 z-50 rounded-lg bg-[#1B7A4A] text-white px-4 py-2 shadow-lg text-sm animate-in slide-in-from-right';
                        toast.textContent = '✓ Berhasil dipindahkan';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 1800);
                    } catch (error) {
                        evt.from.appendChild(evt.item);
                        alert('Gagal memindahkan bawahan.');
                    }
                }
            });
        });
    });
</script>

<style>
    #addMemberModal.flex { display: flex; }
    .search-result-item.selected { background: #E5EDF3 !important; border-color: #567C8D !important; }
    .sortable-ghost {
        opacity: 0.4;
        background: #E5E7EB !important;
        border: 2px dashed #567C8D !important;
    }
    .sortable-drag {
        opacity: 0.9 !important;
        transform: rotate(2deg);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
    }
    @keyframes slide-in-from-right {
        from { transform: translateX(100%); opacity: 0; }
        to   { transform: translateX(0);    opacity: 1; }
    }
    .animate-in.slide-in-from-right {
        animation: slide-in-from-right 0.25s ease-out;
    }
</style>
@endsection
