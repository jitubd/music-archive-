@extends('layouts.dashboard')

@section('title', 'Albums')
@section('header')
    <h1 class="text-lg font-semibold">Albums</h1>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">{{ $albums->total() }} {{ Str::plural('album', $albums->total()) }}</p>
        <a href="{{ route('albums.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + New Album
        </a>
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-300">Title</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden md:table-cell">Artist</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden lg:table-cell">Year</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300">Songs</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300 w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($albums as $album)
                    <tr class="hover:bg-gray-800/50 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('albums.show', $album) }}" class="font-medium hover:text-indigo-400 transition">{{ $album->title }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden md:table-cell">
                            <a href="{{ route('artists.show', $album->artist) }}" class="hover:text-indigo-400 transition">{{ $album->artist->name }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden lg:table-cell">{{ $album->year ?? '—' }}</td>
                        <td class="px-5 py-3 text-right text-gray-400">{{ $album->songs_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @auth
                                <a href="{{ route('albums.edit', $album) }}" class="text-gray-400 hover:text-white transition">Edit</a>
                                <form action="{{ route('albums.destroy', $album) }}" method="POST" onsubmit="return confirm('Delete this album?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-400 transition">Delete</button>
                                </form>
                                @endauth
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">No albums yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-2">
        @forelse($albums as $album)
            <a href="{{ route('albums.show', $album) }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-3 active:border-indigo-600">
                <div class="text-sm font-medium truncate">{{ $album->title }}</div>
                <div class="text-xs text-gray-400 mt-0.5">
                    {{ $album->artist->name }}
                    @if($album->year) · {{ $album->year }}@endif
                    · {{ $album->songs_count }} {{ Str::plural('track', $album->songs_count) }}
                </div>
            </a>
        @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center text-gray-500 text-sm">No albums yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $albums->links() }}</div>
</div>
@endsection
