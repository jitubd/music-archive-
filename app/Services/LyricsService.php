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

        $params = array_filter([
            'artist_name' => $artist,
            'track_name'  => $title,
            'album_name'  => $album,
            'duration'    => $duration ?: null,
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(2, 2000)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->get("{$this->baseUrl}/api/get", $params);

            if ($response->successful()) {
                $data = $response->json();
                return $data['syncedLyrics'] ?? $data['plainLyrics'] ?? null;
            }

            if ($response->status() === 404) {
                return null;
            }
        } catch (\Exception $e) {
            Log::warning("LRCLIB request failed for song {$song->id}: {$e->getMessage()}");
        }

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
