@extends('layouts.dashboard')

@section('title', 'Edit: ' . $song->title)
@section('header')
    <h1 class="text-lg font-semibold">Edit Song</h1>
@endsection

@section('content')
<div class="max-w-lg">
    <form action="{{ route('songs.update', $song) }}" method="POST" class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6 space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title', $song->title) }}" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
            @error('title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Album</label>
            <select name="album_id" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('album_id') border-red-500 @enderror">
                @foreach($albums as $album)
                    <option value="{{ $album->id }}" {{ old('album_id', $song->album_id) == $album->id ? 'selected' : '' }}>
                        {{ $album->artist->name }} — {{ $album->title }}@if($album->year) ({{ $album->year }})@endif
                    </option>
                @endforeach
            </select>
            @error('album_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Track #</label>
                <input type="number" name="track_number" value="{{ old('track_number', $song->track_number) }}" min="1" max="999"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('track_number') border-red-500 @enderror">
                @error('track_number')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Mood</label>
                <input type="text" name="mood" value="{{ old('mood', $song->mood) }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('mood') border-red-500 @enderror">
                @error('mood')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
            <textarea name="notes" rows="3"
                      class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror">{{ old('notes', $song->notes) }}</textarea>
            @error('notes')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Tags</label>
            <div class="flex flex-wrap gap-2">
                @foreach($tags as $tag)
                    <label class="flex items-center gap-1.5 bg-gray-800 px-3 py-1.5 rounded-lg text-sm cursor-pointer hover:bg-gray-700 transition">
                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $song->tags->pluck('id')->toArray())) ? 'checked' : '' }}
                               class="rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500">
                        {{ $tag->name }}
                    </label>
                @endforeach
            </div>
            @error('tags')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">Update Song</button>
            <a href="{{ route('songs.show', $song) }}" class="text-sm text-gray-400 hover:text-white transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
