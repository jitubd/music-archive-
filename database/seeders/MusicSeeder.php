<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MusicSeeder extends Seeder
{
    public function run(): void
    {
        $rock = Genre::create(['name' => 'Rock', 'slug' => 'rock']);
        $country = Genre::create(['name' => 'Country', 'slug' => 'country']);
        $jazz = Genre::create(['name' => 'Jazz', 'slug' => 'jazz']);
        $electronic = Genre::create(['name' => 'Electronic', 'slug' => 'electronic']);
        $classical = Genre::create(['name' => 'Classical', 'slug' => 'classical']);

        $led = Artist::create(['name' => 'Led Zeppelin', 'slug' => 'led-zeppelin', 'genre_id' => $rock->id, 'bio' => 'English rock band formed in London in 1968.']);
        $nirvana = Artist::create(['name' => 'Nirvana', 'slug' => 'nirvana', 'genre_id' => $rock->id, 'bio' => 'American rock band formed in Aberdeen, Washington in 1987.']);
        $denver = Artist::create(['name' => 'John Denver', 'slug' => 'john-denver', 'genre_id' => $country->id, 'bio' => 'American singer-songwriter and record producer.']);
        $miles = Artist::create(['name' => 'Miles Davis', 'slug' => 'miles-davis', 'genre_id' => $jazz->id, 'bio' => 'American jazz trumpeter, bandleader, and composer.']);
        $daft = Artist::create(['name' => 'Daft Punk', 'slug' => 'daft-punk', 'genre_id' => $electronic->id, 'bio' => 'French electronic music duo formed in Paris in 1993.']);
        $beethoven = Artist::create(['name' => 'Ludwig van Beethoven', 'slug' => 'beethoven', 'genre_id' => $classical->id, 'bio' => 'German composer and pianist.']);

        $iv = Album::create(['title' => 'Led Zeppelin IV', 'slug' => 'led-zeppelin-iv', 'artist_id' => $led->id, 'year' => 1971]);
        $nevermind = Album::create(['title' => 'Nevermind', 'slug' => 'nevermind', 'artist_id' => $nirvana->id, 'year' => 1991]);
        $poems = Album::create(['title' => 'Poems, Prayers & Promises', 'slug' => 'poems-prayers-promises', 'artist_id' => $denver->id, 'year' => 1971]);
        $kind = Album::create(['title' => 'Kind of Blue', 'slug' => 'kind-of-blue', 'artist_id' => $miles->id, 'year' => 1959]);
        $homework = Album::create(['title' => 'Homework', 'slug' => 'homework', 'artist_id' => $daft->id, 'year' => 1997]);
        $ninth = Album::create(['title' => 'Symphony No. 9', 'slug' => 'symphony-no-9', 'artist_id' => $beethoven->id, 'year' => 1824]);

        $tagNames = ['Upbeat', 'Chill', 'Energetic', 'Melancholy', 'Instrumental', 'Classic'];
        $tagIds = [];
        foreach ($tagNames as $name) {
            $tagIds[] = Tag::create(['name' => $name])->id;
        }

        $songs = [
            ['album_id' => $iv->id, 'title' => 'Black Dog', 'track_number' => 1, 'drive_file_id' => 'demo1', 'mood' => 'energetic'],
            ['album_id' => $iv->id, 'title' => 'Rock and Roll', 'track_number' => 2, 'drive_file_id' => 'demo2', 'mood' => 'upbeat'],
            ['album_id' => $iv->id, 'title' => 'Stairway to Heaven', 'track_number' => 4, 'drive_file_id' => 'demo3', 'mood' => 'epic'],
            ['album_id' => $nevermind->id, 'title' => 'Smells Like Teen Spirit', 'track_number' => 1, 'drive_file_id' => 'demo4', 'mood' => 'energetic'],
            ['album_id' => $nevermind->id, 'title' => 'Come as You Are', 'track_number' => 2, 'drive_file_id' => 'demo5', 'mood' => 'chill'],
            ['album_id' => $nevermind->id, 'title' => 'Lithium', 'track_number' => 5, 'drive_file_id' => 'demo6', 'mood' => 'intense'],
            ['album_id' => $poems->id, 'title' => 'Take Me Home, Country Roads', 'track_number' => 1, 'drive_file_id' => 'demo7', 'mood' => 'upbeat'],
            ['album_id' => $poems->id, 'title' => 'Poems, Prayers and Promises', 'track_number' => 2, 'drive_file_id' => 'demo8', 'mood' => 'mellow'],
            ['album_id' => $kind->id, 'title' => 'So What', 'track_number' => 1, 'drive_file_id' => 'demo9', 'mood' => 'chill'],
            ['album_id' => $kind->id, 'title' => 'Freddie Freeloader', 'track_number' => 2, 'drive_file_id' => 'demo10', 'mood' => 'mellow'],
            ['album_id' => $homework->id, 'title' => 'Daftendirekt', 'track_number' => 1, 'drive_file_id' => 'demo11', 'mood' => 'energetic'],
            ['album_id' => $homework->id, 'title' => 'Around the World', 'track_number' => 4, 'drive_file_id' => 'demo12', 'mood' => 'upbeat'],
            ['album_id' => $ninth->id, 'title' => 'Ode to Joy', 'track_number' => 4, 'drive_file_id' => 'demo13', 'mood' => 'epic'],
        ];

        foreach ($songs as $data) {
            $song = Song::create($data);
            if (rand(0, 1)) {
                $count = min(rand(1, 3), count($tagIds));
                $picked = array_rand(array_flip($tagIds), $count);
                $song->tags()->attach(is_array($picked) ? $picked : [$picked]);
            }
        }
    }
}
