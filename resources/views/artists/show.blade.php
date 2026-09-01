@extends('layouts.dashboard')

@section('title', $artist->name)
@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('artists.index') }}" class="text-gray-400 hover:text-white transition text-sm">Artists</a>
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <h1 class="text-lg font-semibold truncate">{{ $artist->name }}</h1>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Artist header --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-6">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-800 flex items-center justify-center text-xl sm:text-2xl font-bold text-gray-300 flex-shrink-0">
                {{ strtoupper(substr($artist->name, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold">{{ $artist->name }}</h2>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1 text-sm text-gray-400">
                    @if($artist->genre)
                        <a href="{{ route('genres.show', $artist->genre) }}" class="hover:text-indigo-400 transition">{{ $artist->genre->name }}</a>
                        <span>·</span>
                    @endif
                    <span>{{ $artist->albums->count() }} {{ Str::plural('album', $artist->albums->count()) }}</span>
                    <span>·</span>
                    <span>{{ $artist->songs->count() }} {{ Str::plural('song', $artist->songs->count()) }}</span>
                </div>
                @if($artist->bio)
                    <p class="mt-3 text-sm text-gray-300 leading-relaxed">{{ $artist->bio }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 self-start">
                @auth
                <a href="{{ route('artists.edit', $artist) }}" class="text-sm text-gray-400 hover:text-white transition">Edit</a>
                <form action="{{ route('artists.destroy', $artist) }}" method="POST" onsubmit="return confirm('Delete this artist and all their albums/songs?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-gray-400 hover:text-red-400 transition">Delete</button>
                </form>
                @endauth
            </div>
        </div>
    </div>

    {{-- Albums --}}
    <div class="flex items-center justify-between">
        <h3 class="font-semibold">Albums</h3>
        @auth
        <a href="{{ route('albums.create', ['artist_id' => $artist->id]) }}" class="text-sm text-indigo-400 hover:text-indigo-300 transition">+ Add Album</a>
        @endauth
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        @forelse($artist->albums as $album)
            <a href="{{ route('albums.show', $album) }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-5 hover:border-gray-600 transition group active:border-indigo-600">
                <div class="font-medium group-hover:text-indigo-400 transition">{{ $album->title }}</div>
                <div class="flex items-center gap-2 mt-1 text-xs text-gray-400">
                    @if($album->year)
                        <span>{{ $album->year }}</span>
                        <span>·</span>
                    @endif
                    <span>{{ $album->songs_count }} {{ Str::plural('track', $album->songs_count) }}</span>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 text-center text-gray-500 py-8 bg-gray-900 border border-gray-800 rounded-xl">
                No albums yet.
            </div>
        @endforelse
    </div>
</div>
@endsection
