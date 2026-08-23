<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id', 'title', 'track_number', 'duration_seconds',
        'drive_file_id', 'mime_type', 'size_bytes', 'mood', 'notes', 'lyrics',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // Convenience accessors so views don't need to reach through album->artist
    public function getArtistAttribute()
    {
        return $this->album->artist;
    }

    public function getGenreAttribute()
    {
        return $this->album->artist->genre;
    }
}
