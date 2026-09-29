<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallLog;
use App\Models\CallParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallService
{
    public function getDashboardData(User $user, ?string $filter = null, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        $baseQuery = Call::where('company_id', $companyId);

        $totalCalls = (clone $baseQuery)->count();
        $incomingCalls = (clone $baseQuery)->where('direction', 'incoming')->count();
        $outgoingCalls = (clone $baseQuery)->where('direction', 'outgoing')->count();
        $missedCalls = (clone $baseQuery)->where('status', 'missed')->count();

        $query = (clone $baseQuery)->with(['caller:id,name,email,avatar,role,phone', 'receiver:id,name,email,avatar,role,phone'])
            ->orderBy('created_at', 'desc');

        if ($filter && in_array($filter, ['incoming', 'outgoing', 'missed', 'completed'])) {
            if ($filter === 'missed') {
                $query->where('status', 'missed');
            } elseif ($filter === 'incoming' || $filter === 'outgoing') {
                $query->where('direction', $filter);
            } else {
                $query->where('status', $filter);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('caller', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('receiver', fn($rq) => $rq->where('name', 'like', "%{$search}%"))
                    ->orWhere('room_id', 'like', "%{$search}%");
            });
        }

        $callsList = $query->take(50)->get()->map(function ($call) {
            $durationMinutes = floor($call->duration / 60);
            $durationSeconds = $call->duration % 60;
            $formattedDuration = sprintf('%02d:%02d', $durationMinutes, $durationSeconds);

            return [
                'id' => $call->id,
                'caller' => [
                    'id' => $call->caller?->id,
                    'name' => $call->caller?->name ?? 'External Caller',
                    'avatar' => $call->caller?->avatar ?? 'https://i.pravatar.cc/150?u=caller',
                    'phone' => $call->caller?->phone ?? '+1 (555) 000-0000',
                    'role' => $call->caller?->role ?? 'User',
                ],
                'receiver' => [
                    'id' => $call->receiver?->id,
                    'name' => $call->receiver?->name ?? 'External Contact',
                    'avatar' => $call->receiver?->avatar ?? 'https://i.pravatar.cc/150?u=receiver',
                    'phone' => $call->receiver?->phone ?? '+1 (555) 000-0000',
                    'role' => $call->receiver?->role ?? 'User',
                ],
                'type' => $call->type, // voice, video
                'direction' => $call->direction, // incoming, outgoing
                'status' => $call->status, // completed, missed, rejected, busy, cancelled
                'duration' => $call->duration,
                'durationFormatted' => $formattedDuration,
                'date' => $call->created_at->format('M d, Y h:i A'),
                'timeAgo' => $call->created_at->diffForHumans(),
                'provider' => $call->provider,
                'roomId' => $call->room_id,
            ];
        });

        // Contacts list for dialer
        $contacts = User::where('company_id', $companyId)
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'email', 'avatar', 'role', 'phone', 'is_active')
            ->get();

        return [
            'metrics' => [
                'totalCalls' => $totalCalls,
                'incoming' => $incomingCalls,
                'outgoing' => $outgoingCalls,
                'missed' => $missedCalls,
            ],
            'calls' => $callsList,
            'contacts' => $contacts,
        ];
    }

    public function initiateCall(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;
        $roomId = 'ROOM-' . strtoupper(bin2hex(random_bytes(4)));

        $call = Call::create([
            'company_id' => $companyId,
            'caller_id' => $user->id,
            'receiver_id' => $data['receiver_id'] ?? null,
            'room_id' => $roomId,
            'type' => $data['type'] ?? 'voice',
            'direction' => 'outgoing',
            'status' => 'completed',
            'start_time' => now(),
            'end_time' => now()->addMinutes(rand(1, 15))->addSeconds(rand(5, 50)),
            'duration' => rand(45, 950),
            'provider' => $data['provider'] ?? 'internal_webrtc',
            'notes' => $data['notes'] ?? 'Internal WebRTC enterprise voice call session.',
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $user->id,
            'status' => 'joined',
            'joined_at' => now(),
        ]);

        if (!empty($data['receiver_id'])) {
            CallParticipant::create([
                'call_id' => $call->id,
                'user_id' => $data['receiver_id'],
                'status' => 'joined',
                'joined_at' => now(),
            ]);
        }

        CallLog::create([
            'call_id' => $call->id,
            'event' => 'CALL_INITIATED',
            'metadata' => [
                'webrtc_session_id' => $roomId,
                'codec' => 'Opus/48000/2',
                'encryption' => 'DTLS-SRTP',
            ],
        ]);

        return $call->load(['caller', 'receiver']);
    }

    public function updateCall(int $id, array $data)
    {
        $call = Call::findOrFail($id);
        $call->update($data);

        CallLog::create([
            'call_id' => $call->id,
            'event' => 'CALL_UPDATED',
            'metadata' => $data,
        ]);

        return $call;
    }
}
