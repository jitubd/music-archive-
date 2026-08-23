@extends('layouts.dashboard')

@section('title', 'New Genre')
@section('header')
    <h1 class="text-lg font-semibold">New Genre</h1>
@endsection

@section('content')
<div class="max-w-lg">
    <form action="{{ route('genres.store') }}" method="POST" class="bg-gray-900 border border-gray-800 rounded-xl p-4 sm:p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">Create Genre</button>
            <a href="{{ route('genres.index') }}" class="text-sm text-gray-400 hover:text-white transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
