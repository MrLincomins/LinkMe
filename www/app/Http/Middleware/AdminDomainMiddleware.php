<?php

namespace App\Http\Middleware;

use App\Services\DomainService;
use App\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDomainMiddleware
{
    public function __construct(
        private DomainService $domainService,
        private RedirectService $redirectService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('admin/*') && !$request->is('admin')) {
            return $next($request);
        }

        $host = $request->getHost();
        $adminDomain = config('sniplnk.admin_domain');

        if ($host === $adminDomain) {
            return $next($request);
        }

        $domain = $this->domainService->resolveByHost($host);

        if ($domain && $domain->target_url) {
            $url = $this->redirectService->buildDomainRootUrl($domain, $request);
            return $this->redirectService->makeRedirectResponse($url, $domain->redirect_type);
        }

        abort(404);
    }
}
