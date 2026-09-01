@extends('layouts.dashboard')

@section('title', 'Songs')
@section('header')
    <h1 class="text-lg font-semibold">Songs</h1>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">{{ $songs->total() }} {{ Str::plural('song', $songs->total()) }}</p>
        @auth
        <a href="{{ route('songs.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + New Song
        </a>
        @endauth
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 w-10"></th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300">Title</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300">Artist</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden lg:table-cell">Album</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden lg:table-cell">Mood</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300 w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($songs as $song)
                    <tr class="hover:bg-gray-800/50 transition group">
                        <td class="px-5 py-3">
                            <button
                                    @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($song->album->artist->name) }}', {{ $song->id }}, {{ $song->album_id }})"
                                class="w-7 h-7 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition">
                                <svg class="w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('songs.show', $song) }}" class="font-medium hover:text-indigo-400 transition">
                                {{ $song->title }}
                                @if($song->track_number)
                                    <span class="text-gray-500 font-normal ml-1">#{{ $song->track_number }}</span>
                                @endif
                            </a>
                        </td>
                        <td class="px-5 py-3 text-gray-400">
                            <a href="{{ route('artists.show', $song->album->artist) }}" class="hover:text-indigo-400 transition">{{ $song->album->artist->name }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden lg:table-cell">
                            <a href="{{ route('albums.show', $song->album) }}" class="hover:text-indigo-400 transition">{{ $song->album->title }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden lg:table-cell">
                            @if($song->mood)
                                <span class="text-xs bg-gray-800 px-2 py-0.5 rounded-full text-gray-300">{{ $song->mood }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @auth
                                <a href="{{ route('songs.edit', $song) }}" class="text-gray-400 hover:text-white transition">Edit</a>
                                <form action="{{ route('songs.destroy', $song) }}" method="POST" onsubmit="return confirm('Delete this song?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-400 transition">Delete</button>
                                </form>
                                @endauth
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">No songs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-2">
        @forelse($songs as $song)
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-3 flex items-center gap-3">
                <button
                    @click="playSong('{{ route('stream.api', $song) }}', '{{ addslashes($song->title) }}', '{{ addslashes($song->album->artist->name) }}', {{ $song->id }}, {{ $song->album_id }})"
                    class="w-9 h-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 active:bg-indigo-600 active:text-white flex-shrink-0">
                    <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                </button>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('songs.show', $song) }}" class="text-sm font-medium hover:text-indigo-400 transition block truncate">{{ $song->title }}</a>
                    <div class="text-xs text-gray-400 truncate">
                        {{ $song->album->artist->name }}
                        @if($song->album->year) · {{ $song->album->year }}@endif
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($song->mood)
                        <span class="text-xs bg-gray-800 px-2 py-0.5 rounded-full text-gray-300 hidden">{{ $song->mood }}</span>
                    @endif
                    @auth
                    <a href="{{ route('songs.edit', $song) }}" class="text-xs text-gray-400 hover:text-white">Edit</a>
                    @endauth
                </div>
            </div>
        @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center text-gray-500 text-sm">No songs yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $songs->links() }}</div>
</div>
@endsection
