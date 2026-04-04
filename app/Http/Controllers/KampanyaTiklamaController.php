<?php

namespace App\Http\Controllers;

use App\Models\TursabDavet;
use Illuminate\Http\Request;

class KampanyaTiklamaController extends Controller
{
    public function izle(string $token)
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false || !str_contains($decoded, '|')) {
            return redirect('/register');
        }

        [$etiket, $belgeNo] = explode('|', $decoded, 2);

        if ($etiket && $belgeNo) {
            TursabDavet::where('kampanya_etiket', $etiket)
                ->where('belge_no', $belgeNo)
                ->where('tip', 'email')
                ->whereNull('tiklanma_at')
                ->update([
                    'tiklanma_at' => now(),
                ]);

            // Tekrar tıklamada sayacı artır
            TursabDavet::where('kampanya_etiket', $etiket)
                ->where('belge_no', $belgeNo)
                ->where('tip', 'email')
                ->increment('tiklanma_sayisi');
        }

        return redirect('/register');
    }
}
