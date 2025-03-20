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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category_name()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category_name()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }
}
