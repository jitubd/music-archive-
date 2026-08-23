<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Flexible importer that handles flat Drive structures.
 *
 * Detects structure automatically:
 *   Folder/AlbumFolder/files.mp3          → Artist/Album/Songs
 *   Folder/files.mp3                      → Artist/Songs (auto-album)
 *   Folder/SubFolder/files.mp3            → Artist/Album/Songs
 *
 * Also auto-assigns genres based on folder name patterns.
 */
class ImportFromDrive extends Command
{
    protected $signature = 'drive:import {rootFolderId : Drive folder ID to scan}';
    protected $description = 'Scan a Google Drive folder tree and build/update the music catalog';

    protected GoogleDriveService $driveService;
    protected int $imported = 0;

    protected array $genreMap = [
        'jazz'      => 'Jazz',
        'blues'     => 'Blues',
        'instrumental' => 'Instrumental',
        'classical' => 'Classical',
        'soundtrack'=> 'Soundtrack',
        'ost'       => 'Soundtrack',
    ];

    public function __construct(GoogleDriveService $driveService)
    {
        parent::__construct();
        $this->driveService = $driveService;
    }

    public function handle(): int
    {
        if (!$this->driveService->isAuthorized()) {
            $this->error('Drive is not authorized yet. Run: php artisan drive:authorize');
            return self::FAILURE;
        }

        $rootFolderId = $this->argument('rootFolderId');

        $this->info('Scanning root folder...');
        $rootChildren = $this->driveService->listChildren($rootFolderId);
        $rootFolders = array_filter($rootChildren, fn($f) => $this->driveService->isFolder($f));
        $rootFiles = array_filter($rootChildren, fn($f) => $this->driveService->isAudio($f));

        $stats = ['genres' => 0, 'artists' => 0, 'albums' => 0, 'songs' => 0];

        // If root has audio files directly, create a "Misc" album
        if ($rootFiles) {
            $artist = Artist::updateOrCreate(['slug' => 'misc'], ['name' => 'Misc']);
            $album = Album::updateOrCreate(['artist_id' => $artist->id, 'slug' => 'misc'], ['title' => 'Misc']);
            foreach ($rootFiles as $file) {
                $this->importSong($file, $album->id);
                $stats['songs']++;
            }
            $stats['artists']++;
            $stats['albums']++;
        }

        foreach ($rootFolders as $folder) {
            $this->line("Processing: <info>{$folder['name']}</info>");
            $this->processFolder($folder, null, $stats);
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. %d artists, %d albums, %d songs imported/updated.',
            $stats['artists'], $stats['albums'], $stats['songs']
        ));

        return self::SUCCESS;
    }

    protected function processFolder(array $folder, ?int $parentId, array &$stats): void
    {
        $children = $this->driveService->listChildren($folder['id']);
        $subFolders = array_filter($children, fn($f) => $this->driveService->isFolder($f));
        $audioFiles = array_filter($children, fn($f) => $this->driveService->isAudio($f));

        // Determine if this folder is an Artist or Album level
        // If it has subfolders with audio, this is an Artist and subs are Albums
        // If it has audio directly, this is an Album

        if (!empty($subFolders)) {
            // This is an Artist level — subfolders are Albums
            $genreName = $this->detectGenre($folder['name']);
            $genreId = null;
            if ($genreName) {
                $genre = \App\Models\Genre::updateOrCreate(
                    ['slug' => Str::slug($genreName)],
                    ['name' => $genreName]
                );
                $genreId = $genre->id;
                $stats['genres']++;
            }

            $artist = Artist::updateOrCreate(
                ['slug' => Str::slug($folder['name'])],
                [
                    'name' => $folder['name'],
                    'genre_id' => $genreId,
                    'drive_folder_id' => $folder['id'],
                ]
            );
            $stats['artists']++;

            foreach ($subFolders as $subFolder) {
                $this->line("  Album: <comment>{$subFolder['name']}</comment>");
                $this->processAlbumFolder($subFolder, $artist->id, $stats);
            }

            // Also check for loose audio files at artist level (compilations)
            if ($audioFiles) {
                $album = Album::updateOrCreate(
                    ['artist_id' => $artist->id, 'slug' => Str::slug($folder['name']) . '-misc'],
                    ['title' => $folder['name'] . ' - Other', 'drive_folder_id' => $folder['id']]
                );
                foreach ($audioFiles as $file) {
                    $this->importSong($file, $album->id);
                    $stats['songs']++;
                }
                $stats['albums']++;
            }
        } elseif (!empty($audioFiles)) {
            // This folder IS an album (audio files directly inside)
            $genreName = $this->detectGenre($folder['name']);
            $genreId = null;
            if ($genreName) {
                $genre = \App\Models\Genre::updateOrCreate(
                    ['slug' => Str::slug($genreName)],
                    ['name' => $genreName]
                );
                $genreId = $genre->id;
                $stats['genres']++;
            }

            $artist = Artist::updateOrCreate(
                ['slug' => Str::slug($folder['name'])],
                [
                    'name' => $folder['name'],
                    'genre_id' => $genreId,
                    'drive_folder_id' => $folder['id'],
                ]
            );
            $stats['artists']++;

            $album = Album::updateOrCreate(
                ['artist_id' => $artist->id, 'slug' => Str::slug($folder['name'])],
                ['title' => $folder['name'], 'drive_folder_id' => $folder['id']]
            );
            $stats['albums']++;

            foreach ($audioFiles as $file) {
                $this->importSong($file, $album->id);
                $stats['songs']++;
            }
        } else {
            // Empty folder, skip
            $this->line("  <comment>(empty)</comment>");
        }
    }

    protected function processAlbumFolder(array $folder, int $artistId, array &$stats): void
    {
        [$title, $year] = $this->parseAlbumFolderName($folder['name']);

        $album = Album::updateOrCreate(
            ['artist_id' => $artistId, 'slug' => Str::slug($title)],
            [
                'title' => $title,
                'year' => $year,
                'drive_folder_id' => $folder['id'],
            ]
        );
        $stats['albums']++;

        $children = $this->driveService->listChildren($folder['id']);
        $audioFiles = array_filter($children, fn($f) => $this->driveService->isAudio($f));

        foreach ($audioFiles as $file) {
            $this->importSong($file, $album->id);
            $stats['songs']++;
        }

        // Check for nested subfolders (e.g. disc 1, disc 2)
        $subFolders = array_filter($children, fn($f) => $this->driveService->isFolder($f));
        foreach ($subFolders as $sub) {
            $this->line("    Sub: <comment>{$sub['name']}</comment>");
            $subChildren = $this->driveService->listChildren($sub['id']);
            $subAudio = array_filter($subChildren, fn($f) => $this->driveService->isAudio($f));
            foreach ($subAudio as $file) {
                $this->importSong($file, $album->id);
                $stats['songs']++;
            }
        }
    }

    protected function importSong(array $file, int $albumId): void
    {
        [$trackNumber, $title] = $this->parseTrackFilename($file['name']);

        Song::updateOrCreate(
            ['drive_file_id' => $file['id']],
            [
                'album_id' => $albumId,
                'title' => $title,
                'track_number' => $trackNumber,
                'mime_type' => $file['mimeType'],
                'size_bytes' => $file['size'] ?? null,
            ]
        );
        $this->imported++;
        $this->line("      {$this->imported}. {$title}");
    }

    protected function detectGenre(string $name): ?string
    {
        $lower = strtolower($name);

        // Check known patterns
        foreach ($this->genreMap as $pattern => $genre) {
            if (str_contains($lower, $pattern)) {
                return $genre;
            }
        }

        // Check for "ost" or "soundtrack" patterns
        if (preg_match('/\b(ost|soundtrack|sound\s*track)\b/i', $name)) {
            return 'Soundtrack';
        }

        // Check for "instrumental" or "piano" or "classical"
        if (preg_match('/\b(instrumental|piano|violin|orchestra|symphony|concerto)\b/i', $name)) {
            return 'Instrumental';
        }

        return null;
    }

    protected function parseAlbumFolderName(string $name): array
    {
        if (preg_match('/^(.*)\((\d{4})\)\s*$/', $name, $m)) {
            return [trim($m[1]), (int) $m[2]];
        }
        return [trim($name), null];
    }

    protected function parseTrackFilename(string $filename): array
    {
        $name = preg_replace('/\.[^.]+$/', '', $filename);

        if (preg_match('/^(\d{1,3})\s*[-.]\s*(.+)$/', $name, $m)) {
            return [(int) $m[1], trim($m[2])];
        }
        return [null, trim($name)];
    }
}
