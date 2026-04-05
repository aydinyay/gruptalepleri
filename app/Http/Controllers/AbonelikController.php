<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AbonelikController extends Controller
{
    public function confirm(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403);

        return view('abonelik.confirm', compact('user'));
    }

    public function iptal(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403);

        $user->update(['email_unsubscribed' => true]);

        $tekrarUrl = \URL::signedRoute('abonelik.baslat', ['user' => $user->id]);

        return view('abonelik.sonlandi', compact('user', 'tekrarUrl'));
    }

    public function baslat(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403);

        $user->update(['email_unsubscribed' => false]);

        return view('abonelik.sonlandi', ['user' => $user, 'tekrarUrl' => null, 'yenidenUye' => true]);
    }
}
