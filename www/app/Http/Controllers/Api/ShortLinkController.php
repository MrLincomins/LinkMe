<?php

namespace App\Http\Controllers\Api;
use App\Http\Resources\Link\ShortLinkResource;
use App\Models\ShortLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function trashed(Request $request): AnonymousResourceCollection
    {
        $links = ShortLink::onlyTrashed()
            ->with('domain')
            ->latest('deleted_at')
            ->paginate($request->input('per_page', 15));

        return ShortLinkResource::collection($links);
    }

    public function restore(int $id): ShortLinkResource
    {
        $link = ShortLink::onlyTrashed()->findOrFail($id);
        $link->restore();

        return new ShortLinkResource($link->load('domain'));
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $link = ShortLink::onlyTrashed()->findOrFail($id);
        $link->forceDelete();

        return response()->json([
            'message' => 'Link permanently deleted.',
        ]);
    }

    public function stats(ShortLink $link): JsonResponse
    {
        $link->load('domain');

        $visits = $link->visits()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $topReferers = $link->visits()
            ->whereNotNull('referer')
            ->selectRaw('referer, COUNT(*) as count')
            ->groupBy('referer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'link' => new ShortLinkResource($link),
                'total_hits' => $link->hit_count,
                'visits_by_day' => $visits,
                'top_referers' => $topReferers,
            ],
        ]);
    }

}
