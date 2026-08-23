<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function index()
    {
        $albums = Album::with('artist')
            ->withCount('songs')
            ->orderBy('title')
            ->paginate(20);

        return view('albums.index', compact('albums'));
    }

    public function create()
    {
        $artists = Artist::orderBy('name')->get();
        return view('albums.create', compact('artists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'artist_id' => 'required|exists:artists,id',
            'year'      => 'nullable|integer|min:1900|max:2100',
        ]);

        $album = Album::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'artist_id' => $validated['artist_id'],
            'year' => $validated['year'] ?? null,
        ]);

        Cache::forget('dashboard_stats');

        return redirect()->route('albums.show', $album)->with('success', 'Album created.');
    }

    public function show(Album $album)
    {
        $album->load([
            'artist.genre',
            'songs' => function ($q) {
                $q->with('tags')->orderBy('track_number');
            },
        ]);

        return view('albums.show', compact('album'));
    }

    public function edit(Album $album)
    {
        $artists = Artist::orderBy('name')->get();
        return view('albums.edit', compact('album', 'artists'));
    }

    public function update(Request $request, Album $album)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'artist_id' => 'required|exists:artists,id',
            'year'      => 'nullable|integer|min:1900|max:2100',
        ]);

        $album->update([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'artist_id' => $validated['artist_id'],
            'year' => $validated['year'] ?? null,
        ]);

        Cache::forget('dashboard_stats');

        return redirect()->route('albums.show', $album)->with('success', 'Album updated.');
    }

    public function destroy(Album $album)
    {
        $artist = $album->artist;
        $album->delete();
        Cache::forget('dashboard_stats');
        return redirect()->route('artists.show', $artist)->with('success', 'Album deleted.');
    }
}
