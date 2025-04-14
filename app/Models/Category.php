<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Category extends Model
{
    protected $fillable = ['title', 'slug', 'parent_id', 'user_id', 'position'];
    protected $table = 'categories';
    // Relationship: A category may have subcategories
    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function adminsubcategories()
    {
        return $this->hasMany(Category::class, 'parent_id')->whereNull('user_id');
    }

    // Relationship: A category may belong to a parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = Str::slug($category->title);
        });

        static::updating(function ($category) {
            $category->slug = Str::slug($category->title);
        });
    }
}
