<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\Request as TalepModel;
use App\Models\User;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function start(User $user, Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
        abort_unless($user->role === 'acente', 404);

        session([
            'acente_preview_user_id' => $user->id,
        ]);

        $redirect = $request->query('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/')) {
            return redirect($redirect)->with('success', 'Acente onizleme modu baslatildi.');
        }

        return redirect()->route('acente.dashboard')->with('success', 'Acente onizleme modu baslatildi.');
    }

    public function startFromRequest(string $gtpnr)
    {
        $authUser = auth()->user();
        abort_unless($authUser, 403);

        // Acentenin bu URL'yi dogrudan acmasi durumunda 403 yerine kendi talep ekranina yonlendir.
        if (! in_array($authUser->role, ['admin', 'superadmin'], true)) {
            $query = TalepModel::where('gtpnr', $gtpnr);
            if ($authUser->role === 'acente') {
                $query->where('user_id', $authUser->id);
            }

            $talep = $query->firstOrFail();

            return redirect()->route('acente.requests.show', $talep->gtpnr);
        }

        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $user = User::where('role', 'acente')->findOrFail($talep->user_id);

        session([
            'acente_preview_user_id' => $user->id,
        ]);

        return redirect()->route('acente.requests.show', $gtpnr)
            ->with('success', 'Acente onizleme modu baslatildi.');
    }

    public function stop()
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);

        session()->forget('acente_preview_user_id');

        return redirect()->route('admin.requests.index')->with('success', 'Acente onizleme modu kapatildi.');
    }
}
