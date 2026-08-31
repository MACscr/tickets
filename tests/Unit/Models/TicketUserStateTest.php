<?php

use Padmission\Tickets\Models\TicketNotification;
use Padmission\Tickets\Models\TicketUserState;

it('stores per user ticket state in the ticket user states table', function () {
    expect((new TicketUserState)->getTable())->toBe('ticket_user_states');
});

it('keeps the old ticket notification model as a compatibility alias', function () {
    expect(new TicketNotification)
        ->toBeInstanceOf(TicketUserState::class)
        ->getTable()->toBe('ticket_user_states');
});
