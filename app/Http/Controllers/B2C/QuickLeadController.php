<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\SmsService;
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

        $id = DB::table('b2c_quick_leads')->insertGetId([
            'name'         => $validated['name'],
            'phone'        => $validated['phone'],
            'email'        => $validated['email'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'locale'       => app()->getLocale(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $adminUrl    = route('superadmin.b2c.quick-leads.index');
        $name        = $validated['name'];
        $phone       = $validated['phone'];
        $serviceType = $validated['service_type'] ?? '';
        $notes       = $validated['notes'] ?? '';

        // Push bildirimi — admin + superadmin paneli
        (new NotificationService())->yeniB2cQuickLead($name, $phone, $serviceType, $adminUrl);

        // Email — tüm admin + superadmin kullanıcılara
        (new EmailService())->yeniB2cQuickLead($name, $phone, $serviceType, $notes, $adminUrl);

        // SMS — admin bildirim numarasına
        $smsMsg = "🌐 B2C Lead: {$name} / {$phone}" . ($serviceType ? " / {$serviceType}" : '');
        (new SmsService())->sendToAdmin(null, $smsMsg);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Talebiniz alındı.']);
        }

        return back()->with('success', 'Talebiniz alındı! En kısa sürede sizi arayacağız.');
    }
}
