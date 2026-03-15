<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can_send_broadcast, 403);

        $duyurular = BroadcastNotification::with('sender')
            ->where('sender_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.broadcast.index', compact('duyurular'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can_send_broadcast, 403);

        $kullanicilar = User::with('agency')->orderBy('name')->get(['id', 'name', 'email', 'role']);
        return view('admin.broadcast.create', compact('kullanicilar'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can_send_broadcast, 403);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'message'           => 'required|string|max:1000',
            'emoji'             => 'nullable|string|max:8',
            'target'            => 'required|in:all,acenteler,adminler,secili',
            'target_user_ids'   => 'nullable|array',
            'target_user_ids.*' => 'integer|exists:users,id',
            'channels'          => 'nullable|array',
            'channels.*'        => 'in:push,sms,email',
            'scheduled_at'      => 'nullable|date|after:now',
        ]);

        // En az bir kanal zorunlu; hiç gönderilmemişse push default
        $channels = ! empty($validated['channels']) ? $validated['channels'] : ['push'];

        $zamanla = ! empty($validated['scheduled_at']);

        $broadcast = BroadcastNotification::create([
            'title'           => $validated['title'],
            'message'         => $validated['message'],
            'emoji'           => $validated['emoji'] ?? null,
            'target'          => $validated['target'],
            'target_user_ids' => $validated['target_user_ids'] ?? null,
            'channels'        => $channels,
            'status'          => $zamanla ? 'scheduled' : 'draft',
            'scheduled_at'    => $zamanla ? $validated['scheduled_at'] : null,
            'sender_id'       => auth()->id(),
        ]);

        if (! $zamanla) {
            (new BroadcastService())->send($broadcast);
        }

        $mesaj = $zamanla
            ? 'Duyuru zamanlandı: ' . \Carbon\Carbon::parse($validated['scheduled_at'])->format('d.m.Y H:i')
            : "Duyuru {$broadcast->sent_count} kullanıcıya gönderildi.";

        return redirect()->route('admin.broadcast.index')->with('success', $mesaj);
    }
}
