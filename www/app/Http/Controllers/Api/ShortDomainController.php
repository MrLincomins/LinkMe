<?php

namespace App\Http\Controllers\Api;


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

        return $domains;
    }

    public function store($request): JsonResponse
    {
        $domain = ShortDomain::create($request->validated());

        return $domain
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShortDomain $domain)
    {
        return $domain->loadCount('links');

    }

    public function update($request, ShortDomain $domain)
    {
        $domain->update($request->validated());

        return $domain;
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
