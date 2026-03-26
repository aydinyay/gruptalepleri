<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('label');           // Görünen ad: "Süper Admin - Güneş"
            $table->string('phone');           // 05321234567 veya virgülle çoklu
            $table->string('event');           // new_agency | new_request | offer_added | offer_accepted | all
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_notification_settings');
    }
};
