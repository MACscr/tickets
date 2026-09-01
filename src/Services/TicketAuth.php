<?php

namespace Padmission\Tickets\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Padmission\Tickets\Models\Ticket;

class TicketAuth
{
    public function getUserId(): int|string|null
    {
        return Filament::auth()->id() ?? session()->get('padmission-tickets::user_key');
    }

    public function authorizeTicketAccess(Ticket $ticket, ?Authenticatable $user): void
    {
        if ($user?->getAuthIdentifier() === $ticket->submitter_id) {
            return;
        }

        $isAuthorized = $user !== null
            && Gate::forUser($user)->allows('view', $ticket)
            && Gate::forUser($user)->allows('manage', $ticket);

        abort_unless($isAuthorized, 403);
    }
}
