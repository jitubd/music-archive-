@extends('layouts.dashboard')

@section('title', 'Tags')
@section('header')
    <h1 class="text-lg font-semibold">Tags</h1>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-400">{{ $tags->count() }} {{ Str::plural('tag', $tags->count()) }}</p>
    </div>

    <form action="{{ route('admin.tags.store') }}?key={{ request('key') }}" method="POST" class="mb-6 bg-gray-900 border border-gray-800 rounded-xl p-4">
        @csrf
        <div class="flex items-center gap-3">
            <input type="text" name="name" placeholder="New tag name (e.g. chill, workout, romantic)"
                   class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0">
                + Add Tag
            </button>
        </div>
        @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
    </form>

    @if(session('success'))
        <div class="mb-4 bg-green-900/50 border border-green-700 text-green-200 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        @forelse($tags as $tag)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-800 last:border-0">
                <div class="flex items-center gap-3">
                    <span class="bg-gray-800 px-3 py-1 rounded-full text-sm">#{{ $tag->name }}</span>
                    <span class="text-xs text-gray-500">{{ $tag->songs_count }} {{ Str::plural('song', $tag->songs_count) }}</span>
                </div>
                <form action="{{ route('admin.tags.destroy', $tag) }}?key={{ request('key') }}" method="POST" onsubmit="return confirm('Delete this tag?')">
                    @csrf @method('DELETE')
                    <button class="text-gray-400 hover:text-red-400 transition text-sm">Delete</button>
                </form>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-gray-500 text-sm">No tags yet. Create your first tag above.</div>
        @endforelse
    </div>
</div>
@endsection