<?php

namespace App\Http\Controllers\Api;

use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShortLinkPasswordController extends Controller
{
    public function index(ShortLink $link): JsonResponse
    {
        return response()->json([
            'data' => $link->passwords()->latest('id')->get(),
        ]);
    }

    public function store(Request $request, ShortLink $link): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'present|string|max:255',
            'target_url' => 'nullable|url|max:2048',
            'extra_query' => 'nullable|string|max:1024',
            'extra_path' => 'nullable|string|max:1024',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $pw = $link->passwords()->create($validated);

        return response()->json(['data' => $pw], 201);
    }

    public function update(Request $request, ShortLink $link, ShortLinkPassword $password): JsonResponse
    {
        if ($password->short_link_id !== $link->id) {
            return response()->json(['message' => 'Пароль не принадлежит этой ссылке.'], 404);
        }

        $validated = $request->validate([
            'password' => 'sometimes|present|string|max:255',
            'target_url' => 'nullable|url|max:2048',
            'extra_query' => 'nullable|string|max:1024',
            'extra_path' => 'nullable|string|max:1024',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $password->update($validated);

        return response()->json(['data' => $password->fresh()]);
    }

    public function destroy(ShortLink $link, ShortLinkPassword $password): JsonResponse
    {
        if ($password->short_link_id !== $link->id) {
            return response()->json(['message' => 'Пароль не принадлежит этой ссылке.'], 404);
        }

        $password->delete();

        return response()->json(['message' => 'Пароль удалён.']);
    }
}
