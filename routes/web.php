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
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/search', [DashboardController::class, 'search'])->name('dashboard.search');

// CRUD resources
Route::resource('genres', GenreController::class)->except(['show']);
Route::get('genres/{genre}', [GenreController::class, 'show'])->name('genres.show');
Route::resource('artists', ArtistController::class)->except(['show']);
Route::get('artists/{artist}', [ArtistController::class, 'show'])->name('artists.show');
Route::resource('albums', AlbumController::class)->except(['show']);
Route::get('albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
Route::resource('songs', SongController::class)->except(['show']);
Route::get('songs/{song}', [SongController::class, 'show'])->name('songs.show');

// JSON API (for frontend consumption)
Route::get('/api/catalog', [CatalogController::class, 'home']);
Route::get('/api/genres/{slug}', [CatalogController::class, 'genre']);
Route::get('/api/artists/{slug}', [CatalogController::class, 'artist']);
Route::get('/api/search', [CatalogController::class, 'search']);

// Audio streaming
Route::get('/stream/{song}', [CatalogController::class, 'stream'])->name('stream.api');

// Audio download
Route::get('/download/{song}', function (App\Models\Song $song, App\Services\GoogleDriveService $drive) {
    $filename = $song->album->artist->name . ' - ' . $song->title . '.' . ($song->mime_type === 'audio/flac' ? 'flac' : 'mp3');
    $filename = preg_replace('/[^\w\s\-\.]/', '', $filename);
    return $drive->downloadFile($song->drive_file_id, $filename, $song->mime_type ?: 'audio/mpeg');
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

Route::get('/debug', function () {
    return response()->json([
        'client_id' => config('services.google.client_id') ? 'SET' : 'EMPTY',
        'redirect_uri' => config('services.google.redirect_uri') ?? 'NULL',
        'url' => config('services.google.client_id')
            ? 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id'     => config('services.google.client_id'),
                'redirect_uri'  => config('services.google.redirect_uri'),
                'response_type' => 'code',
                'scope'         => 'https://www.googleapis.com/auth/drive.readonly',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
            ])
            : 'NO CLIENT ID',
    ]);
});

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

// Protected import route (one-time use)
Route::get('/admin/import', function () {
    $secret = env('IMPORT_SECRET', 'musicarchive2024');
    if (request('key') !== $secret) {
        abort(403);
    }

    if (!app(\App\Services\GoogleDriveService::class)->isAuthorized()) {
        return response()->json(['error' => 'Google Drive not authorized. Visit /auth/google first.'], 401);
    }

    $exitCode = Artisan::call('drive:import', ['rootFolderId' => '1Lp12tPEogQYr3fuhcAKUZ5Mw_lISP1Fi']);
    return response()->json([
        'status' => 'Import complete',
        'output' => Artisan::output(),
    ]);
});
