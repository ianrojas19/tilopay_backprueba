<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    /**
     * Handle an incoming request.
     * Accepts X-Correlation-Id header or generates a new UUID.
     * Adds correlation_id to log context and response headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-Id') ?: Str::uuid()->toString();

        // Store in request attributes for later use
        $request->attributes->set('correlation_id', $correlationId);

        // Add correlation_id to all log entries
        Log::withContext(['correlation_id' => $correlationId]);

        /** @var Response $response */
        $response = $next($request);

        // Add correlation_id to response headers
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
