<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use App\Services\DomainService;
use App\Services\RedirectService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RedirectController extends Controller
{
    public function __construct(
        private DomainService $domainService,
        private RedirectService $redirectService
    ) {}

    public function handle(Request $request, string $code)
    {
        $domain = $this->domainService->resolveByHost($request->getHost());

        if ($domain) {
            $this->domainService->markVerified($domain);
        }

        $link = $this->findLink($code, $domain?->id);

        if (!$link) {
            if ($domain && $domain->target_url) {
                return $this->redirectDomainRoot($domain, $request);
            }

            return response()->view('home', [], 404);
        }

        if ($link->passwords()->active()->exists()) {
            return $this->handlePasswordProtected($request, $link);
        }

        $this->incrementHitCount($link);
        $url = $this->redirectService->buildRedirectUrl($link, null, $request);

        return $this->redirectService->makeRedirectResponse($url, $link->redirect_type);
    }

    public function home(Request $request)
    {
        $host = $request->getHost();
        $adminDomain = config('linkme.admin_domain');

        $domain = $this->domainService->resolveByHost($host);

        if ($domain && $host !== $adminDomain) {
            $this->domainService->markVerified($domain);

            if ($domain->target_url) {
                return $this->redirectDomainRoot($domain, $request);
            }
        }

        return view('home');
    }

    private function findLink(string $code, ?int $domainId): ?ShortLink
    {
        if ($domainId === null) {
            return null;
        }

        return ShortLink::active()
            ->byDomain($domainId)
            ->byCode($code)
            ->first();
    }

    private function handlePasswordProtected(Request $request, ShortLink $link)
    {
        if ($request->isMethod('get')) {
            return view('password-prompt', ['code' => $link->code]);
        }

        $inputPassword = $request->input('password', '');

        $matched = $link->passwords()
            ->available()
            ->where('password', $inputPassword)
            ->first();

        if (!$matched) {
            return response()->view('password-prompt', [
                'code' => $link->code,
                'error' => 'Incorrect password',
            ], 401);
        }

        $this->incrementHitCount($link);
        $this->incrementPasswordHitCount($matched);

        $matched->refresh();
        if ($matched->max_uses !== null && $matched->hit_count >= $matched->max_uses) {
            $matched->update(['is_active' => false]);
        }

        $url = $this->redirectService->buildRedirectUrl($link, $matched, $request);

        return $this->redirectService->makeRedirectResponse($url, $link->redirect_type);
    }

    private function redirectDomainRoot($domain, Request $request): \Illuminate\Http\RedirectResponse
    {
        $url = $this->redirectService->buildDomainRootUrl($domain, $request);

        return $this->redirectService->makeRedirectResponse($url, $domain->redirect_type);
    }

    private function incrementHitCount(ShortLink $link): void
    {
        ShortLink::where('id', $link->id)->increment('hit_count');
    }

    private function incrementPasswordHitCount(ShortLinkPassword $password): void
    {
        ShortLinkPassword::where('id', $password->id)->increment('hit_count');
    }
}

