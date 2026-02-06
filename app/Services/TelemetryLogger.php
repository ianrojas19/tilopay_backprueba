<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryLogger
{
    /**
     * Log an action with telemetry context.
     */
    public static function log(Request $request, string $action, ?int $ticketId = null): void
    {
        $context = [
            'correlation_id' => $request->attributes->get('correlation_id'),
            'user_id' => $request->attributes->get('user_id'),
            'action' => $action,
        ];

        if ($ticketId !== null) {
            $context['ticket_id'] = $ticketId;
        }

        Log::info("Telemetry: {$action}", $context);
    }

    /**
     * Log an IDOR blocked access attempt.
     */
    public static function logIdorBlocked(Request $request, int $ticketId): void
    {
        Log::warning('Telemetry: security.idor_blocked', [
            'correlation_id' => $request->attributes->get('correlation_id'),
            'user_id' => $request->attributes->get('user_id'),
            'action' => 'security.idor_blocked',
            'ticket_id' => $ticketId,
        ]);
    }
}
