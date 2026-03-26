<?php

use App\Models\SistemAyar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transfer_suppliers')) {
            Schema::table('transfer_suppliers', function (Blueprint $table): void {
                if (! Schema::hasColumn('transfer_suppliers', 'terms_accepted_at')) {
                    $table->timestamp('terms_accepted_at')->nullable()->after('approved_at');
                }

                if (! Schema::hasColumn('transfer_suppliers', 'terms_version_accepted')) {
                    $table->unsignedInteger('terms_version_accepted')->nullable()->after('terms_accepted_at');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'supplier')
                ->update(['role' => 'acente']);
        }

        if (Schema::hasTable('sistem_ayarlari')) {
            $defaultTerms = trim(<<<'TEXT'
Transfer tedarikcisi olarak listeleyecegim fiyat, kural ve kapasite bilgilerinin dogrulugundan sorumluyum.
Rezervasyon kabul ettigimde hizmeti eksiksiz saglamayi ve iptal/iade kurallarina uymayi kabul ederim.
Platform komisyon ve mutabakat kurallarini okudum, kabul ediyorum.
TEXT);

            SistemAyar::set(
                SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_TEXT,
                (string) SistemAyar::get(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_TEXT, $defaultTerms)
            );

            $version = (int) SistemAyar::get(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_VERSION, '1');
            SistemAyar::set(
                SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_VERSION,
                (string) max(1, $version)
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transfer_suppliers')) {
            Schema::table('transfer_suppliers', function (Blueprint $table): void {
                if (Schema::hasColumn('transfer_suppliers', 'terms_version_accepted')) {
                    $table->dropColumn('terms_version_accepted');
                }

                if (Schema::hasColumn('transfer_suppliers', 'terms_accepted_at')) {
                    $table->dropColumn('terms_accepted_at');
                }
            });
        }
    }
};

