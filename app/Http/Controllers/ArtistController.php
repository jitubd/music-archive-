<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ArtistController extends Controller
{
    public function index()
    {
        $page = request('page', 1);
        $artists = Cache::remember("artists_index_p{$page}", 3600, function () {
            return Artist::with('genre')
                ->withCount('albums')
                ->orderBy('name')
                ->paginate(20);
        });

        return view('artists.index', compact('artists'));
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        return view('artists.create', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'bio'      => 'nullable|string',
        ]);

        Artist::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'genre_id' => $validated['genre_id'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_artists');
        Cache::forget('dashboard_top_genres');

        return redirect()->route('artists.index')->with('success', 'Artist created.');
    }

    public function show(Artist $artist)
    {
        $artist = Cache::remember("artist_show_{$artist->id}", 3600, function () use ($artist) {
            return Artist::with([
                'genre',
                'albums' => function ($q) {
                    $q->withCount('songs')->orderBy('title');
                },
            ])->find($artist->id);
        });

        return view('artists.show', compact('artist'));
    }

    public function edit(Artist $artist)
    {
        $genres = Genre::orderBy('name')->get();
        return view('artists.edit', compact('artist', 'genres'));
    }

    public function update(Request $request, Artist $artist)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'bio'      => 'nullable|string',
        ]);

        $artist->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'genre_id' => $validated['genre_id'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_artists');
        Cache::forget('dashboard_top_genres');
        Cache::forget("artist_show_{$artist->id}");
        Cache::forget("albums_index_p1");

        return redirect()->route('artists.index')->with('success', 'Artist updated.');
    }

    public function destroy(Artist $artist)
    {
        $artist->delete();
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_artists');
        Cache::forget('dashboard_top_genres');
        Cache::forget("artist_show_{$artist->id}");
        Cache::forget("albums_index_p1");
        return redirect()->route('artists.index')->with('success', 'Artist deleted.');
    }
}
