<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserBookmark extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'bookmark_id',
        'user_id',
        'category_id',
        'sub_category_id',
        'add_to',
    ];
    public function bookmark()
    {
        return $this->belongsTo(Bookmark::class, 'bookmark_id');
    }
}
