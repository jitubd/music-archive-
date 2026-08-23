@extends('layouts.dashboard')

@section('title', 'New Album')
@section('header')
    <h1 class="text-lg font-semibold">New Album</h1>
@endsection

@section('content')
<div class="max-w-lg">
    <form action="{{ route('albums.store') }}" method="POST" class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title') }}" required autofocus
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
            @error('title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Artist</label>
            <select name="artist_id" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('artist_id') border-red-500 @enderror">
                <option value="">Select artist...</option>
                @foreach($artists as $artist)
                    <option value="{{ $artist->id }}" {{ old('artist_id', request('artist_id')) == $artist->id ? 'selected' : '' }}>{{ $artist->name }}</option>
                @endforeach
            </select>
            @error('artist_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Year</label>
            <input type="number" name="year" value="{{ old('year') }}" min="1900" max="2100" placeholder="e.g. 1971"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('year') border-red-500 @enderror">
            @error('year')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">Create Album</button>
            <a href="{{ route('albums.index') }}" class="text-sm text-gray-400 hover:text-white transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
