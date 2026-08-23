<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->index('title');
            $table->index('album_id');
        });

        Schema::table('artists', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->index('title');
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropIndex(['title', 'album_id']);
        });

        Schema::table('artists', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->dropIndex(['title']);
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
