<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leisure_package_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('leisure_package_templates', 'cost_price_per_person')) {
                $table->decimal('cost_price_per_person', 10, 2)->nullable()->after('base_price_per_person')
                    ->comment('Tedarikçi maliyet fiyatı — kişi başı (sadece superadmin görür)');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'supplier_name')) {
                $table->string('supplier_name', 120)->nullable()->after('cost_price_per_person')
                    ->comment('Bu şablonu operate eden tedarikçi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leisure_package_templates', function (Blueprint $table): void {
            foreach (['cost_price_per_person', 'supplier_name'] as $col) {
                if (Schema::hasColumn('leisure_package_templates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
