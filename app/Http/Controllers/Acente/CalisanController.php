<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CalisanController extends Controller
{
    use ResolvesPreviewUser;

    public function index()
    {
        $user = $this->acenteActor();
        abort_unless($user->isAcenteOwner(), 403, 'Bu sayfaya sadece acente sahibi erişebilir.');

        $calisanlar = User::where('parent_agency_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('acente.calisanlar', compact('calisanlar'));
    }

    public function davetGonder(Request $request)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $user = $this->acenteActor();
        abort_unless($user->isAcenteOwner(), 403);

        $request->validate([
            'email'      => 'required|email|unique:users,email',
            'acente_rolu' => 'required|in:tam,operasyon,muhasebe',
        ]);

        $token = Str::random(64);

        $calisan = User::create([
            'name'             => $request->email, // geçici, davet kabul edilince güncellenir
            'email'            => $request->email,
            'password'         => Hash::make(Str::random(32)), // geçici
            'role'             => 'acente',
            'parent_agency_id' => $user->id,
            'acente_rolu'      => $request->acente_rolu,
            'davet_token'      => $token,
            'davet_expires_at' => now()->addHours(48),
        ]);

        $davetLink = route('davet.show', $token);
        $ownerName = $user->name;
        $rolLabel  = ['tam' => 'Tam Erişim', 'operasyon' => 'Operasyon', 'muhasebe' => 'Muhasebe'][$request->acente_rolu] ?? $request->acente_rolu;

        try {
            Mail::html(
                "<p>Merhaba,</p>
                <p><strong>{$ownerName}</strong> sizi GrupTalepleri sistemine <strong>{$rolLabel}</strong> rolüyle davet etti.</p>
                <p>Hesabınızı aktifleştirmek için aşağıdaki bağlantıya tıklayın (48 saat geçerlidir):</p>
                <p><a href=\"{$davetLink}\">{$davetLink}</a></p>",
                function ($m) use ($calisan, $ownerName) {
                    $m->to($calisan->email)->subject("GrupTalepleri — {$ownerName} sizi davet etti");
                }
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Davet email hatası: ' . $e->getMessage());
        }

        return back()->with('success', "Davet gönderildi: {$request->email} — Link: {$davetLink}");
    }

    public function sil(Request $request, int $id)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $user = $this->acenteActor();
        abort_unless($user->isAcenteOwner(), 403);

        $calisan = User::where('id', $id)
            ->where('parent_agency_id', $user->id)
            ->firstOrFail();

        $calisan->delete();

        return back()->with('success', 'Çalışan silindi.');
    }

    public function yetkiGuncelle(Request $request, int $id)
    {
        if ($blocked = $this->blockPreviewWrites()) {
            return $blocked;
        }

        $user = $this->acenteActor();
        abort_unless($user->isAcenteOwner(), 403);

        $request->validate(['acente_rolu' => 'required|in:tam,operasyon,muhasebe']);

        $calisan = User::where('id', $id)
            ->where('parent_agency_id', $user->id)
            ->firstOrFail();

        $calisan->update(['acente_rolu' => $request->acente_rolu]);

        return back()->with('success', 'Yetki güncellendi.');
    }
}
