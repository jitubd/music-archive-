<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('cover_drive_file_id')->nullable();
            $table->string('drive_folder_id')->nullable();
            $table->timestamps();

            $table->unique(['artist_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
