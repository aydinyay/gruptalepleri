<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\SupplierApplication;
use Illuminate\Http\Request;

class SupplierApplyController extends Controller
{
    public function show()
    {
        return view('b2c.supplier-apply.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_name'   => 'required|string|max:120',
            'company_name'     => 'required|string|max:200',
            'email'            => 'required|email|max:180',
            'phone'            => 'nullable|string|max:30',
            'service_types'    => 'nullable|array',
            'service_types.*'  => 'string|max:50',
            'notes'            => 'nullable|string|max:1000',
        ]);

        SupplierApplication::create([
            'applicant_name'     => $validated['applicant_name'],
            'company_name'       => $validated['company_name'],
            'email'              => $validated['email'],
            'phone'              => $validated['phone'] ?? null,
            'service_types_json' => $validated['service_types'] ?? [],
            'notes'              => $validated['notes'] ?? null,
            'status'             => 'pending',
        ]);

        return redirect()->route('b2c.supplier-apply.show')
            ->with('success', 'Başvurunuz alındı. En kısa sürede sizinle iletişime geçeceğiz.');
    }
}
