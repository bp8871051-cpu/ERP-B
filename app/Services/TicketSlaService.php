<?php

namespace App\Services;

use App\Models\SupportSlaLog;
use App\Models\SupportSlaPolicy;
use App\Models\Ticket;
use Carbon\Carbon;

class TicketSlaService
{
    /**
     * Calculate and apply SLA policy deadlines to a ticket
     */
    public function applySlaToTicket(Ticket $ticket, ?int $policyId = null): Ticket
    {
        $policy = null;
        if ($policyId) {
            $policy = SupportSlaPolicy::where('company_id', $ticket->company_id)->find($policyId);
        }

        // Fallback to priority matching policy
        if (!$policy) {
            $policy = SupportSlaPolicy::where('company_id', $ticket->company_id)
                ->where('status', 'active')
                ->where('priority', $ticket->priority)
                ->first();
        }

        // Fallback to default
        if (!$policy) {
            $policy = SupportSlaPolicy::where('company_id', $ticket->company_id)
                ->where('status', 'active')
                ->first();
        }

        if ($policy) {
            $now = Carbon::now();
            $ticket->sla_policy_id = $policy->id;
            $ticket->first_response_due_at = $this->calculateTargetTime($now, $policy->first_response_target_minutes, $policy->business_hours);
            $ticket->resolution_due_at = $this->calculateTargetTime($now, $policy->resolution_target_minutes, $policy->business_hours);
            $ticket->sla_status = 'within_sla';
            $ticket->save();

            SupportSlaLog::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'sla_policy_id' => $policy->id,
                'event' => 'created',
                'target_minutes' => $policy->resolution_target_minutes,
                'notes' => 'SLA Policy applied: ' . $policy->name,
            ]);
        }

        return $ticket;
    }

    /**
     * Calculate target datetime considering business hours (9am - 6pm Monday-Friday)
     */
    protected function calculateTargetTime(Carbon $startTime, int $minutes, bool $businessHoursOnly = true): Carbon
    {
        if (!$businessHoursOnly) {
            return $startTime->copy()->addMinutes($minutes);
        }

        // For business hours calculation, advance minutes during 9am - 6pm weekdays
        $current = $startTime->copy();
        $remainingMinutes = $minutes;

        while ($remainingMinutes > 0) {
            // If weekend, advance to next Monday 9:00 AM
            if ($current->isWeekend()) {
                $current->next(Carbon::MONDAY)->setTime(9, 0, 0);
                continue;
            }

            // If before 9:00 AM, advance to 9:00 AM
            if ($current->hour < 9) {
                $current->setTime(9, 0, 0);
            }

            // If after 6:00 PM (18:00), advance to next day 9:00 AM
            if ($current->hour >= 18) {
                $current->addDay()->setTime(9, 0, 0);
                continue;
            }

            // Calculate remaining business minutes in current day (until 18:00)
            $dayEnd = $current->copy()->setTime(18, 0, 0);
            $availableMinutesToday = $current->diffInMinutes($dayEnd);

            if ($remainingMinutes <= $availableMinutesToday) {
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutesToday;
                $current->addDay()->setTime(9, 0, 0);
            }
        }

        return $current;
    }

    /**
     * Record First Response SLA achievement
     */
    public function recordFirstResponse(Ticket $ticket): void
    {
        if (!$ticket->first_responded_at) {
            $now = Carbon::now();
            $ticket->first_responded_at = $now;

            $isBreached = $ticket->first_response_due_at && $now->isAfter($ticket->first_response_due_at);
            if ($isBreached && $ticket->sla_status !== 'breached') {
                $ticket->sla_status = 'breached';
            }
            $ticket->save();

            SupportSlaLog::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'sla_policy_id' => $ticket->sla_policy_id,
                'event' => 'first_response',
                'actual_minutes' => $ticket->created_at->diffInMinutes($now),
                'is_breached' => $isBreached,
                'notes' => $isBreached ? 'First response SLA target breached.' : 'First response achieved within target.',
            ]);
        }
    }

    /**
     * Record Resolution SLA achievement
     */
    public function recordResolution(Ticket $ticket): void
    {
        $now = Carbon::now();
        $ticket->resolved_at = $now;

        $isBreached = $ticket->resolution_due_at && $now->isAfter($ticket->resolution_due_at);
        if ($isBreached) {
            $ticket->sla_status = 'breached';
        }
        $ticket->save();

        SupportSlaLog::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'sla_policy_id' => $ticket->sla_policy_id,
            'event' => 'resolved',
            'actual_minutes' => $ticket->created_at->diffInMinutes($now),
            'is_breached' => $isBreached,
            'notes' => $isBreached ? 'Resolution SLA breached.' : 'Ticket resolved within SLA deadline.',
        ]);
    }

    /**
     * Pause SLA tracking (e.g. waiting for customer)
     */
    public function pauseSla(Ticket $ticket, ?string $reason = null): void
    {
        if (!$ticket->sla_paused_at) {
            $ticket->sla_paused_at = Carbon::now();
            $ticket->save();

            SupportSlaLog::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'sla_policy_id' => $ticket->sla_policy_id,
                'event' => 'paused',
                'notes' => $reason ?: 'SLA paused while waiting for customer response.',
            ]);
        }
    }

    /**
     * Resume SLA tracking and extend due dates by paused duration
     */
    public function resumeSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at) {
            $pausedMinutes = Carbon::now()->diffInMinutes($ticket->sla_paused_at);
            $ticket->sla_paused_minutes += $pausedMinutes;

            // Extend due dates
            if ($ticket->resolution_due_at) {
                $ticket->resolution_due_at = $ticket->resolution_due_at->addMinutes($pausedMinutes);
            }
            if ($ticket->first_response_due_at && !$ticket->first_responded_at) {
                $ticket->first_response_due_at = $ticket->first_response_due_at->addMinutes($pausedMinutes);
            }

            $ticket->sla_paused_at = null;
            $ticket->save();

            SupportSlaLog::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'sla_policy_id' => $ticket->sla_policy_id,
                'event' => 'resumed',
                'notes' => 'SLA resumed. Target extended by ' . $pausedMinutes . ' minutes.',
            ]);
        }
    }
}
