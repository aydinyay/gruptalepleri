<?php

use App\Models\Request;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Request::STATUS_ALIASES as $from => $to) {
            DB::table('requests')->where('status', $from)->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        DB::table('requests')
            ->where('status', Request::STATUS_FIYATLANDIRILDI)
            ->update(['status' => 'fiyatlandir??ldi']);

        DB::table('requests')
            ->where('status', Request::STATUS_DEPOZITODA)
            ->update(['status' => 'depozito']);
    }
};
