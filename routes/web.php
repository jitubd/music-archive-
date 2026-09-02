<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\SongController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('cache.headers:public;max_age=60');
Route::get('/dashboard/search', [DashboardController::class, 'search'])->name('dashboard.search');

// CRUD resources
Route::resource('genres', GenreController::class)->except(['show']);
Route::get('genres/{genre}', [GenreController::class, 'show'])->name('genres.show')->middleware('cache.headers:public;max_age=60');
Route::resource('artists', ArtistController::class)->except(['show']);
Route::get('artists/{artist}', [ArtistController::class, 'show'])->name('artists.show')->middleware('cache.headers:public;max_age=60');
Route::resource('albums', AlbumController::class)->except(['show']);
Route::get('albums/{album}', [AlbumController::class, 'show'])->name('albums.show')->middleware('cache.headers:public;max_age=60');
Route::resource('songs', SongController::class)->except(['show']);
Route::get('songs/{song}', [SongController::class, 'show'])->name('songs.show')->middleware('cache.headers:public;max_age=60');

// JSON API (for frontend consumption)
Route::get('/api/catalog', [CatalogController::class, 'home']);
Route::get('/api/genres/{slug}', [CatalogController::class, 'genre']);
Route::get('/api/artists/{slug}', [CatalogController::class, 'artist']);
Route::get('/api/search', [CatalogController::class, 'search']);

// Direct stream URL (browser fetches straight from Google Drive)
Route::get('/api/songs/{song}/url', function (App\Models\Song $song, App\Services\GoogleDriveService $drive) {
    return response()->json(['url' => $drive->getDirectUrl($song->drive_file_id)])
        ->header('Cache-Control', 'public, max-age=3600');
})->name('song.url');

// Audio streaming (fallback proxy)
Route::get('/stream/{songId}', [CatalogController::class, 'stream'])->name('stream.api');

// Warm the cache so the first real page load is fast
Route::get('/admin/warm', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }
    try {
        $controller = app(\App\Http\Controllers\DashboardController::class);
        $controller->index()->getData();

        app(\App\Http\Controllers\GenreController::class)->index()->getData();
        app(\App\Http\Controllers\ArtistController::class)->index()->getData();
        app(\App\Http\Controllers\AlbumController::class)->index()->getData();
        app(\App\Http\Controllers\SongController::class)->index()->getData();

        return response()->json([
            'dashboard_cached' => \Illuminate\Support\Facades\Cache::has('dashboard_stats'),
            'genres_index_cached' => \Illuminate\Support\Facades\Cache::has('genres_index_p1'),
            'artists_index_cached' => \Illuminate\Support\Facades\Cache::has('artists_index_p1'),
            'albums_index_cached' => \Illuminate\Support\Facades\Cache::has('albums_index_p1'),
            'songs_index_cached' => \Illuminate\Support\Facades\Cache::has('songs_index_p1'),
            'message' => 'All caches warmed on ' . now()->toDateTimeString() . '. First user load will now be fast.',
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Audio download
Route::get('/download/{song}', function (App\Models\Song $song, App\Services\GoogleDriveService $drive) {
    return redirect($drive->getDirectUrl($song->drive_file_id));
})->name('song.download');

// Lyrics - cache 1 day in browser
Route::get('/api/songs/{song}/lyrics', function (App\Models\Song $song) {
    return response()->json(['lyrics' => $song->lyrics])
        ->header('Cache-Control', 'public, max-age=86400');
});

// Album cover art from online (iTunes) - cache 7 days in browser
Route::get('/api/albums/{album}/art', function (App\Models\Album $album, App\Services\AlbumArtService $art) {
    $url = $art->getArtworkUrl($album);
    return response()->json(['artwork_url' => $url, 'year' => $album->year])
        ->header('Cache-Control', 'public, max-age=604800');
});

// Album cover art from Drive
Route::get('/cover/{album}', function (App\Models\Album $album, App\Services\GoogleDriveService $drive) {
    if (!$album->cover_drive_file_id) {
        abort(404);
    }
    return $drive->streamFile($album->cover_drive_file_id, null, 'image/jpeg');
})->name('cover.stream');

// Google Drive OAuth
Route::get('/auth/google', function (App\Services\GoogleDriveService $drive) {
    return redirect($drive->getAuthUrl());
})->name('auth.google');

Route::get('/auth/callback', function (Illuminate\Http\Request $request, App\Services\GoogleDriveService $drive) {
    $code = $request->input('code');
    if (!$code) {
        return redirect('/')->with('error', 'Authorization failed: no code received.');
    }
    try {
        $drive->exchangeCode($code);
        return redirect('/')->with('success', 'Google Drive authorized successfully!');
    } catch (\Exception $e) {
        return redirect('/')->with('error', 'Authorization failed: ' . $e->getMessage());
    }
});

// Auto-import page - runs all batches automatically
Route::get('/admin/auto-import', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }
    return response()->view('auto-import', ['secret' => $secret])->header('Content-Type', 'text/html');
});

Route::get('/admin/auto-lyrics', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }
    return response()->view('auto-lyrics', ['secret' => $secret])->header('Content-Type', 'text/html');
});

// List artists still missing a genre (diagnostic)
Route::get('/admin/unassigned-artists', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }
    return response()->json(
        \App\Models\Artist::whereNull('genre_id')->orderBy('name')->pluck('name')
    );
});

// Assign genres to artists via keyword matching
Route::get('/admin/assign-genres', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }

    // Create genres and map keywords -> genre names
    $genres = [
        'Rock'              => ['rock', 'springsteen', 'led zeppelin', 'pink floyd', 'queen', 'rolling stones', 'ac/dc', 'guns n roses', 'the police', 'dire straits', 'metallica', 'nirvana', 'bon jovi', 'foo fighters', 'pearl jam', 'green day', 'scorpions', 'aerosmith', 'van halen', 'def leppard', 'the beatles', 'beatles', 'beatles rearrange', 'inxs', 'radiohead', 'coldplay', 'oasis', 'blur', 'the cure', 'the smiths', 'jarre', 'jeff healey', 'yngwie', 'steve vai', 'joe satriani', 'cream', 'jimi hendrix', 'jim morrison', 'john lennon', 'the who', 'crosby', 'u2', 'deep purple', 'alterbridge', 'audioslave', 'dream theatre', 'eagles', 'eric johnson', 'gary moore', 'guns n', 'journey', 'marillion', 'mark knofler', 'mr.big', 'mr. big', 'ozzy osbourne', 'rainbow', 'red hot chilli peppers', 'sting', 'uriah heep', 'bruce dickinson', 'dio', 'camel', 'doors', 'richard wright', 'grunge', 'hard rock', 'progressive', 'yes', 'genesis', 'king crimson', 'rush', 'the band', 'toto', 'john mellencamp', 'tom petty', 'bruce springsteen', 'creed', 'alter bridge', 'jimi', 'hendrix', 'yngwie malmsteen', 'alan parsons', 'travis', 'al stewart', 'chris rea', 'vademola', 'nail young', 'neil young', 'mega'],
        'Pop'               => ['pop', 'madonna', 'michael jackson', 'britney', 'taylor swift', 'adele', 'katy perry', 'rihanna', 'justin', 'ed sheeran', 'ariana', 'maroon', 'backstreet', 'nsync', 'whitney', 'mariah', 'elton john', 'elton jone', 'phil collins', 'george michael', 'george micheal', 'prince', 'jackson 5', 'lady gaga', 'bruno mars', 'lizzo', 'demi lovato', 'selena', 'air supply', 'akon', 'bryan adams', 'chris de burgh', 'dido', 'james blaunt', 'jewel', 'jim croce', 'lionel richie', 'lobo', 'richard marx', 'semisonic', 'spice girls', 'kelly clarkson', 'avril', 'p!nk', 'christina aguilera', 'nsync', 'spice', 'poets of the fall', 'jason mraz', 'john legend', 'sade', 'mika', 'nail diamond', 'neil diamond'],
        'Classical'         => ['classical', 'mozart', 'beethoven', 'bach', 'chopin', 'vivaldi', 'tchaikovsky', 'handel', 'paganini', 'ludovico', 'yiruma', 'lang lang', 'vienna', 'philharmonic', 'orchestra', 'chamber', 'concerto', 'sonata', 'symphony', 'nocturne', 'piano', 'richard clayderman', 'clayderman', 'vanessa mae', 'vanessa-mae', 'kenny g', 'instrumental', 'music(instrumental)'],
        'Jazz'              => ['jazz', 'miles davis', 'john coltrane', 'louis armstrong', 'duke ellington', 'ella fitzgerald', 'nina simone', 'chet baker', 'diana krall', 'norah jones', 'billie holiday', 'count basie', 'herbie hancock', 'thelonious', 'frank sinatra', 'chet atkins', 'neck & neck'],
        'Blues'             => ['blues', 'bb king', 'b.b. king', 'muddy waters', 'howlin', 'john lee hooker', 'robert johnson', 'buddy guy', 'srv', 'stevie ray', 'eric clapton', 'john mayer'],
        'R&B/Soul'          => ['r&b', 'rnb', 'soul', 'motown', 'stevie wonder', 'ray charles', 'james brown', 'aretha', 'marvin gaye', 'al green', 'otis redding', 'luther vandross', 'boyz ii men', 'usher', 'alicia keys', 'john legend', 'sade', 's a d e'],
        'Electronic'        => ['electronic', 'edm', 'dance', 'techno', 'house', 'trance', 'dubstep', 'daft punk', 'depeche mode', 'kraftwerk', 'the chemical brothers', 'prodigy', 'fatboy slim', 'moby', 'garrix', 'skrillex', 'jean-michel jarre'],
        'Hip-Hop/Rap'       => ['hip', 'hop', 'rap', 'eminem', 'kanye', 'jay-z', 'jay z', 'drake', 'kendrick', 'nas', 'tupac', 'notorious', 'biggie', 'snoop', 'dr. dre', 'wu-tang', 'public enemy', 'run-dmc', 'outkast'],
        'Metal'             => ['metal', 'iron maiden', 'black sabbath', 'slayer', 'megadeth', 'pantera', 'judas priest', 'judas_priest', 'motorhead', 'system of a down', 'slipknot', 'opeth', 'dream theater', 'lamb of god', 'threshold', 'alterbridge', 'mr.big', 'dio', 'ozzy', 'dio'],
        'Country'           => ['country', 'johnny cash', 'dolly parton', 'garth brooks', 'willy nelson', 'hank williams', 'kenny rogers', 'shania', 'carrie underwood', 'taylor', 'don mclean', 'j.denver', 'john denver', 'joan baez', 'jim croce', 'everly bros', 'everly brothers'],
        'Folk'              => ['folk', 'bob dylan', 'joni mitchell', 'simon & garfunkel', 'simon and garfunkel', 'pete seeger', 'leonard cohen', 'cat stevens', 'tracy chapman', 'vance joy', 'ed sheeran', 'bread', 'david gates', 'don mclean', 'john denver', 'j.denver', 'jim croce', 'cat stevens', 'james taylor', 'carole king'],
        'Reggae'            => ['reggae', 'bob marley', 'peter tosh', 'jimmy cliff', 'shaggy', 'sean paul'],
        'Latin'             => ['latin', 'salsa', 'reggaeton', 'bossa nova', 'shakira', 'ricky martin', 'juanes', 'enrique', 'santana', 'buena vista', 'gipsy kings'],
        'New Age/Ambient'   => ['yanni', 'enya', 'enigma', 'vangelis', 'kitaro', 'george winston', 'new age', 'ambient', 'relaxation', 'instrumental', 'kenny g', 'richard clayderman'],
        'Punk'              => ['punk', 'ramones', 'clash', 'sex pistols', 'the who', 'greenday', 'blink'],
        'Indie'             => ['indie', 'arctic monkeys', 'the strokes', 'arcade fire', 'vampire weekend', 'mgmt', 'tame impala', 'florence', 'bon iver', 'fleet foxes'],
        'Soundtrack/OST'    => ['ost', 'soundtrack', 'score', 'original motion', 'open season', 'matrix', 'titanic', 'gladiator', 'inception', 'interstellar', 'forest gump'],
    ];

    $created = 0;
    $assigned = 0;

    $genreIds = [];
    foreach ($genres as $name => $keywords) {
        $slug = \Illuminate\Support\Str::slug($name);
        $genre = \App\Models\Genre::updateOrCreate(['slug' => $slug], ['name' => $name]);
        $genreIds[$slug] = $genre->id;
        $created++;
    }

    $artists = \App\Models\Artist::whereNull('genre_id')->get();

    foreach ($artists as $artist) {
        $lower = mb_strtolower($artist->name);
        foreach ($genres as $name => $keywords) {
            $slug = \Illuminate\Support\Str::slug($name);
            foreach ($keywords as $kw) {
                if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                    $artist->update(['genre_id' => $genreIds[$slug]]);
                    $assigned++;
                    break 2;
                }
            }
        }
    }

    \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
    \Illuminate\Support\Facades\Cache::forget('dashboard_top_genres');
    \Illuminate\Support\Facades\Cache::forget('genres_index_p1');

    return response()->json([
        'genres_created' => \App\Models\Genre::count(),
        'artists_assigned' => $assigned,
        'artists_without_genre' => \App\Models\Artist::whereNull('genre_id')->count(),
        'next' => "/admin/warm?key={$secret}",
    ]);
});

// Protected import route - runs in batches to avoid Render timeout
Route::get('/admin/import', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }

    try {
        $drive = app(\App\Services\GoogleDriveService::class);
        if (!$drive->isAuthorized()) {
            return response()->json(['error' => 'Google Drive not authorized. Visit /auth/google first.'], 401);
        }

        $rootFolderId = '1Lp12tPEogQYr3fuhcAKUZ5Mw_lISP1Fi';
        $batch = (int) request('batch', 0);
        $limit = 3;

        $rootChildren = $drive->listChildren($rootFolderId);
        $rootFolders = array_values(array_filter($rootChildren, fn($f) => $drive->isFolder($f)));
        $totalFolders = count($rootFolders);

        $start = $batch * $limit;
        $end = min($start + $limit, $totalFolders);
        $songsImported = 0;

        for ($i = $start; $i < $end; $i++) {
            $folder = $rootFolders[$i];
            $children = $drive->listChildren($folder['id']);
            $subFolders = array_values(array_filter($children, fn($f) => $drive->isFolder($f)));
            $audioFiles = array_filter($children, fn($f) => $drive->isAudio($f));

            $artist = \App\Models\Artist::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($folder['name'])],
                ['name' => $folder['name'], 'drive_folder_id' => $folder['id']]
            );

            if (!empty($subFolders)) {
                foreach ($subFolders as $sub) {
                    $subChildren = $drive->listChildren($sub['id']);
                    $subAudio = array_filter($subChildren, fn($f) => $drive->isAudio($f));

                    $album = \App\Models\Album::updateOrCreate(
                        ['artist_id' => $artist->id, 'slug' => \Illuminate\Support\Str::slug($sub['name'])],
                        ['title' => $sub['name'], 'drive_folder_id' => $sub['id']]
                    );

                    foreach ($subAudio as $file) {
                        $name = preg_replace('/\.[^.]+$/', '', $file['name']);
                        preg_match('/^(\d{1,3})\s*[-.]\s*(.+)$/', $name, $m);
                        \App\Models\Song::updateOrCreate(
                            ['drive_file_id' => $file['id']],
                            [
                                'album_id' => $album->id,
                                'title' => $m ? trim($m[2]) : trim($name),
                                'track_number' => $m ? (int) $m[1] : null,
                                'mime_type' => $file['mimeType'],
                                'size_bytes' => $file['size'] ?? null,
                            ]
                        );
                        $songsImported++;
                    }
                }
            } elseif (!empty($audioFiles)) {
                $album = \App\Models\Album::updateOrCreate(
                    ['artist_id' => $artist->id, 'slug' => \Illuminate\Support\Str::slug($folder['name'])],
                    ['title' => $folder['name'], 'drive_folder_id' => $folder['id']]
                );

                foreach ($audioFiles as $file) {
                    $name = preg_replace('/\.[^.]+$/', '', $file['name']);
                    preg_match('/^(\d{1,3})\s*[-.]\s*(.+)$/', $name, $m);
                    \App\Models\Song::updateOrCreate(
                        ['drive_file_id' => $file['id']],
                        [
                            'album_id' => $album->id,
                            'title' => $m ? trim($m[2]) : trim($name),
                            'track_number' => $m ? (int) $m[1] : null,
                            'mime_type' => $file['mimeType'],
                            'size_bytes' => $file['size'] ?? null,
                        ]
                    );
                    $songsImported++;
                }
            }
        }

        $nextBatch = ($end < $totalFolders) ? $batch + 1 : null;

        return response()->json([
            'batch' => $batch,
            'folders_processed' => $end . '/' . $totalFolders,
            'songs_imported' => $songsImported,
            'done' => $nextBatch === null,
            'run_next' => $nextBatch !== null ? "/admin/import?key={$secret}&batch={$nextBatch}" : null,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Lyrics fetch - batch from LRCLIB
Route::get('/admin/fetch-lyrics', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(4003);
    }

    try {
        $batch = (int) request('batch', 0);
        $limit = 20;

        $songs = \App\Models\Song::whereNull('lyrics')
            ->orWhere('lyrics', '')
            ->with('album.artist')
            ->skip($batch * $limit)
            ->take($limit)
            ->get();

        $totalMissing = \App\Models\Song::whereNull('lyrics')->orWhere('lyrics', '')->count();
        $found = 0;

        $lyricsService = app(\App\Services\LyricsService::class);

        foreach ($songs as $song) {
            $lrc = $lyricsService->fetchForSong($song);
            if ($lrc) {
                $song->update(['lyrics' => $lrc]);
                $found++;
            }
        }

        $remaining = $totalMissing - $songs->count();
        $nextBatch = $remaining > 0 ? $batch + 1 : null;

        return response()->json([
            'batch' => $batch,
            'checked' => $songs->count(),
            'found' => $found,
            'remaining' => $remaining,
            'done' => $nextBatch === null,
            'run_next' => $nextBatch !== null ? "/admin/fetch-lyrics?key={$secret}&batch={$nextBatch}" : null,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
