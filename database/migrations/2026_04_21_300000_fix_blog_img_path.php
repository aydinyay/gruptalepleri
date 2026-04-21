<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('blog_yazilari')
            ->where('kapak_gorseli', 'images/blog/Image_6zd6ab6zd6ab6zd6.png')
            ->update(['kapak_gorseli' => 'images/blog-img/Image_6zd6ab6zd6ab6zd6.png']);
    }

    public function down(): void {}
};
