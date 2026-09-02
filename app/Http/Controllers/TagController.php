<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('songs')->orderBy('name')->get();

        return view('admin.tags', compact('tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        Tag::create(['name' => trim($validated['name'])]);

        Cache::forget('dashboard_stats');

        return back()->with('success', 'Tag created.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        Cache::forget('dashboard_stats');

        return back()->with('success', 'Tag deleted.');
    }
}