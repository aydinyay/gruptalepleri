<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
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
        } while ($this->exists($code));

        return $code;
    }

    private function exists(string $code): bool
    {
        if (Schema::hasTable('requests') && DB::table('requests')->where('gtpnr', $code)->exists()) {
            return true;
        }

        if (Schema::hasTable('leisure_requests') && DB::table('leisure_requests')->where('gtpnr', $code)->exists()) {
            return true;
        }

        return false;
    }
}
