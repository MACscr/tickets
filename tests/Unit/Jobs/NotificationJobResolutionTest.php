<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Padmission\Tickets\Enums\ActivityType;
use Padmission\Tickets\Events\TicketActivityEvent;
use Padmission\Tickets\Events\TicketAssignedEvent;
use Padmission\Tickets\Events\TicketClosedEvent;
use Padmission\Tickets\Events\TicketCreatedEvent;
use Padmission\Tickets\Jobs\NotificationJob;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Tests\User;
use Padmission\Tickets\TicketPlugin;

test('it resolves default notification job class', function () {
    $resolvedClass = TicketPlugin::resolveJobClass(NotificationJob::class);

    expect($resolvedClass)->toBe(NotificationJob::class);
});

test('it resolves custom notification job class when configured', function () {
    // Mock custom job class
    $customJobClass = 'App\\Jobs\\CustomNotificationJob';

    // Temporarily set config
    config(['padmission-tickets.jobs' => [
        NotificationJob::class => $customJobClass,
    ]]);

    $resolvedClass = TicketPlugin::resolveJobClass(NotificationJob::class);

    expect($resolvedClass)->toBe($customJobClass);

    config(['padmission-tickets.jobs' => [
        NotificationJob::class => NotificationJob::class,
    ]]);
});

test('notification job has extensible methods', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();
    $event = new TicketCreatedEvent($ticket);

    $job = new NotificationJob($user, $ticket, $event);

    // Test that protected methods exist and are accessible to child classes
    $reflection = new ReflectionClass($job);

    expect($reflection->hasMethod('resolveUser'))->toBeTrue();
    expect($reflection->hasMethod('resolveModel'))->toBeTrue();
    expect($reflection->hasMethod('sendNotification'))->toBeTrue();
    expect($reflection->hasMethod('getNotificationClass'))->toBeTrue();
});

test('notification job generates unique id for debouncing', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();
    $event = new TicketCreatedEvent($ticket);

    $job = new NotificationJob($user, $ticket, $event);

    $uniqueId = $job->uniqueId();

    // Should contain ticket class, ticket key, and user id
    expect($uniqueId)->toContain('notification-');
    expect($uniqueId)->toContain((string) $ticket->getKey());
    expect($uniqueId)->toContain((string) $user->getKey());
});

test('unique id differs across event types but coalesces within the same event type', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $activityJob = new NotificationJob($user, $ticket, new TicketActivityEvent($ticket, ActivityType::Message));
    $anotherActivityJob = new NotificationJob($user, $ticket, new TicketActivityEvent($ticket, ActivityType::Message));
    $closedJob = new NotificationJob($user, $ticket, new TicketClosedEvent($ticket));
    $createdJob = new NotificationJob($user, $ticket, new TicketCreatedEvent($ticket));
    $assignedJob = new NotificationJob($user, $ticket, new TicketAssignedEvent($ticket));

    expect($activityJob->uniqueId())->toBe($anotherActivityJob->uniqueId());

    expect(collect([$activityJob, $closedJob, $createdJob, $assignedJob])->map->uniqueId()->unique())
        ->toHaveCount(4);
});

test('constructor invokes the initializeJob hook for subclasses', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();
    $event = new TicketCreatedEvent($ticket);

    $job = new class($user, $ticket, $event) extends NotificationJob
    {
        public array $initializedWith = [];

        protected function initializeJob(Authenticatable $user, Ticket $model): void
        {
            $this->initializedWith = [$user->getKey(), $model->getKey()];
        }
    };

    expect($job->initializedWith)->toBe([$user->getKey(), $ticket->getKey()]);
});
