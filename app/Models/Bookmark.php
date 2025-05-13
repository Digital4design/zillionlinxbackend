<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{


    protected $fillable = [
        'title',
        'user_id',
        'website_url',
        'icon_path',
        'pinned',
        'position',
        'favicon_path',

    ];
    public function userBookmarks()
    {
        return $this->hasMany(UserBookmark::class, 'bookmark_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
