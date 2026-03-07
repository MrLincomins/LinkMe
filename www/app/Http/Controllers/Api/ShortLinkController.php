<?php

namespace App\Http\Controllers\Api;
use App\Http\Resources\Link\ShortLinkResource;
use App\Models\ShortLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Http\Requests\Link\StoreShortLinkRequest;
use App\Http\Requests\Link\UpdateShortLinkRequest;

class ShortLinkController extends Controller
{
    public function index(Request $request)
    {
        $query = ShortLink::with('domain');

        if ($request->has('domain_id')) {
            $query->byDomain((int)$request->input('domain_id'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        $links = $query->latest()->paginate(
            $request->input('per_page', 15)
        );

        return ShortLinkResource::collection($links);
    }

    public function store(StoreShortLinkRequest $request): JsonResponse
    {
        $link = ShortLink::create($request->validated());

        return (new ShortLinkResource($link->load('domain')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShortLink $link): ShortLinkResource
    {
        return new ShortLinkResource(
            $link->load(['domain', 'passwords'])
        );
    }

    public function update(UpdateShortLinkRequest $request, ShortLink $link): ShortLinkResource
    {
        $link->update($request->validated());

        return new ShortLinkResource($link->load('domain'));
    }

    public function destroy(ShortLink $link): JsonResponse
    {
        $link->delete();

        return response()->json([
            'message' => 'Ссылка успешно удалена.',
        ]);
    }
}
