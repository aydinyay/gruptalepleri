<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('email', 'admin@gruptalepleri.com')
            ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
            ->update(['phone' => '905324262630']);

        DB::table('users')->where('email', 'owner@gruptalepleri.com')
            ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
            ->update(['phone' => '905324262630']);

        DB::table('users')->where('email', 'yildiz@turgon.net')
            ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
            ->update(['phone' => '905354154799']);
    }

    public function down(): void {}
};
