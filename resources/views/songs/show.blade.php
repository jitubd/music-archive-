@extends('layouts.dashboard')

@section('title', $song->title)
@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('songs.index') }}" class="text-gray-400 hover:text-white transition text-sm">Songs</a>
        <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <h1 class="text-lg font-semibold truncate">{{ $song->title }}</h1>
    </div>
@endsection

@section('content')
<div class="space-y-4 sm:space-y-6 max-w-2xl">
    {{-- Player card --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <button
                @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($song->album->artist->name) }}', {{ $song->id }}, {{ $song->album_id }})"
                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center text-white transition flex-shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
            </button>
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold">{{ $song->title }}</h2>
                <div class="text-sm text-gray-400 mt-0.5">
                    <a href="{{ route('artists.show', $song->album->artist) }}" class="hover:text-indigo-400 transition">{{ $song->album->artist->name }}</a>
                    <span class="mx-1">—</span>
                    <a href="{{ route('albums.show', $song->album) }}" class="hover:text-indigo-400 transition">{{ $song->album->title }}</a>
                    @if($song->album->year)
                        <span class="text-gray-500">({{ $song->album->year }})</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('song.download', $song) }}"
               class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white px-4 py-2 rounded-lg text-sm transition flex-shrink-0 w-full sm:w-auto justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </a>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl">
        <div class="px-4 sm:px-5 py-3 border-b border-gray-800 bg-gray-800/30 flex items-center justify-between">
            <h3 class="font-semibold">Details</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('songs.edit', $song) }}" class="text-sm text-gray-400 hover:text-white transition">Edit</a>
                <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Delete this song?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-gray-400 hover:text-red-400 transition">Delete</button>
                </form>
            </div>
        </div>
        <div class="divide-y divide-gray-800">
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">Track</span>
                <span>{{ $song->track_number ?? '—' }}</span>
            </div>
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">Mood</span>
                @if($song->mood)
                    <span class="bg-gray-800 px-2 py-0.5 rounded-full text-xs">{{ $song->mood }}</span>
                @else
                    <span>—</span>
                @endif
            </div>
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">Duration</span>
                <span>{{ $song->duration_seconds ? floor($song->duration_seconds / 60) . ':' . str_pad($song->duration_seconds % 60, 2, '0', STR_PAD_LEFT) : '—' }}</span>
            </div>
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">Size</span>
                <span>{{ $song->size_bytes ? round($song->size_bytes / 1048576, 1) . ' MB' : '—' }}</span>
            </div>
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">MIME</span>
                <span class="font-mono text-xs">{{ $song->mime_type ?? '—' }}</span>
            </div>
            <div class="px-4 sm:px-5 py-3 flex items-center justify-between text-sm">
                <span class="text-gray-400">Tags</span>
                <div class="flex flex-wrap gap-1 justify-end">
                    @forelse($song->tags as $tag)
                        <span class="bg-gray-800 px-2 py-0.5 rounded-full text-xs">#{{ $tag->name }}</span>
                    @empty
                        <span>—</span>
                    @endforelse
                </div>
            </div>
            @if($song->notes)
                <div class="px-4 sm:px-5 py-3 text-sm">
                    <span class="text-gray-400 block mb-1">Notes</span>
                    <p class="text-gray-300">{{ $song->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('albums.show', $song->album) }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to album
    </a>
</div>
@endsection
