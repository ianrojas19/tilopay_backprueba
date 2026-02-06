<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TelemetryLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        TelemetryLogger::log($request, 'tickets.list');

        $tickets = Ticket::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        $ticket = Ticket::create([
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'status' => 'open',
            'user_id' => $userId,
        ]);

        TelemetryLogger::log($request, 'tickets.create', $ticket->id);

        return response()->json($ticket, 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        $ticket = Ticket::forUser($userId)->find($id);

        if (!$ticket) {
            // Check if ticket exists at all to determine if this is an IDOR attempt
            if (Ticket::find($id)) {
                TelemetryLogger::logIdorBlocked($request, $id);
            }
            return response()->json(['error' => 'Not Found'], 404);
        }

        TelemetryLogger::log($request, 'tickets.show', $ticket->id);

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        $ticket = Ticket::forUser($userId)->find($id);

        if (!$ticket) {
            // Check if ticket exists at all to determine if this is an IDOR attempt
            if (Ticket::find($id)) {
                TelemetryLogger::logIdorBlocked($request, $id);
            }
            return response()->json(['error' => 'Not Found'], 404);
        }

        $ticket->update($request->only(['subject', 'body', 'status']));

        TelemetryLogger::log($request, 'tickets.update', $ticket->id);

        return response()->json($ticket);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        $ticket = Ticket::forUser($userId)->find($id);

        if (!$ticket) {
            // Check if ticket exists at all to determine if this is an IDOR attempt
            if (Ticket::find($id)) {
                TelemetryLogger::logIdorBlocked($request, $id);
            }
            return response()->json(['error' => 'Not Found'], 404);
        }

        TelemetryLogger::log($request, 'tickets.delete', $ticket->id);

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }
}
