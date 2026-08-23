<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LyricsService
{
    protected string $baseUrl = 'https://lrclib.net';

    /**
     * Fetch synced lyrics for a song from LRCLIB.
     * Returns the LRC synced lyrics string or null.
     */
    public function fetchForSong(Song $song): ?string
    {
        $artist = $song->album->artist->name ?? '';
        $album  = $song->album->title ?? '';
        $title  = $song->title ?? '';
        $duration = $song->duration_seconds ?? 0;

        if (!$artist || !$title) {
            return null;
        }

        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'MusicArchive v1.0 (Laravel)'])
            ->get("{$this->baseUrl}/api/get", array_filter([
                'artist_name' => $artist,
                'track_name'  => $title,
                'album_name'  => $album,
                'duration'    => $duration ?: null,
            ]));

        if ($response->successful()) {
            $data = $response->json();
            return $data['syncedLyrics'] ?? $data['plainLyrics'] ?? null;
        }

        if ($response->status() === 404) {
            return null;
        }

        Log::warning("LRCLIB request failed", [
            'song'   => $song->id,
            'status' => $response->status(),
        ]);

        return null;
    }

    /**
     * Search LRCLIB and return results array.
     */
    public function search(string $artist, string $track, string $album = ''): array
    {
        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'MusicArchive v1.0 (Laravel)'])
            ->get("{$this->baseUrl}/api/search", array_filter([
                'artist_name' => $artist,
                'track_name'  => $track,
                'album_name'  => $album,
            ]));

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }
}
