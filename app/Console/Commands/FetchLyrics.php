<?php

namespace App\Console\Commands;

use App\Models\Song;
use App\Services\LyricsService;
use Illuminate\Console\Command;

class FetchLyrics extends Command
{
    protected $signature = 'lyrics:fetch {--limit=50} {--song-id=}';
    protected $description = 'Fetch synced lyrics from LRCLIB for songs missing lyrics';

    public function handle(LyricsService $lyrics): int
    {
        $query = Song::whereNull('lyrics')
            ->orWhere('lyrics', '')
            ->with('album.artist');

        if ($songId = $this->option('song-id')) {
            $query->where('id', $songId);
        }

        $limit = (int) $this->option('limit');
        $songs = $query->limit($limit)->get();

        if ($songs->isEmpty()) {
            $this->info('All songs already have lyrics.');
            return self::SUCCESS;
        }

        $found = 0;
        $bar = $this->output->createProgressBar($songs->count());
        $bar->start();

        foreach ($songs as $song) {
            $lrc = $lyrics->fetchForSong($song);

            if ($lrc) {
                $song->update(['lyrics' => $lrc]);
                $found++;
                $this->newLine();
                $this->line("  <green>✓</green> {$song->title} — {$song->album->artist->name}");
            }

            $bar->advance();
            usleep(250000); // 250ms delay between requests
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Found lyrics for {$found}/{$songs->count()} songs.");

        return self::SUCCESS;
    }
}
