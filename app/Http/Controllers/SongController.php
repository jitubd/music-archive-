<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Song;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SongController extends Controller
{
    public function index()
    {
        $page = request('page', 1);
        $songs = Cache::remember("songs_index_p{$page}", 3600, function () {
            return Song::with('album.artist')
                ->orderBy('title')
                ->paginate(30);
        });

        return view('songs.index', compact('songs'));
    }

    public function create()
    {
        $albums = Album::with('artist')->orderBy('title')->get();
        $tags = Tag::orderBy('name')->get();
        return view('songs.create', compact('albums', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'album_id'     => 'required|exists:albums,id',
            'track_number' => 'nullable|integer|min:1|max:999',
            'mood'         => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'tags'         => 'nullable|array',
            'tags.*'       => 'exists:tags,id',
        ]);

        $song = Song::create([
            'title'        => $validated['title'],
            'album_id'     => $validated['album_id'],
            'track_number' => $validated['track_number'] ?? null,
            'mood'         => $validated['mood'] ?? null,
            'notes'        => $validated['notes'] ?? null,
        ]);

        if (!empty($validated['tags'])) {
            $song->tags()->sync($validated['tags']);
        }

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_songs');
        Cache::forget("album_show_{$validated['album_id']}");
        Cache::forget("songs_index_p1");

        $album = Album::find($validated['album_id']);
        return redirect()->route('albums.show', $album)->with('success', 'Song created.');
    }

    public function show(Song $song)
    {
        $song = Cache::remember("song_show_{$song->id}", 3600, function () use ($song) {
            return Song::with('album.artist', 'tags')->find($song->id);
        });
        return view('songs.show', compact('song'));
    }

    public function edit(Song $song)
    {
        $song->load('tags');
        $albums = Album::with('artist')->orderBy('title')->get();
        $tags = Tag::orderBy('name')->get();
        return view('songs.edit', compact('song', 'albums', 'tags'));
    }

    public function update(Request $request, Song $song)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'album_id'     => 'required|exists:albums,id',
            'track_number' => 'nullable|integer|min:1|max:999',
            'mood'         => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'tags'         => 'nullable|array',
            'tags.*'       => 'exists:tags,id',
        ]);

        $song->update([
            'title'        => $validated['title'],
            'album_id'     => $validated['album_id'],
            'track_number' => $validated['track_number'] ?? null,
            'mood'         => $validated['mood'] ?? null,
            'notes'        => $validated['notes'] ?? null,
        ]);

        $song->tags()->sync($validated['tags'] ?? []);

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_songs');
        Cache::forget("song_show_{$song->id}");
        Cache::forget("album_show_{$song->album_id}");
        Cache::forget("songs_index_p1");

        return redirect()->route('songs.show', $song)->with('success', 'Song updated.');
    }

    public function destroy(Song $song)
    {
        $album = $song->album;
        $albumId = $song->album_id;
        $songId = $song->id;
        $song->delete();
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_recent_songs');
        Cache::forget("song_show_{$songId}");
        Cache::forget("album_show_{$albumId}");
        Cache::forget("songs_index_p1");
        return redirect()->route('albums.show', $album)->with('success', 'Song deleted.');
    }
}
