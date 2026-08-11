<?php

use Illuminate\Support\Facades\Event;
use Padmission\Tickets\Database\Seeders\TicketStatusSeeder;
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

test('unread response ticket count ignores hidden turn changed activity after the seen response', function () {
    Event::fake();

    $user = User::factory()->create();
    $supporter = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $ticket = Ticket::factory()->open()->create(['submitter_id' => $user->id]);

    $reply = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'type' => ActivityType::TurnChanged,
        'sender' => ActivitySender::System,
    ]);

    TicketUserState::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'last_seen_activity_id' => $reply->id,
    ]);

    expect(app(CopilotTicketService::class)->unreadResponseTicketCount($user))->toBe(0);
});

test('unread response ticket count excludes closed tickets', function () {
    Event::fake();

    $user = User::factory()->create();
    $supporter = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $ticket = Ticket::factory()->closed()->create(['submitter_id' => $user->id]);

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    expect(app(CopilotTicketService::class)->unreadResponseTicketCount($user))->toBe(0);
});

test('unread response ticket count includes open tickets with unseen responses', function () {
    Event::fake();

    $user = User::factory()->create();
    $supporter = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $ticket = Ticket::factory()->open()->create(['submitter_id' => $user->id]);

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    expect(app(CopilotTicketService::class)->unreadResponseTicketCount($user))->toBe(1);
});

test('mark ticket seen never moves the pointer backwards', function () {
    Event::fake();

    $user = User::factory()->create();
    $supporter = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $ticket = Ticket::factory()->open()->create(['submitter_id' => $user->id]);

    $latestReply = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $supporter->id,
        'type' => ActivityType::Message,
        'sender' => ActivitySender::Supporter,
    ]);

    $hiddenActivity = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'type' => ActivityType::TurnChanged,
        'sender' => ActivitySender::System,
    ]);

    TicketUserState::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'last_seen_activity_id' => $hiddenActivity->id,
    ]);

    app(CopilotTicketService::class)->markTicketSeen($user, $ticket);

    expect($ticket->ticketUserStates()->where('user_id', $user->id)->first())
        ->last_seen_activity_id->toBe($hiddenActivity->id);
});
