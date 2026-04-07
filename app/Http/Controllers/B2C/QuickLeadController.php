<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickLeadController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:120',
            'phone'        => 'required|string|max:30',
            'email'        => 'nullable|email|max:180',
            'service_type' => 'nullable|string|max:50',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::table('b2c_quick_leads')->insert([
            'name'         => $validated['name'],
            'phone'        => $validated['phone'],
            'email'        => $validated['email'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Talebiniz alındı.']);
        }

        return back()->with('success', 'Talebiniz alındı! En kısa sürede sizi arayacağız.');
    }
}
