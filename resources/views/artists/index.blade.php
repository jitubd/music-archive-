@extends('layouts.dashboard')

@section('title', 'Artists')
@section('header')
    <h1 class="text-lg font-semibold">Artists</h1>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">{{ $artists->total() }} {{ Str::plural('artist', $artists->total()) }}</p>
        @auth
        <a href="{{ route('artists.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + New Artist
        </a>
        @endauth
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-300">Artist</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden md:table-cell">Genre</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300">Albums</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300 w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($artists as $artist)
                    <tr class="hover:bg-gray-800/50 transition">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-xs font-bold text-gray-300 flex-shrink-0">
                                    {{ strtoupper(substr($artist->name, 0, 2)) }}
                                </div>
                                <a href="{{ route('artists.show', $artist) }}" class="font-medium hover:text-indigo-400 transition">{{ $artist->name }}</a>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden md:table-cell">
                            @if($artist->genre)
                                <a href="{{ route('genres.show', $artist->genre) }}" class="hover:text-indigo-400 transition">{{ $artist->genre->name }}</a>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right text-gray-400">{{ $artist->albums_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @auth
                                <a href="{{ route('artists.edit', $artist) }}" class="text-gray-400 hover:text-white transition">Edit</a>
                                <form action="{{ route('artists.destroy', $artist) }}" method="POST" onsubmit="return confirm('Delete this artist?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-400 transition">Delete</button>
                                </form>
                                @endauth
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">No artists yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-2">
        @forelse($artists as $artist)
            <a href="{{ route('artists.show', $artist) }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-3 flex items-center gap-3 active:border-indigo-600">
                <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-sm font-bold text-gray-300 flex-shrink-0">
                    {{ strtoupper(substr($artist->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ $artist->name }}</div>
                    <div class="text-xs text-gray-400">
                        {{ $artist->genre->name ?? 'Uncategorized' }}
                        · {{ $artist->albums_count }} {{ Str::plural('album', $artist->albums_count) }}
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center text-gray-500 text-sm">No artists yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $artists->links() }}</div>
</div>
@endsection
