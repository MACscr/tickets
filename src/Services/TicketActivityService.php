<?php

namespace Padmission\Tickets\Services;

use Illuminate\Support\Collection;
use Padmission\Tickets\Enums\ActivityType;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketUserState;

class TicketActivityService
{
    /**
     * Get unread activities for a ticket within the specified time range
     */
    public function getUnreadActivities(
        Ticket $ticket,
        $notifiable,
        int $maxEvents,
        int $maxDays
    ): Collection {
        $userState = $this->getUserState($ticket, $notifiable);

        return $ticket
            ->ticketActivities()
            ->with('user')
            ->where('created_at', '>', now()->subDays($maxDays))
            ->where('created_at', '<=', now())
            ->where('type', '!=', ActivityType::TurnChanged)
            ->when($userState?->last_notified_activity_id, function ($query, int|string $lastNotifiedActivityId) {
                $query->where('id', '>', $lastNotifiedActivityId);
            })
            ->orderBy('created_at', 'asc')
            ->limit($maxEvents)
            ->get();
    }

    public function getUserState(Ticket $ticket, $notifiable): ?TicketUserState
    {
        /** @var TicketUserState|null $userState */
        $userState = $ticket
            ->ticketUserStates()
            ->where('user_id', $notifiable->getKey())
            ->latest()
            ->first();

        return $userState;
    }

    public function markActivitiesNotified(Ticket $ticket, $notifiable, ?int $lastNotifiedActivityId = null): void
    {
        $userState = $this->getUserState($ticket, $notifiable);

        if ($userState) {
            if ($lastNotifiedActivityId) {
                $userState->last_notified_activity_id = $lastNotifiedActivityId;
                $userState->save();

                return;
            }

            $userState->touch();

            return;
        }

        $values = [];

        if ($lastNotifiedActivityId) {
            $values['last_notified_activity_id'] = $lastNotifiedActivityId;
        }

        $ticket->ticketUserStates()->create([
            'user_id' => $notifiable->getKey(),
            ...$values,
        ]);
    }

    public function getLastNotification(Ticket $ticket, $notifiable): ?TicketUserState
    {
        return $this->getUserState($ticket, $notifiable);
    }

    public function markNotificationUpdated(Ticket $ticket, $notifiable): void
    {
        $lastNotifiedActivityId = $ticket
            ->ticketActivities()
            ->where('type', '!=', ActivityType::TurnChanged)
            ->latest('id')
            ->value('id');

        if (is_numeric($lastNotifiedActivityId)) {
            $this->markActivitiesNotified($ticket, $notifiable, (int) $lastNotifiedActivityId);
        } else {
            $this->markActivitiesNotified($ticket, $notifiable);
        }
    }
}
