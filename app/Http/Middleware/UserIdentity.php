<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIdentity
{
    /**
     * Maneja una solicitud entrante.
     * Valida el encabezado X-User-Id y almacena user_id en los atributos de la solicitud.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');

        if (empty($userId) || !is_numeric($userId)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'X-User-Id es requerido',
            ], 401);
        }

        $request->attributes->set('user_id', (int) $userId);

        return $next($request);
    }
}
