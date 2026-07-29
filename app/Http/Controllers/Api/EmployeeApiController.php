<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Employee API — consumed by the waj-attendance external sync system.
 * Read-only endpoint: returns employee list for attendance data sync.
 */
class EmployeeApiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select('pin', 'nama', 'nik', 'is_active');

        // Optional: filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Optional: incremental sync — only users updated since given datetime
        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->updated_since);
        }

        return $query->paginate(500);
    }
}
