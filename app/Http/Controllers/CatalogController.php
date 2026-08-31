<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogController extends Controller
{
    public function home()
    {
        return response()->json([
            'genres' => Genre::withCount('artists')->get(),
            'recent_artists' => Artist::with('genre')
                ->latest()
                ->take(12)
                ->get(),
        ]);
    }

    public function genre(string $slug)
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();

        return response()->json([
            'genre' => $genre,
            'artists' => $genre->artists()->get(),
            'songs' => Song::whereHas('album.artist', fn ($q) => $q->where('genre_id', $genre->id))
                ->with('album.artist')
                ->get(),
        ]);
    }

    public function artist(string $slug)
    {
        $artist = Artist::where('slug', $slug)
            ->with(['genre', 'albums.songs'])
            ->firstOrFail();

        return response()->json($artist);
    }

    public function search(Request $request)
    {
        $q = $request->query('q', '');

        $songs = Song::where('title', 'like', "%{$q}%")
            ->orWhereHas('album', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orWhereHas('album.artist', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->with('album.artist')
            ->limit(50)
            ->get();

        return response()->json($songs);
    }

    /**
     * Streams the audio file straight from Drive. The <audio> element's
     * src should point here, e.g. /stream/{song}.
     */
    public function stream(Request $request, Song $song, GoogleDriveService $drive)
    {
        return redirect()->away($drive->getDirectUrl($song->drive_file_id));
    }
}
