<?php

namespace App\Services;

use App\Enums\RedirectType;
use App\Models\ShortDomain;
use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectService
{
    public function buildRedirectUrl(
        ShortLink          $link,
        ?ShortLinkPassword $password = null,
        ?Request           $request = null
    ): string
    {
        if ($password) {
            return $this->buildPasswordUrl($link, $password);
        }

        return $this->buildLinkUrl($link, $request);
    }

    public function makeRedirectResponse(string $url, RedirectType $type): RedirectResponse
    {
        return new RedirectResponse($url, $type->statusCode());
    }

    public function buildDomainRootUrl(ShortDomain $domain, ?Request $request = null): string
    {
        $url = $domain->target_url;

        if ($domain->extra_path) {
            $url = $this->appendPath($url, $domain->extra_path);
        }

        if ($domain->forward_query && $request && $request->query()) {
            $url = $this->mergeRequestQuery($url, $request);
        } elseif ($domain->extra_query) {
            $url = $this->mergeQueryParams($url, $domain->extra_query);
        }

        return $url;
    }

    private function buildPasswordUrl(ShortLink $link, ShortLinkPassword $password): string
    {
        $url = $password->target_url ?: $link->target_url;

        if ($password->extra_path) {
            $url = $this->appendPath($url, $password->extra_path);
        }

        if ($password->extra_query) {
            $url = $this->mergeQueryParams($url, $password->extra_query);
        }

        return $url;
    }

    private function buildLinkUrl(ShortLink $link, ?Request $request): string
    {
        $url = $link->target_url;

        if ($link->extra_path) {
            $url = $this->appendPath($url, $link->extra_path);
        }

        if ($link->forward_query && $request && $request->query()) {
            $url = $this->mergeRequestQuery($url, $request);
        } elseif ($link->extra_query) {
            $url = $this->mergeQueryParams($url, $link->extra_query);
        }

        return $url;
    }

    private function appendPath(string $url, string $extraPath): string
    {
        $cleaned = ltrim(trim($extraPath), '/');

        if ($cleaned === '') {
            return $url;
        }

        $parts = parse_url($url);
        $basePath = rtrim($parts['path'] ?? '', '/');
        $newPath = $basePath ? "{$basePath}/{$cleaned}" : "/{$cleaned}";

        return $this->rebuildUrl($parts, $newPath);
    }

    private function mergeQueryParams(string $url, string $extraQuery): string
    {
        $cleaned = ltrim(trim($extraQuery), '?');

        if ($cleaned === '') {
            return $url;
        }

        $parts = parse_url($url);

        $existing = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $extras = [];
        parse_str($cleaned, $extras);

        $merged = array_merge($existing, $extras);

        return $this->rebuildUrl($parts, null, http_build_query($merged));
    }

    private function mergeRequestQuery(string $url, Request $request): string
    {
        $parts = parse_url($url);

        $existing = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $merged = array_merge($existing, $request->query());

        return $this->rebuildUrl($parts, null, http_build_query($merged));
    }

    private function rebuildUrl(array $parts, ?string $newPath = null, ?string $newQuery = null): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $newPath ?? ($parts['path'] ?? '');
        $query = $newQuery ?? ($parts['query'] ?? '');
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        $result = $scheme . $host . $port . $path;

        if ($query !== '') {
            $result .= '?' . $query;
        }

        return $result . $fragment;
    }
}
