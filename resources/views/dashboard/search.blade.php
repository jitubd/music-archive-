@extends('layouts.dashboard')

@section('title', 'Search: ' . $q)
@section('header')
    <h1 class="text-lg font-semibold">Search: "{{ $q }}"</h1>
@endsection

@section('content')
<div class="space-y-6 sm:space-y-8">
    @if($genres->count())
        <section>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Genres ({{ $genres->count() }})</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                @foreach($genres as $genre)
                    <a href="{{ route('genres.show', $genre) }}" class="block bg-gray-900 border border-gray-800 rounded-lg p-3 sm:p-4 hover:border-gray-600 transition active:border-indigo-600">
                        <div class="font-medium">{{ $genre->name }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $genre->artists_count }} {{ Str::plural('artist', $genre->artists_count) }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($artists->count())
        <section>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Artists ({{ $artists->count() }})</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                @foreach($artists as $artist)
                    <a href="{{ route('artists.show', $artist) }}" class="block bg-gray-900 border border-gray-800 rounded-lg p-3 sm:p-4 hover:border-gray-600 transition active:border-indigo-600">
                        <div class="font-medium">{{ $artist->name }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $artist->genre->name ?? 'Uncategorized' }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($albums->count())
        <section>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Albums ({{ $albums->count() }})</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                @foreach($albums as $album)
                    <a href="{{ route('albums.show', $album) }}" class="block bg-gray-900 border border-gray-800 rounded-lg p-3 sm:p-4 hover:border-gray-600 transition active:border-indigo-600">
                        <div class="font-medium">{{ $album->title }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $album->artist->name }}@if($album->year) · {{ $album->year }}@endif</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($songs->count())
        <section>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Songs ({{ $songs->count() }})</h2>
            <div class="bg-gray-900 border border-gray-800 rounded-xl divide-y divide-gray-800">
                @foreach($songs as $song)
                    <div class="px-3 sm:px-5 py-3 flex items-center gap-3 sm:gap-4 hover:bg-gray-800/50 transition group">
                        <button
                            @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($song->album->artist->name) }}', {{ $song->id }}, {{ $song->album_id }})"
                            class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition flex-shrink-0">
                            <svg class="w-3.5 h-3.5 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('songs.show', $song) }}" class="text-sm font-medium hover:text-indigo-400 transition truncate block">{{ $song->title }}</a>
                            <div class="text-xs text-gray-400 truncate">
                                {{ $song->album->artist->name }} — {{ $song->album->title }}
                                @if($song->album->year)({{ $song->album->year }})@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(!$genres->count() && !$artists->count() && !$albums->count() && !$songs->count())
        <div class="text-center text-gray-500 py-12">No results found for "{{ $q }}".</div>
    @endif
</div>
@endsection
