<?php

namespace App\Services;

use App\Models\Album;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AlbumArtService
{
    public function getArtworkUrl(Album $album): ?string
    {
        $cacheKey = 'album_art_' . $album->id;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null;
        }

        $artist = $album->artist->name ?? '';
        $title  = $album->title ?? '';
        $term   = "{$artist} {$title}";

        $response = Http::timeout(5)->get('https://itunes.apple.com/search', [
            'term'      => $term,
            'entity'    => 'album',
            'limit'     => 1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $results = $data['results'] ?? [];
            if (!empty($results[0]['artworkUrl100'])) {
                $url = str_replace('100x100', '600x600', $results[0]['artworkUrl100']);
                Cache::put($cacheKey, $url, 86400);
                return $url;
            }
        }

        Cache::put($cacheKey, '', 86400);
        return null;
    }
}
