<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function index(): JsonResponse
    {
        $songs = auth()->user()->songs()
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'bpm', 'updated_at', 'created_at']);

        return response()->json($songs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|nullable|string',
            'bpm' => 'sometimes|nullable|integer|min:20|max:300',
        ]);

        $song = auth()->user()->songs()->create([
            'title' => $validated['title'] ?? 'Cím nélküli dal',
            'content' => $validated['content'] ?? '',
            'bpm' => $validated['bpm'] ?? null,
        ]);

        return response()->json($song, 201);
    }

    public function show(Song $song): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        return response()->json($song);
    }

    public function update(Request $request, Song $song): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|nullable|string',
            'bpm' => 'sometimes|nullable|integer|min:20|max:300',
        ]);

        $song->update($validated);

        return response()->json($song);
    }

    public function destroy(Song $song): JsonResponse
    {
        abort_if($song->user_id !== auth()->id(), 403);

        $song->delete();

        return response()->json(null, 204);
    }
}
