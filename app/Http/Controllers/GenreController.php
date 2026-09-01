<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index()
    {
        $page = request('page', 1);
        $genres = Cache::remember("genres_index_p{$page}", 3600, function () {
            return Genre::withCount('artists')->orderBy('name')->paginate(20);
        });
        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        Genre::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_top_genres');
        Cache::forget("genres_index_p1");

        return redirect()->route('genres.index')->with('success', 'Genre created.');
    }

    public function show(Genre $genre)
    {
        $genre = Cache::remember("genre_show_{$genre->id}", 3600, function () use ($genre) {
            return Genre::with(['artists' => function ($q) {
                $q->withCount('albums')->orderBy('name');
            }])->find($genre->id);
        });

        return view('genres.show', compact('genre'));
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
        ]);

        $genre->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_top_genres');
        Cache::forget("genres_index_p1");
        Cache::forget("genre_show_{$genre->id}");

        return redirect()->route('genres.index')->with('success', 'Genre updated.');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_top_genres');
        Cache::forget("genres_index_p1");
        Cache::forget("genre_show_{$genre->id}");
        return redirect()->route('genres.index')->with('success', 'Genre deleted.');
    }
}
