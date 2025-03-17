<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropUnique('bookmarks_website_url_unique'); // Remove unique constraint
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->unique('website_url'); // Re-add unique constraint if rolled back
        });
    }
};
