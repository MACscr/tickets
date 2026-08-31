<?php

use Padmission\Tickets\Enums\ActivitySender;
use Padmission\Tickets\Enums\ActivityType;
use Padmission\Tickets\Models\TicketActivity;

test('system activity content falls back when structured data is missing', function (ActivityType $type): void {
    $activity = new TicketActivity([
        'sender' => ActivitySender::System,
        'type' => $type,
        'data' => null,
    ]);

    expect($activity->content)->toBeString()->not->toBeEmpty();
})->with([
    ActivityType::AssigneeChanged,
    ActivityType::TurnChanged,
    ActivityType::StatusChanged,
    ActivityType::PriorityChanged,
]);
