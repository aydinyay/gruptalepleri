<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('offers')) {
            return;
        }

        Schema::table('offers', function (Blueprint $table) {
            if (! Schema::hasColumn('offers', 'airline_pnr')) {
                $table->string('airline_pnr')->nullable()->after('airline');
            }
            if (! Schema::hasColumn('offers', 'flight_number')) {
                $table->string('flight_number')->nullable()->after('airline_pnr');
            }
            if (! Schema::hasColumn('offers', 'flight_departure_time')) {
                $table->time('flight_departure_time')->nullable()->after('flight_number');
            }
            if (! Schema::hasColumn('offers', 'flight_arrival_time')) {
                $table->time('flight_arrival_time')->nullable()->after('flight_departure_time');
            }
            if (! Schema::hasColumn('offers', 'baggage_kg')) {
                $table->unsignedSmallInteger('baggage_kg')->nullable()->after('flight_arrival_time');
            }
            if (! Schema::hasColumn('offers', 'supplier_reference')) {
                $table->string('supplier_reference')->nullable()->after('offer_text');
            }
            if (! Schema::hasColumn('offers', 'pax_confirmed')) {
                $table->unsignedSmallInteger('pax_confirmed')->nullable()->after('supplier_reference');
            }
            if (! Schema::hasColumn('offers', 'created_by')) {
                $table->string('created_by')->nullable()->after('ai_raw_output');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('offers')) {
            return;
        }

        Schema::table('offers', function (Blueprint $table) {
            $columns = [
                'airline_pnr',
                'flight_number',
                'flight_departure_time',
                'flight_arrival_time',
                'baggage_kg',
                'supplier_reference',
                'pax_confirmed',
                'created_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

