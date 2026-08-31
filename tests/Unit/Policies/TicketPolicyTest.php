<?php

use Filament\Panel;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Policies\TicketPolicy;
use Padmission\Tickets\Tests\User;

test('manage denies the submitter', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create(['submitter_id' => $user->id]);

    expect((new TicketPolicy)->manage($user, $ticket))->toBeFalse();
});

test('manage allows a non-submitter with access to the ticket panel', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    expect((new TicketPolicy)->manage($user, $ticket))->toBeTrue();
});

test('manage denies a user without access to the ticket panel', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $restrictedUser = new class extends User
    {
        public function canAccessPanel(Panel $panel): bool
        {
            return false;
        }
    };
    $restrictedUser->id = $user->id + 1;

    expect((new TicketPolicy)->manage($restrictedUser, $ticket))->toBeFalse();
});
