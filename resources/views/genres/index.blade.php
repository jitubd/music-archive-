@extends('layouts.dashboard')

@section('title', 'Genres')
@section('header')
    <h1 class="text-lg font-semibold">Genres</h1>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">{{ $genres->total() }} {{ Str::plural('genre', $genres->total()) }}</p>
        @auth
        <a href="{{ route('genres.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + New Genre
        </a>
        @endauth
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-300">Name</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-300 hidden md:table-cell">Slug</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300">Artists</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-300 w-32">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($genres as $genre)
                    <tr class="hover:bg-gray-800/50 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('genres.show', $genre) }}" class="font-medium hover:text-indigo-400 transition">{{ $genre->name }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-400 hidden md:table-cell">{{ $genre->slug }}</td>
                        <td class="px-5 py-3 text-right text-gray-400">{{ $genre->artists_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @auth
                                <a href="{{ route('genres.edit', $genre) }}" class="text-gray-400 hover:text-white transition">Edit</a>
                                <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Delete this genre?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-400 transition">Delete</button>
                                </form>
                                @endauth
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">No genres yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-2">
        @forelse($genres as $genre)
            <a href="{{ route('genres.show', $genre) }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-3 flex items-center justify-between active:border-indigo-600">
                <div>
                    <div class="text-sm font-medium">{{ $genre->name }}</div>
                    <div class="text-xs text-gray-400">{{ $genre->artists_count }} {{ Str::plural('artist', $genre->artists_count) }}</div>
                </div>
                <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @empty
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 text-center text-gray-500 text-sm">No genres yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $genres->links() }}</div>
</div>
@endsection
