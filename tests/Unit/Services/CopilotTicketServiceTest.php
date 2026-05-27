<?php

use Illuminate\Support\Facades\Event;
use Padmission\Tickets\Enums\ActivitySender;
use Padmission\Tickets\Enums\ActivityType;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketActivity;
use Padmission\Tickets\Models\TicketUserState;
use Padmission\Tickets\Services\CopilotTicketService;
use Padmission\Tickets\Tests\User;

test('marking a ticket seen records the latest support response without changing notification state', function () {
    Event::fake();

    $user = User::factory()->create();
    $supporter = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'submitter_id' => $user->id,
    ]);

    $firstReply = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    $latestReply = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    TicketUserState::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'last_notified_activity_id' => $firstReply->id,
    ]);

    app(CopilotTicketService::class)->markTicketSeen($user, $ticket);

    $state = $ticket->ticketUserStates()->where('user_id', $user->id)->first();

    expect($state)
        ->last_seen_activity_id->toBe($latestReply->id)
        ->last_notified_activity_id->toBe($firstReply->id);
});
