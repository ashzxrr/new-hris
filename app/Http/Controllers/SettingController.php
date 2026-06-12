<?php

namespace App\Http\Controllers;

use App\Models\AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $users = AuthUser::orderBy('name')->get();
        $currentUser = Auth::guard('admin')->user();
        $machines = config('fingerprint.machines');

        return view('setting.index', compact('users', 'currentUser', 'machines'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:auth_users,username',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,hrd,payroll,ga',
        ]);

        AuthUser::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => 1,
        ]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = AuthUser::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:auth_users,username,' . $id,
            'role'     => 'required|in:admin,hrd,payroll,ga',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name'     => $request->name,
            'username' => $request->username,
            'role'     => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggleUser($id)
    {
        $user = AuthUser::findOrFail($id);

        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'Status akun berhasil diubah.');
    }

    public function destroyUser($id)
    {
        $user = AuthUser::findOrFail($id);

        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('admin')->user();

        $request->validate([
            'name'             => 'required|string|max:100',
            'current_password' => 'required|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password saat ini salah.');
        }

        $data = ['name' => $request->name];

        if ($request->filled('new_password')) {
            $data['password'] = Hash::make($request->new_password);
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function backup()
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $filename = 'backup-' . $dbName . '-' . date('Y-m-d_His') . '.sql';
        $path = storage_path('app/' . $filename);

        // Cari mysqldump.exe di lokasi umum Laragon
        $possiblePaths = [
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
            'mysqldump', // fallback kalau ada di PATH
        ];

        $mysqldumpPath = null;
        foreach ($possiblePaths as $p) {
            if ($p === 'mysqldump' || file_exists($p)) {
                $mysqldumpPath = $p;
                break;
            }
        }

        // Cari otomatis di folder laragon/bin/mysql/*
        if (!$mysqldumpPath || !file_exists($mysqldumpPath)) {
            $glob = glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe');
            if (!empty($glob)) {
                $mysqldumpPath = $glob[0];
            }
        }

        if (!$mysqldumpPath) {
            return back()->with('error', 'mysqldump.exe tidak ditemukan. Cek lokasi instalasi MySQL di Laragon.');
        }

        $command = sprintf(
            '"%s" --user=%s %s --host=%s %s > "%s"',
            $mysqldumpPath,
            escapeshellarg($dbUser),
            $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            $path
        );

        exec($command . ' 2>&1', $output, $resultCode);

        if (!file_exists($path) || filesize($path) === 0) {
            return back()->with('error', 'Gagal membuat backup. Output: ' . implode(' | ', $output) . ' | Path dicoba: ' . $mysqldumpPath);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
