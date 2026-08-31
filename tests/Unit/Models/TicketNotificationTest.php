<?php

use Padmission\Tickets\Models\TicketNotification;

it('keeps the ticket notification model as a ticket user state compatibility alias', function () {
    expect((new TicketNotification)->getTable())->toBe('ticket_user_states');
});
