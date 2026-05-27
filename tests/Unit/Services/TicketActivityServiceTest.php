<?php

use Illuminate\Support\Facades\Config;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketActivity;
use Padmission\Tickets\Models\TicketUserState;
use Padmission\Tickets\Notifications\TicketNotification;
use Padmission\Tickets\Services\TicketActivityService;
use Padmission\Tickets\Tests\User;

beforeEach(function () {
    $this->service = new TicketActivityService;
    $this->ticket = Ticket::factory()->create();
    $this->user = User::factory()->create();
});

test('can get unread activities within date range', function () {
    // Create an old activity (should be excluded)
    TicketActivity::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_at' => now()->subDays(15),
    ]);

    // Create a new activity (should be included)
    $newActivity = TicketActivity::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_at' => now()->subDays(2),
    ]);

    $activities = $this->service->getUnreadActivities($this->ticket, $this->user, 10, 7);

    expect($activities)->toHaveCount(1);
    expect($activities->first()->id)->toBe($newActivity->id);
});

test('can get unread activities within date range with latest notification state', function () {
    $oldActivity = TicketActivity::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_at' => now()->subDays(4),
    ]);

    $newActivity = TicketActivity::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_at' => now()->subDays(2),
    ]);

    TicketUserState::factory()->create([
        'user_id' => $this->user->id,
        'ticket_id' => $this->ticket->id,
        'last_notified_activity_id' => $oldActivity->id,
        'updated_at' => now(),
    ]);

    $activities = $this->service->getUnreadActivities($this->ticket, $this->user, 10, 7);

    expect($activities)->toHaveCount(1);
    expect($activities->first()->id)->toBe($newActivity->id);
});

test('can get user state for user and ticket', function () {
    $userB = User::factory()->create();
    $ticketB = Ticket::factory()->create();

    $notification = TicketUserState::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->user->id,
    ]);

    TicketUserState::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $userB->id,
    ]);

    TicketUserState::factory()->create([
        'ticket_id' => $ticketB->id,
        'user_id' => $this->user->id,
    ]);

    $userState = $this->service->getUserState($this->ticket, $this->user);

    expect($userState)->not->toBeNull();
    expect($userState->id)->toBe($notification->id);
});

test('returns null when no user state exists', function () {
    $userState = $this->service->getUserState($this->ticket, $this->user);

    expect($userState)->toBeNull();
});

test('respects max events configuration', function () {
    Config::set('padmission-tickets.notification-max-events', 2);

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    // Create 4 activities
    for ($i = 0; $i < 4; $i++) {
        TicketActivity::factory()->create([
            'ticket_id' => $ticket->id,
        ]);
    }

    $notification = new TicketNotification($ticket, 'history');

    // Use the activity service to get unread activities
    $activityService = app(TicketActivityService::class);
    $activities = $activityService->getUnreadActivities($ticket, $user, 2, 7);

    expect($activities)->toHaveCount(2); // Should limit to 2
});

test('returns null when user has no previous state for ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();
    $notification = new TicketNotification($ticket, 'history');

    $activityService = app(TicketActivityService::class);
    $userState = $activityService->getUserState($ticket, $user);

    expect($userState)->toBeNull();
});

test('gets user state for specific user and ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $userStateRecord = $ticket->ticketUserStates()->create([
        'user_id' => $user->getKey(),
        'created_at' => now()->subHour(),
    ]);

    $activityService = app(TicketActivityService::class);
    $userState = $activityService->getUserState($ticket, $user);

    expect($userState)
        ->not->toBeNull()
        ->id->toBe($userStateRecord->id)
        ->user_id->toBe($user->getKey());
});
