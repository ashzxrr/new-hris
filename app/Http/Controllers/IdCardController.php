<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class IdCardController extends Controller
{
    public function index(Request $request)
    {
        $bagianList = User::where('is_active', 1)
            ->whereNotNull('bagian')
            ->distinct()
            ->pluck('bagian')
            ->sort()
            ->values();

        $query = User::where('is_active', 1);

        if ($request->filled('bagian')) {
            $query->where('bagian', $request->bagian);
        }

        if ($request->status_cetak == 'belum') {
            $query->whereNull('id_card_printed_at');
        } elseif ($request->status_cetak == 'sudah') {
            $query->whereNotNull('id_card_printed_at');
        }

        if ($request->filled('baru')) {
            $days = (int) $request->baru;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $karyawan = $query->orderBy('bagian')->orderBy('nama')->get();

        return view('idcard.index', compact('karyawan', 'bagianList'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'exists:users,id',
        ]);

        $karyawan = User::whereIn('id', $request->id)
            ->orderBy('bagian')
            ->orderBy('nama')
            ->get();

        // Tandai karyawan yang sudah di-export ID Card-nya
        User::whereIn('id', $request->id)->update([
            'id_card_printed_at' => now(),
        ]);

        $pdf = Pdf::loadView('idcard.print', compact('karyawan'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('id-card.pdf');
    }
}