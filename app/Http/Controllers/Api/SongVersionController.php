<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SongVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongVersionController extends Controller
{
    public function index(Song $song): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        $versions = $song->versions()
            ->select(['id', 'song_id', 'title', 'label', 'created_at',
                DB::raw('substr(content, 1, 200) as snippet')])
            ->get();

        return response()->json($versions);
    }

    public function store(Request $request, Song $song): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'label' => 'sometimes|nullable|string|max:100',
        ]);

        $version = $song->versions()->create([
            'content' => $song->content,
            'title' => $song->title,
            'label' => $validated['label'] ?? null,
        ]);

        return response()->json($version, 201);
    }

    public function restore(Song $song, SongVersion $version): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        $song->versions()->create([
            'content' => $song->content,
            'title' => $song->title,
            'label' => 'auto – visszaállítás előtt',
        ]);

        $song->update([
            'content' => $version->content,
            'title' => $version->title,
        ]);

        return response()->json($song->fresh());
    }
}
