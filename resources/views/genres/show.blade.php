@extends('layouts.dashboard')

@section('title', $genre->name)
@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('genres.index') }}" class="text-gray-400 hover:text-white transition text-sm">Genres</a>
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <h1 class="text-lg font-semibold">{{ $genre->name }}</h1>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-400">{{ $genre->artists->count() }} {{ Str::plural('artist', $genre->artists->count()) }}</p>
        <div class="flex items-center gap-3">
            @auth
            <a href="{{ route('genres.edit', $genre) }}" class="text-sm text-gray-400 hover:text-white transition">Edit</a>
            <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Delete this genre and all its data?')">
                @csrf @method('DELETE')
                <button class="text-sm text-gray-400 hover:text-red-400 transition">Delete</button>
            </form>
            @endauth
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($genre->artists as $artist)
            <a href="{{ route('artists.show', $artist) }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-600 transition group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-sm font-bold text-gray-300 group-hover:bg-indigo-600 group-hover:text-white transition">
                        {{ strtoupper(substr($artist->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-medium group-hover:text-indigo-400 transition">{{ $artist->name }}</div>
                        <div class="text-xs text-gray-400">{{ $artist->albums_count }} {{ Str::plural('album', $artist->albums_count) }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 text-center text-gray-500 py-8 bg-gray-900 border border-gray-800 rounded-xl">
                No artists in this genre yet.
            </div>
        @endforelse
    </div>
</div>
@endsection
