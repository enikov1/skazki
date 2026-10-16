<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\Story;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectOldContentSlugs
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route) return $next($request);

        foreach (['story' => Story::class, 'article' => Article::class] as $parameter => $modelClass) {
            $model = $route->parameter($parameter);
            if (! $model instanceof $modelClass) continue;

            $routeSegments = explode('/', $route->uri());
            $position = array_search('{'.$parameter.':slug}', $routeSegments, true);
            if ($position === false) $position = array_search('{'.$parameter.'}', $routeSegments, true);
            if ($position === false) continue;
            $pathSegments = explode('/', trim($request->path(), '/'));
            $requestedSlug = $pathSegments[$position] ?? null;
            if (! is_string($requestedSlug) || $requestedSlug === $model->slug) continue;
            if (! $model->slugRedirects()->where('old_slug', $requestedSlug)->exists()) continue;

            $pathSegments[$position] = $model->slug;
            $target = '/'.implode('/', $pathSegments);
            if ($request->getQueryString()) $target .= '?'.$request->getQueryString();
            return redirect($target, 301);
        }

        return $next($request);
    }
}
