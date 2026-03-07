<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\Domain\StoreShortDomainRequest;
use App\Http\Requests\Domain\UpdateShortDomainRequest;
use App\Http\Resources\Domain\ShortDomainResource;
use App\Models\ShortDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;


class ShortDomainController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $domains = ShortDomain::withCount('links')
            ->latest('created_at')
            ->paginate($request->input('per_page', 15));

        return ShortDomainResource::collection($domains);
    }

    public function store(StoreShortDomainRequest $request): JsonResponse
    {
        $domain = ShortDomain::create($request->validated());

        return (new ShortDomainResource($domain))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShortDomain $domain): ShortDomainResource
    {
        return new ShortDomainResource(
            $domain->loadCount('links')
        );
    }

    public function update(UpdateShortDomainRequest $request, ShortDomain $domain): ShortDomainResource
    {
        $domain->update($request->validated());

        return new ShortDomainResource($domain);
    }

    public function destroy(ShortDomain $domain): JsonResponse
    {
        if ($domain->links()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить домен с активными ссылками.',
            ], 422);
        }

        $domain->delete();

        return response()->json([
            'message' => 'Домен удалён',
        ]);
    }
}
