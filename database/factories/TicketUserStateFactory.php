<?php

namespace Padmission\Tickets\Database\Factories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketUserState;
use Padmission\Tickets\TicketPlugin;

class TicketUserStateFactory extends Factory
{
    protected $model = TicketUserState::class;

    public function modelName(): string
    {
        return TicketPlugin::resolveModelClass($this->model);
    }

    public function definition(): array
    {
        return [
            'ticket_id' => TicketPlugin::resolveModelClass(Ticket::class)::factory(),
            'user_id' => TicketPlugin::resolveModelClass(Authenticatable::class)::factory(),
        ];
    }
}
