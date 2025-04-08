<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBookmark extends Model
{
    protected $table = 'admin_bookmarks';

    protected $fillable = [
        'title',
        'website_url',
        'created_at',
        'updated_at',
    ];
}
