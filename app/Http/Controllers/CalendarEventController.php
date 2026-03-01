<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    /**
     * GET /api/calendar/events?from=2026-03-01&to=2026-03-31
     *
     * Returns all events whose start falls within the given range.
     * Defaults to the current month if no params are supplied.
     * Also returns recurring event occurrences expanded within the range.
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->toDateTimeString());
        $to   = $request->get('to',   now()->endOfMonth()->toDateTimeString());

        $events = CalendarEvent::with(['users', 'creator:id,name,avatar'])
            ->inRange($from, $to)
            ->orderBy('start_datetime')
            ->get();

        // Expand recurring events into occurrences within the range
        $expanded = [];
        foreach ($events as $event) {
            $expanded[] = $event;

            if ($event->recurrence) {
                $occurrences = $this->expandRecurrences($event, $from, $to);
                foreach ($occurrences as $occurrence) {
                    $expanded[] = $occurrence;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $expanded,
        ]);
    }

    /**
     * POST /api/calendar/events
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'nullable|string|max:255',
            'start_datetime'      => 'required|date',
            'end_datetime'        => 'required|date|after_or_equal:start_datetime',
            'is_all_day'          => 'boolean',
            'color'               => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'recurrence'          => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_ends_on'  => 'nullable|date|after:start_datetime',
            'user_ids'            => 'nullable|array',
            'user_ids.*'          => 'integer|exists:users,id',
        ]);

        $event = CalendarEvent::create([
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'location'            => $validated['location'] ?? null,
            'start_datetime'      => $validated['start_datetime'],
            'end_datetime'        => $validated['end_datetime'],
            'is_all_day'          => $validated['is_all_day'] ?? false,
            'color'               => $validated['color'] ?? '#4F46E5',
            'recurrence'          => $validated['recurrence'] ?? null,
            'recurrence_ends_on'  => $validated['recurrence_ends_on'] ?? null,
            'created_by'          => $request->user()->id,
        ]);

        $event->users()->sync($validated['user_ids'] ?? []);
        $event->load(['users', 'creator:id,name,avatar']);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data'    => $event,
        ], 201);
    }

    /**
     * GET /api/calendar/events/{id}
     */
    public function show(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->load(['users', 'creator:id,name,avatar']);

        return response()->json([
            'success' => true,
            'data'    => $calendarEvent,
        ]);
    }

    /**
     * PUT /api/calendar/events/{id}
     */
    public function update(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'nullable|string|max:255',
            'start_datetime'      => 'required|date',
            'end_datetime'        => 'required|date|after_or_equal:start_datetime',
            'is_all_day'          => 'boolean',
            'color'               => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'recurrence'          => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_ends_on'  => 'nullable|date|after:start_datetime',
            'user_ids'            => 'nullable|array',
            'user_ids.*'          => 'integer|exists:users,id',
        ]);

        $calendarEvent->update([
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'location'            => $validated['location'] ?? null,
            'start_datetime'      => $validated['start_datetime'],
            'end_datetime'        => $validated['end_datetime'],
            'is_all_day'          => $validated['is_all_day'] ?? $calendarEvent->is_all_day,
            'color'               => $validated['color'] ?? $calendarEvent->color,
            'recurrence'          => $validated['recurrence'] ?? null,
            'recurrence_ends_on'  => $validated['recurrence_ends_on'] ?? null,
        ]);

        $calendarEvent->users()->sync($validated['user_ids'] ?? []);
        $calendarEvent->load(['users', 'creator:id,name,avatar']);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data'    => $calendarEvent,
        ]);
    }

    /**
     * DELETE /api/calendar/events/{id}
     */
    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }

    // ============================================
    // RECURRENCE EXPANSION
    // ============================================

    /**
     * Generate virtual occurrences of a recurring event within the given range.
     * Returns plain arrays (not DB records) so the frontend can render them.
     * The original event is always included by the main query.
     */
    private function expandRecurrences(CalendarEvent $event, string $from, string $to): array
    {
        $occurrences = [];
        $duration    = $event->start_datetime->diffInSeconds($event->end_datetime);

        $cursor  = $event->start_datetime->copy();
        $rangeTo = new \DateTime($to);
        $limit   = $event->recurrence_ends_on
            ? min($rangeTo, new \DateTime($event->recurrence_ends_on->toDateString()))
            : $rangeTo;

        // Advance to first occurrence after the original
        $cursor = $this->advanceCursor($cursor, $event->recurrence);

        while ($cursor <= $limit) {
            if ($cursor >= new \DateTime($from)) {
                $occEnd = $cursor->copy()->addSeconds($duration);
                $clone  = $event->toArray();

                $clone['id']             = null; // virtual — no DB id
                $clone['is_occurrence']  = true;
                $clone['parent_event_id']= $event->id;
                $clone['start_datetime'] = $cursor->toIso8601String();
                $clone['end_datetime']   = $occEnd->toIso8601String();

                $occurrences[] = $clone;
            }

            $cursor = $this->advanceCursor($cursor, $event->recurrence);
        }

        return $occurrences;
    }

    private function advanceCursor(\Carbon\Carbon $cursor, string $recurrence): \Carbon\Carbon
    {
        return match ($recurrence) {
            'daily'   => $cursor->copy()->addDay(),
            'weekly'  => $cursor->copy()->addWeek(),
            'monthly' => $cursor->copy()->addMonth(),
            'yearly'  => $cursor->copy()->addYear(),
            default   => $cursor->copy()->addDay(),
        };
    }
}
