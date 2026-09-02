@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('header')
    <h1 class="text-lg font-semibold">Dashboard</h1>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
            $cards = [
                ['label' => 'Genres', 'value' => $stats['genres'], 'route' => route('genres.index'), 'color' => 'bg-purple-600'],
                ['label' => 'Artists', 'value' => $stats['artists'], 'route' => route('artists.index'), 'color' => 'bg-blue-600'],
                ['label' => 'Albums', 'value' => $stats['albums'], 'route' => route('albums.index'), 'color' => 'bg-green-600'],
                ['label' => 'Songs', 'value' => $stats['songs'], 'route' => route('songs.index'), 'color' => 'bg-amber-600'],
                ['label' => 'Tags', 'value' => $stats['tags'], 'route' => route('admin.tags', ['key' => env('IMPORT_SECRET', 'musicarchive2024')]), 'color' => 'bg-pink-600'],
                ['label' => 'Total Size', 'value' => $stats['total_size'] ? round($stats['total_size'] / 1073741824, 1) . ' GB' : '0 GB', 'route' => '#', 'color' => 'bg-gray-600'],
            ];
        @endphp
        @foreach($cards as $card)
            <a href="{{ $card['route'] }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-4 hover:border-gray-600 transition">
                <div class="text-2xl font-bold">{{ $card['value'] }}</div>
                <div class="text-sm text-gray-400 mt-1">{{ $card['label'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Recent songs --}}
        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold">Recently Added Songs</h2>
                <a href="{{ route('songs.index') }}" class="text-sm text-indigo-400 hover:text-indigo-300">View all</a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($recentSongs as $song)
                    <div class="px-5 py-3 flex items-center gap-4 hover:bg-gray-800/50 transition group">
                        <button
                            @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($song->album->artist->name) }}', {{ $song->id }}, {{ $song->album_id }})"
                            class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition flex-shrink-0">
                            <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('songs.show', $song) }}" class="text-sm font-medium hover:text-indigo-400 transition truncate block">{{ $song->title }}</a>
                            <div class="text-xs text-gray-400 truncate">
                                {{ $song->album->artist->name }} — {{ $song->album->title }}
                                @if($song->album->year)({{ $song->album->year }})@endif
                            </div>
                        </div>
                        @if($song->mood)
                            <span class="text-xs bg-gray-800 px-2 py-0.5 rounded-full text-gray-300">{{ $song->mood }}</span>
                        @endif
                        @if($song->track_number)
                            <span class="text-xs text-gray-500 w-6 text-right">#{{ $song->track_number }}</span>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-gray-500 text-sm">No songs yet. Run <code>php artisan drive:import</code> to import from Google Drive.</div>
                @endforelse
            </div>
        </div>

        {{-- Top genres + recent artists --}}
        <div class="space-y-6">
            <div class="bg-gray-900 border border-gray-800 rounded-xl">
                <div class="px-5 py-4 border-b border-gray-800">
                    <h2 class="font-semibold">Genres</h2>
                </div>
                <div class="divide-y divide-gray-800">
                    @forelse($topGenres as $genre)
                        <a href="{{ route('genres.show', $genre) }}" class="px-5 py-3 flex items-center justify-between hover:bg-gray-800/50 transition">
                            <span class="text-sm">{{ $genre->name }}</span>
                            <span class="text-xs text-gray-400">{{ $genre->artists_count }} {{ Str::plural('artist', $genre->artists_count) }}</span>
                        </a>
                    @empty
                        <div class="px-5 py-4 text-center text-gray-500 text-sm">No genres yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-xl">
                <div class="px-5 py-4 border-b border-gray-800">
                    <h2 class="font-semibold">Recent Artists</h2>
                </div>
                <div class="divide-y divide-gray-800">
                    @forelse($recentArtists as $artist)
                        <a href="{{ route('artists.show', $artist) }}" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-800/50 transition">
                            <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-xs font-bold text-gray-300 flex-shrink-0">
                                {{ strtoupper(substr($artist->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm truncate">{{ $artist->name }}</div>
                                @if($artist->genre)
                                    <div class="text-xs text-gray-400">{{ $artist->genre->name }}</div>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-4 text-center text-gray-500 text-sm">No artists yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
