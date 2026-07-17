@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-semibold text-slate-800 mb-5">Setting</h1>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-3 mb-4">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl p-3 mb-4">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mb-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">👤 Profil Saya</h3>
        <form method="POST" action="{{ route('setting.profile') }}" class="grid grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="text-xs text-slate-500 mb-1 block">Nama</label>
                <input type="text" name="name" value="{{ $currentUser->name }}" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div>
                <label class="text-xs text-slate-500 mb-1 block">Username</label>
                <input type="text" value="{{ $currentUser->username }}" disabled
                    class="w-full bg-slate-50 border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm text-slate-400">
            </div>
            <div>
                <label class="text-xs text-slate-500 mb-1 block">Password Saat Ini *</label>
                <input type="password" name="current_password" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div></div>
            <div>
                <label class="text-xs text-slate-500 mb-1 block">Password Baru (opsional)</label>
                <input type="password" name="new_password"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div>
                <label class="text-xs text-slate-500 mb-1 block">Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="col-span-2">
                <button type="submit" class="pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </span>
                    <span>Simpan Profil</span>
                </button>
            </div>
        </form>
    </div>

    @if($currentUser->role === 'admin')
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mb-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-800">🔐 Manajemen Akun</h3>
            <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                class="pbtn pbtn-primary pbtn-sm">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
                <span>Tambah Akun</span>
            </button>
        </div>

        <div class="bg-white rounded-[11px] border border-[#C8D9E6] overflow-hidden shadow-[0_1px_4px_rgba(47,65,86,.06)]">
        <table class="w-full text-[12.5px] text-[#2F4156]">
            <thead class="bg-[#EAF1F6] border-b border-[#C8D9E6]">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Nama</th>
                    <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Username</th>
                    <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Role</th>
                    <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody class="border-t border-[#EAF1F6]">
                @foreach($users as $u)
                <tr class="border-b border-[#EAF1F6] last:border-0 hover:bg-[#F9FBFD] transition-colors">
                    <td class="px-4 py-3 font-medium">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-[#567C8D] font-mono text-[11px]">{{ $u->username }}</td>
                    <td class="px-4 py-3">
                        <span class="text-[10px] bg-[#EAF1F6] text-[#2F4156] px-2 py-0.5 rounded-full uppercase font-medium">{{ $u->role }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($u->is_active)
                            <span class="badge-success">Aktif</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full bg-[#FEE2E2] text-[#B91C1C] text-[10px] font-medium">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openEditUserModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->username }}', '{{ $u->role }}')"
                                class="pbtn pbtn-secondary pbtn-sm">
                                Edit
                            </button>
                            @if($u->id !== $currentUser->id)
                            <form method="POST" action="{{ route('setting.users.toggle', $u->id) }}" class="inline">
                                @csrf @method('PUT')
                                <button type="submit" class="pbtn pbtn-warning pbtn-sm">
                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('setting.users.destroy', $u->id) }}" class="inline"
                                onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="pbtn pbtn-danger pbtn-sm">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mb-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">🖥️ Web Management Mesin</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach($machines as $m)
            <a href="http://{{ $m['ip'] }}:{{ $m['port'] }}" target="_blank"
                class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition border border-[#E5E7EB]">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ $m['name'] }}</p>
                    <p class="text-xs text-slate-400 font-mono">{{ $m['ip'] }}:{{ $m['port'] }}</p>
                </div>
                <span class="text-[#4F46E5] text-sm">→ Buka</span>
            </a>
            @endforeach
        </div>
    </div>

    @if($currentUser->role === 'admin')
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mb-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">💾 Backup Database</h3>
        <p class="text-xs text-slate-400 mb-4">
            Download salinan database (.sql) sebagai cadangan. Proses ini mungkin memakan waktu beberapa detik untuk database besar.
        </p>
        <a href="{{ route('setting.backup') }}"
            class="pbtn pbtn-primary">
            ⬇️ Download Backup (.sql)
        </a>
    </div>
    @endif
</div>

<div id="addUserModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-slate-800">Tambah Akun</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400">✕</button>
        </div>
        <form method="POST" action="{{ route('setting.users.store') }}">
            @csrf
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Nama</label>
                <input type="text" name="name" required class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Username</label>
                <input type="text" name="username" required class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
            </div>
            <div class="mb-4">
                <label class="text-xs text-slate-500 mb-1 block">Role</label>
                <select name="role" required class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                    <option value="admin">Admin</option>
                    <option value="hrd">HRD</option>
                    <option value="payroll">Payroll</option>
                    <option value="ga">GA</option>
                </select>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                    class="pbtn pbtn-secondary">Batal</button>
                <button type="submit" class="pbtn pbtn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="editUserModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-slate-800">Edit Akun</h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400">✕</button>
        </div>
        <form id="editUserForm" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Nama</label>
                <input type="text" name="name" id="edit_user_name" required class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Username</label>
                <input type="text" name="username" id="edit_user_username" required class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Password Baru (kosongkan jika tidak diubah)</label>
                <input type="password" name="password" minlength="6" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
            </div>
            <div class="mb-4">
                <label class="text-xs text-slate-500 mb-1 block">Role</label>
                <select name="role" id="edit_user_role" required class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                    <option value="admin">Admin</option>
                    <option value="hrd">HRD</option>
                    <option value="payroll">Payroll</option>
                    <option value="ga">GA</option>
                </select>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')"
                    class="pbtn pbtn-secondary">Batal</button>
                <button type="submit" class="pbtn pbtn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(id, name, username, role) {
    document.getElementById('edit_user_name').value = name;
    document.getElementById('edit_user_username').value = username;
    document.getElementById('edit_user_role').value = role;
    document.getElementById('editUserForm').action = '/setting/users/' + id;
    document.getElementById('editUserModal').classList.remove('hidden');
}
</script>
@endsection
