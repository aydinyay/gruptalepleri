<?php

namespace App\Services;

use Illuminate\Support\Str;
use DB;

class GtpnrService
{
    private array $prefixes = [
        'group_flight' => 'UG',
        'charter'      => 'CT',
        'jet'          => 'JT',
        'dinner_cruise'=> 'DC',
        'yacht'        => 'YT',
    ];

    public function generate(string $type = 'group_flight'): string
    {
        $prefix = $this->prefixes[$type] ?? 'UG';

        do {
            $code = $prefix . '-' . strtoupper(Str::random(6));
        } while (DB::table('requests')->where('gtpnr', $code)->exists());

        return $code;
    }
}