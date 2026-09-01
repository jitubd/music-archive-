<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('dashboard_stats', 3600, function () {
            return [
                'genres'     => Genre::count(),
                'artists'    => Artist::count(),
                'albums'     => Album::count(),
                'songs'      => Song::count(),
                'tags'       => Tag::count(),
                'total_size' => Song::sum('size_bytes'),
            ];
        });

        $recentSongs = Cache::remember('dashboard_recent_songs', 3600, function () {
            return Song::with('album.artist', 'tags')
                ->latest()
                ->limit(10)
                ->get();
        });

        $recentArtists = Cache::remember('dashboard_recent_artists', 3600, function () {
            return Artist::with('genre')
                ->latest()
                ->limit(6)
                ->get();
        });

        $topGenres = Cache::remember('dashboard_top_genres', 3600, function () {
            return Genre::withCount('artists')->orderByDesc('artists_count')->get();
        });

        return view('dashboard.index', compact('stats', 'recentSongs', 'recentArtists', 'topGenres'));
    }

    public function warm()
    {
        $status = $this->index()->getData();

        return response()->json([
            'dashboard_cached' => Cache::has('dashboard_stats'),
            'recent_songs_cached' => Cache::has('dashboard_recent_songs'),
            'recent_artists_cached' => Cache::has('dashboard_recent_artists'),
            'top_genres_cached' => Cache::has('dashboard_top_genres'),
            'message' => 'Cache warmed. First real page load will now be fast.',
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return redirect()->back()->with('error', 'Search query must be at least 2 characters.');
        }

        $songs = Song::where('title', 'LIKE', "%{$q}%")
            ->orWhereHas('album', fn ($q2) => $q2->where('title', 'LIKE', "%{$q}%"))
            ->orWhereHas('album.artist', fn ($q2) => $q2->where('name', 'LIKE', "%{$q}%"))
            ->with('album.artist')
            ->limit(50)
            ->get();

        $artists = Artist::where('name', 'LIKE', "%{$q}%")->with('genre')->get();
        $albums = Album::where('title', 'LIKE', "%{$q}%")->with('artist')->get();
        $genres = Genre::where('name', 'LIKE', "%{$q}%")->withCount('artists')->get();

        return view('dashboard.search', compact('q', 'songs', 'artists', 'albums', 'genres'));
    }
}
