<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogKategorisi extends Model
{
    protected $table = 'blog_kategorileri';

    protected $fillable = ['ad', 'slug', 'aktif'];

    public function yaziler()
    {
        return $this->hasMany(BlogYazisi::class, 'kategori_id');
    }
}
