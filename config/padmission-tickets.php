<?php

use App\Models\Tenant;
use App\Models\User;
<<<<<<< HEAD
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Padmission\Tickets\Enums\NotificationStrategy;
use Padmission\Tickets\Events\TicketActivityEvent;
use Padmission\Tickets\Events\TicketAssignedEvent;
use Padmission\Tickets\Events\TicketClosedEvent;
use Padmission\Tickets\Events\TicketCreatedEvent;
use Padmission\Tickets\Jobs\NotificationJob;
use Padmission\Tickets\Listeners\TicketNotificationListener;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketActivity;
use Padmission\Tickets\Models\TicketAttachment;
use Padmission\Tickets\Models\TicketDisposition;
use Padmission\Tickets\Models\TicketNotification;
use Padmission\Tickets\Models\TicketPriority;
use Padmission\Tickets\Models\TicketStatus;
=======
use Carbon\CarbonInterval;
use Illuminate\Contracts\Auth\Authenticatable;
use Padmission\Tickets\Enums\NotificationStrategy;
use Padmission\Tickets\Events;
use Padmission\Tickets\Jobs\NotificationJob;
use Padmission\Tickets\Models;
use Padmission\Tickets\Notifications;
>>>>>>> origin/main

return [
    'run_migrations' => true,

    /**
     * Swap package models (left) with your own model (right).
     * Your models should extend the package base models to inherit observers automatically.
     * Example: class CustomTicket extends \Padmission\Tickets\Models\Ticket { }
     *
     * @var array<class-string, class-string>
     */
    'models' => [
        Authenticatable::class => User::class,
<<<<<<< HEAD
        Ticket::class => Ticket::class,
        TicketActivity::class => TicketActivity::class,
        TicketAttachment::class => TicketAttachment::class,
        TicketDisposition::class => TicketDisposition::class,
        TicketNotification::class => TicketNotification::class,
        TicketPriority::class => TicketPriority::class,
        TicketStatus::class => TicketStatus::class,
=======
        Models\Ticket::class => Models\Ticket::class,
        Models\TicketActivity::class => Models\TicketActivity::class,
        Models\TicketAttachment::class => Models\TicketAttachment::class,
        Models\TicketDisposition::class => Models\TicketDisposition::class,
        Models\TicketNotification::class => Models\TicketNotification::class,
        Models\TicketPriority::class => Models\TicketPriority::class,
        Models\TicketStatus::class => Models\TicketStatus::class,
>>>>>>> origin/main
    ],

    /**
     * Swap package job classes (left) with your own job classes (right).
     * Your jobs should extend the package base jobs to inherit the same behavior.
     * Example: class CustomNotificationJob extends \Padmission\Tickets\Jobs\NotificationJob { }
     *
     * @var array<class-string, class-string>
     */
    'jobs' => [
        NotificationJob::class => NotificationJob::class,
    ],

    'tenancy' => [
        'enabled' => false,
        'tenancy_model' => Tenant::class,
<<<<<<< HEAD
    ],

    'levels' => [
        // 'default' => fn () => __('padmission-tickets::tickets.levels.default'),
        // 'escalated' => fn () => __('padmission-tickets::tickets.levels.escalated'),
=======
>>>>>>> origin/main
    ],

    /**
     * Attachment storage configuration
     *
     * Settings for file storage and media handling.
     *
     * @var array<string, string|null>
     */
    'attachments' => [
        /**
         * The disk on which to store added files and derived images by default.
         * Choose one or more of the disks configured in config/filesystems.php.
         * Typically, this should match the default Spatie Media library disk name.
         *
         * If null, it will default to filament.default_filesystem_disk
         *
         * @var string|null
         */
        'preview_disk' => env('MEDIA_DISK', 's3'),
        'disk' => env('MEDIA_DISK', 's3'),
    ],

<<<<<<< HEAD
    'event-listeners' => [
        TicketActivityEvent::class => [
            TicketNotificationListener::class,
        ],
        TicketAssignedEvent::class => [
            TicketNotificationListener::class,
        ],
        TicketClosedEvent::class => [
            TicketNotificationListener::class,
        ],
        TicketCreatedEvent::class => [
            TicketNotificationListener::class,
        ],
    ],

=======
>>>>>>> origin/main
    /**
     * Notification configuration
     *
     * @var array<string, class-string|null>
     */
    'notifications' => [
        Events\TicketCreatedEvent::class => Notifications\TicketNotification::class,
        Events\TicketActivityEvent::class => Notifications\TicketNotification::class,
        Events\TicketAssignedEvent::class => Notifications\TicketNotification::class,
        Events\TicketClosedEvent::class => Notifications\TicketNotification::class,
    ],

    /**
     * Default notification strategy when user doesn't define one
     * Options: Padmission\Tickets\Enums\NotificationStrategy::Immediate, Padmission\Tickets\Enums\NotificationStrategy::Debounced
     *
     * @var string
     */
    'default-notification-strategy' => NotificationStrategy::Debounced,

    /**
     * Debounce time in seconds for grouped notifications
     *
     * @var int
     */
    'notification-debounce' => CarbonInterval::minutes(10)->totalSeconds,

    /**
     * Maximum number of activities to include in a single notification
     *
     * @var int
     */
    'notification-max-events' => 10,
];
