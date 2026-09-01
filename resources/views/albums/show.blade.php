@extends('layouts.dashboard')

@section('title', $album->title)
@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('artists.show', $album->artist) }}" class="text-gray-400 hover:text-white transition text-sm truncate max-w-[120px] sm:max-w-none">{{ $album->artist->name }}</a>
        <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <h1 class="text-lg font-semibold truncate">{{ $album->title }}</h1>
        @if($album->year)
            <span class="text-sm text-gray-400 hidden sm:inline">({{ $album->year }})</span>
        @endif
    </div>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-6">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">{{ $album->title }}</h2>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1 text-sm text-gray-400">
                    <a href="{{ route('artists.show', $album->artist) }}" class="hover:text-indigo-400 transition">{{ $album->artist->name }}</a>
                    @if($album->artist->genre)
                        <span>·</span>
                        <a href="{{ route('genres.show', $album->artist->genre) }}" class="hover:text-indigo-400 transition">{{ $album->artist->genre->name }}</a>
                    @endif
                    <span>·</span>
                    <span>{{ $album->songs->count() }} {{ Str::plural('track', $album->songs->count()) }}</span>
                    @if($album->year)
                        <span>·</span>
                        <span>{{ $album->year }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                @auth
                <a href="{{ route('songs.create', ['album_id' => $album->id]) }}" class="text-sm text-indigo-400 hover:text-indigo-300 transition">+ Add Song</a>
                <a href="{{ route('albums.edit', $album) }}" class="text-sm text-gray-400 hover:text-white transition">Edit</a>
                <form action="{{ route('albums.destroy', $album) }}" method="POST" onsubmit="return confirm('Delete this album and all its songs?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-gray-400 hover:text-red-400 transition">Delete</button>
                </form>
                @endauth
            </div>
        </div>
    </div>

    {{-- Tracklist --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-4 sm:px-5 py-3 border-b border-gray-800 bg-gray-800/30">
            <h3 class="font-semibold">Tracklist</h3>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($album->songs as $song)
                <div class="px-3 sm:px-5 py-3 flex items-center gap-3 sm:gap-4 hover:bg-gray-800/50 transition group">
                    <button
                        @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($album->artist->name) }}', {{ $song->id }}, {{ $album->id }})"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition flex-shrink-0">
                        <svg class="w-3.5 h-3.5 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                    </button>
                    <div class="w-6 sm:w-8 text-sm text-gray-500 text-right flex-shrink-0">
                        @if($song->track_number)
                            {{ $song->track_number }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('songs.show', $song) }}" class="text-sm font-medium hover:text-indigo-400 transition block truncate">{{ $song->title }}</a>
                        <div class="flex items-center gap-2 mt-0.5">
                            @if($song->mood)
                                <span class="text-xs bg-gray-800 px-2 py-0.5 rounded-full text-gray-300">{{ $song->mood }}</span>
                            @endif
                            @if($song->tags->count())
                                @foreach($song->tags as $tag)
                                    <span class="text-xs text-gray-500">#{{ $tag->name }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        @if($song->duration_seconds)
                            <span class="text-xs text-gray-500">{{ floor($song->duration_seconds / 60) }}:{{ str_pad($song->duration_seconds % 60, 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                        <div class="flex items-center gap-2">
                            @auth
                            <a href="{{ route('songs.edit', $song) }}" class="text-gray-400 hover:text-white text-xs">Edit</a>
                            <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Delete this song?')">
                                @csrf @method('DELETE')
                                <button class="text-gray-400 hover:text-red-400 text-xs">Del</button>
                            </form>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-gray-500 text-sm">
                    No songs in this album yet.
                    @auth
                    <a href="{{ route('songs.create', ['album_id' => $album->id]) }}" class="text-indigo-400 hover:text-indigo-300 ml-1">Add one</a>
                    @endauth
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
