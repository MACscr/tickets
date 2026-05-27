<?php

namespace Padmission\Tickets\Http\DataMappers;

use Padmission\Tickets\Enums\Turn;
use Padmission\Tickets\Models\Ticket;

class TicketMapper
{
    public static function map(Ticket $ticket): array
    {
        $latestMessageContent = $ticket->latestMessage?->content;

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => TicketStatusMapper::map($ticket->status),
            'latest_message' => $latestMessageContent === null
                ? null
                : (string) str(html_entity_decode(strip_tags((string) $latestMessageContent), ENT_QUOTES | ENT_HTML5, 'UTF-8'))->words(20),
            'is_closed' => $ticket->isClosed,
            'needs_attention' => $ticket->turn === Turn::User,
            'updated_at' => $ticket->updated_at->diffForHumans(),
        ];
    }
}
