<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\EventAttendee;
use App\Models\EventReminder;
use App\Models\EventRecurrence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarService
{
    public function getEvents(User $user, ?string $start = null, ?string $end = null, ?string $category = null, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        $query = CalendarEvent::where('company_id', $companyId)
            ->with(['creator:id,name,avatar', 'attendees.user:id,name,avatar,email', 'reminders'])
            ->orderBy('start_time', 'asc');

        if ($start) {
            $query->where('end_time', '>=', Carbon::parse($start));
        }
        if ($end) {
            $query->where('start_time', '<=', Carbon::parse($end));
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(function ($ev) {
            return [
                'id' => $ev->id,
                'title' => $ev->title,
                'description' => $ev->description,
                'start' => $ev->start_time->toIso8601String(),
                'end' => $ev->end_time->toIso8601String(),
                'startDate' => $ev->start_time->format('Y-m-d'),
                'startTime' => $ev->start_time->format('H:i'),
                'endDate' => $ev->end_time->format('Y-m-d'),
                'endTime' => $ev->end_time->format('H:i'),
                'timezone' => $ev->timezone,
                'location' => $ev->location,
                'meetingLink' => $ev->meeting_link,
                'color' => $ev->color ?: $this->getDefaultColorForCategory($ev->category),
                'category' => $ev->category,
                'status' => $ev->status,
                'isAllDay' => (bool) $ev->is_all_day,
                'isRecurring' => (bool) $ev->is_recurring,
                'recurrenceRule' => $ev->recurrence_rule,
                'creator' => [
                    'id' => $ev->creator?->id,
                    'name' => $ev->creator?->name ?? 'Admin',
                    'avatar' => $ev->creator?->avatar,
                ],
                'attendees' => $ev->attendees->map(fn($att) => [
                    'id' => $att->id,
                    'userId' => $att->user_id,
                    'name' => $att->user?->name ?? $att->name ?? 'Attendee',
                    'email' => $att->user?->email ?? $att->email,
                    'avatar' => $att->user?->avatar,
                    'status' => $att->status,
                ]),
                'reminders' => $ev->reminders->map(fn($r) => [
                    'id' => $r->id,
                    'minutesBefore' => $r->minutes_before,
                    'type' => $r->type,
                ]),
            ];
        });
    }

    private function getDefaultColorForCategory(string $category): string
    {
        return match ($category) {
            'meeting' => '#0F8B7A', // Teal
            'task' => '#2563EB',    // Blue
            'workflow' => '#7C3AED',// Purple
            'schedule' => '#F59E0B',// Amber
            'personal' => '#EC4899',// Pink
            default => '#0F8B7A',
        };
    }

    public function createEvent(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        return DB::transaction(function () use ($companyId, $user, $data) {
            $event = CalendarEvent::create([
                'company_id' => $companyId,
                'calendar_id' => $data['calendar_id'] ?? null,
                'creator_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_time' => Carbon::parse($data['start_time']),
                'end_time' => Carbon::parse($data['end_time']),
                'timezone' => $data['timezone'] ?? 'UTC',
                'location' => $data['location'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'color' => $data['color'] ?? $this->getDefaultColorForCategory($data['category'] ?? 'meeting'),
                'category' => $data['category'] ?? 'meeting',
                'status' => $data['status'] ?? 'scheduled',
                'is_all_day' => $data['is_all_day'] ?? false,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurrence_rule' => $data['recurrence_rule'] ?? null,
            ]);

            // Attendees
            if (!empty($data['attendee_ids']) && is_array($data['attendee_ids'])) {
                foreach ($data['attendee_ids'] as $userId) {
                    EventAttendee::create([
                        'event_id' => $event->id,
                        'user_id' => $userId,
                        'status' => 'pending',
                    ]);
                }
            }

            // Reminders
            if (!empty($data['reminders']) && is_array($data['reminders'])) {
                foreach ($data['reminders'] as $rem) {
                    EventReminder::create([
                        'event_id' => $event->id,
                        'minutes_before' => is_numeric($rem) ? (int) $rem : ($rem['minutes_before'] ?? 15),
                        'type' => is_array($rem) ? ($rem['type'] ?? 'notification') : 'notification',
                    ]);
                }
            } else {
                EventReminder::create([
                    'event_id' => $event->id,
                    'minutes_before' => 15,
                    'type' => 'notification',
                ]);
            }

            return $event->load(['creator', 'attendees.user', 'reminders']);
        });
    }

    public function updateEvent(int $id, User $user, array $data)
    {
        $event = CalendarEvent::findOrFail($id);

        if (isset($data['start_time'])) {
            $data['start_time'] = Carbon::parse($data['start_time']);
        }
        if (isset($data['end_time'])) {
            $data['end_time'] = Carbon::parse($data['end_time']);
        }

        $event->update($data);

        if (isset($data['attendee_ids']) && is_array($data['attendee_ids'])) {
            EventAttendee::where('event_id', $event->id)->delete();
            foreach ($data['attendee_ids'] as $uId) {
                EventAttendee::create([
                    'event_id' => $event->id,
                    'user_id' => $uId,
                    'status' => 'pending',
                ]);
            }
        }

        return $event->load(['creator', 'attendees.user', 'reminders']);
    }

    public function deleteEvent(int $id, User $user)
    {
        $event = CalendarEvent::findOrFail($id);
        $event->delete();
        return ['success' => true];
    }
}
