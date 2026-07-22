@extends('layouts.app')

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Daftar TL & Bawahan</h1>
        <p class="text-xs text-slate-400 mt-1">Atur hubungan Team Leader dan bawahan dengan drag-and-drop.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse($tls as $tl)
            <div class="bg-white rounded-2xl border border-[#E5E7EB] shadow-sm overflow-hidden">
                <div class="bg-[#2F4156] px-4 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-sm">{{ $tl->nama ?? 'Tanpa Nama' }}</h3>
                            <p class="text-[11px] text-[#C8D9E6]">{{ $tl->nip ?? '-' }}</p>
                        </div>
                        <span class="text-[11px] bg-white/15 px-2 py-1 rounded-full">TL</span>
                    </div>
                </div>

                <div class="p-3 min-h-[140px] sortable-list" data-tl-id="{{ $tl->id }}">
                    @forelse($tl->anggota as $anggota)
                        <div class="sortable-item flex items-center justify-between gap-2 rounded-lg border border-[#E5E7EB] bg-[#F8FAFC] px-3 py-2 mb-2 cursor-grab" data-user-id="{{ $anggota->id }}">
                            <div>
                                <div class="text-sm font-medium text-slate-700">{{ $anggota->nama ?? 'Tanpa Nama' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $anggota->nip ?? '-' }}</div>
                            </div>
                            <span class="text-[10px] text-[#567C8D]">Drag</span>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-[#C8D9E6] bg-[#F8FAFC] p-3 text-sm text-slate-400 text-center">
                            Belum ada bawahan.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="lg:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-[#C8D9E6] bg-white p-8 text-center text-sm text-slate-400">
                Belum ada data TL yang tersedia.
            </div>
        @endforelse
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lists = document.querySelectorAll('.sortable-list');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        lists.forEach((list) => {
            new Sortable(list, {
                group: 'shared-tl',
                animation: 150,
                handle: '.sortable-item',
                draggable: '.sortable-item',
                onEnd: async function (evt) {
                    const userId = evt.item?.dataset?.userId;
                    const targetTlId = evt.to?.dataset?.tlId;

                    if (!userId || !targetTlId) {
                        return;
                    }

                    const payload = {
                        user_id: userId,
                        tl_id: targetTlId,
                    };

                    try {
                        const response = await fetch('{{ route("tl-bawahan.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            evt.from.appendChild(evt.item);
                            alert(result.message || 'Gagal memindahkan bawahan.');
                            return;
                        }

                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 z-50 rounded-lg bg-[#1B7A4A] text-white px-4 py-2 shadow-lg text-sm';
                        toast.textContent = 'Berhasil dipindahkan';
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
@endsection
