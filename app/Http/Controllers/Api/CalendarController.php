<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(protected CalendarService $calendarService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $events = $this->calendarService->getEvents(
            $request->user(),
            $request->query('start'),
            $request->query('end'),
            $request->query('category'),
            $request->query('search')
        );

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'attendee_ids' => 'nullable|array',
            'color' => 'nullable|string',
            'is_all_day' => 'nullable|boolean',
        ]);

        $event = $this->calendarService->createEvent($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $event,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = \App\Models\CalendarEvent::with(['creator', 'attendees.user', 'reminders'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $event,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->calendarService->updateEvent($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $event,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->calendarService->deleteEvent($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
