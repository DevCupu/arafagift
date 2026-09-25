<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompressResponse
{
    private const MIN_LENGTH = 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('HEAD')) {
            return $response;
        }

        if (! str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }

        if ($response instanceof StreamedResponse || $response->headers->has('Content-Encoding')) {
            return $response;
        }

        if (in_array($response->getStatusCode(), [204, 304], true)) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || strlen($content) < self::MIN_LENGTH) {
            return $response;
        }

        $compressed = gzencode($content, 6);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));

        $vary = $response->headers->get('Vary');
        $response->headers->set('Vary', $vary ? $vary.', Accept-Encoding' : 'Accept-Encoding');

        return $response;
    }
}
