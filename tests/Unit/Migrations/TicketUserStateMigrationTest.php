<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Padmission\Tickets\Database\Seeders\TicketStatusSeeder;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Models\TicketActivity;
use Padmission\Tickets\Tests\User;

test('renaming notification rows backfills the last notified activity cutoff', function () {
    Event::fake();

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $notifiedActivity = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->addMinute(),
    ]);

    Schema::dropIfExists('ticket_user_states');
    Schema::dropIfExists('ticket_notifications');

    Schema::create('ticket_notifications', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->index('user_id');
        $table->unique(['ticket_id', 'user_id']);
    });

    DB::table('ticket_notifications')->insert([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(15),
        'updated_at' => now()->subMinutes(5),
    ]);

    $migration = require __DIR__.'/../../../database/migrations/2025_12_23_150556_rename_ticket_notifications_to_ticket_user_states.php';

    $migration->up();

    expect(DB::table('ticket_user_states')->where('ticket_id', $ticket->id)->where('user_id', $user->id)->value('last_notified_activity_id'))
        ->toBe($notifiedActivity->id);
});

test('renaming notification rows backfills the last seen activity floor from the notified cutoff', function () {
    Event::fake();

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $notifiedActivity = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    Schema::dropIfExists('ticket_user_states');
    Schema::dropIfExists('ticket_notifications');

    Schema::create('ticket_notifications', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->index('user_id');
        $table->unique(['ticket_id', 'user_id']);
    });

    DB::table('ticket_notifications')->insert([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(15),
        'updated_at' => now()->subMinutes(5),
    ]);

    $migration = require __DIR__.'/../../../database/migrations/2025_12_23_150556_rename_ticket_notifications_to_ticket_user_states.php';

    $migration->up();

    expect(DB::table('ticket_user_states')->where('ticket_id', $ticket->id)->where('user_id', $user->id)->value('last_seen_activity_id'))
        ->toBe($notifiedActivity->id);
});

test('backfills null pointers when the legacy table already has the pointer columns', function () {
    Event::fake();

    $user = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $openTicket = Ticket::factory()->create();
    $closedTicket = Ticket::factory()->closed()->create();

    $openNotifiedActivity = TicketActivity::factory()->create([
        'ticket_id' => $openTicket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    TicketActivity::factory()->create([
        'ticket_id' => $openTicket->id,
        'created_at' => now()->addMinute(),
    ]);

    TicketActivity::factory()->create([
        'ticket_id' => $closedTicket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $closedLatestActivity = TicketActivity::factory()->create([
        'ticket_id' => $closedTicket->id,
        'created_at' => now()->addMinute(),
    ]);

    Schema::dropIfExists('ticket_user_states');
    Schema::dropIfExists('ticket_notifications');

    Schema::create('ticket_last_seen', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('last_seen_activity_id')->nullable()->constrained('ticket_activities')->nullOnDelete();
        $table->foreignId('last_notified_activity_id')->nullable()->constrained('ticket_activities')->nullOnDelete();
        $table->timestamps();

        $table->index('user_id');
        $table->unique(['ticket_id', 'user_id']);
    });

    DB::table('ticket_last_seen')->insert([
        [
            'ticket_id' => $openTicket->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(5),
        ],
        [
            'ticket_id' => $closedTicket->id,
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(5),
        ],
    ]);

    $migration = require __DIR__.'/../../../database/migrations/2025_12_23_150556_rename_ticket_notifications_to_ticket_user_states.php';

    $migration->up();

    expect(DB::table('ticket_user_states')->where('ticket_id', $openTicket->id)->where('user_id', $user->id)->value('last_seen_activity_id'))
        ->toBe($openNotifiedActivity->id)
        ->and(DB::table('ticket_user_states')->where('ticket_id', $closedTicket->id)->where('user_id', $user->id)->value('last_seen_activity_id'))
        ->toBe($closedLatestActivity->id);
});

test('does not overwrite existing last seen pointers when re-run', function () {
    Event::fake();

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $seenActivity = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->addMinute(),
    ]);

    DB::table('ticket_user_states')->insert([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'last_seen_activity_id' => $seenActivity->id,
        'last_notified_activity_id' => $seenActivity->id,
        'created_at' => now()->subMinutes(15),
        'updated_at' => now()->subMinutes(5),
    ]);

    $migration = require __DIR__.'/../../../database/migrations/2025_12_23_150556_rename_ticket_notifications_to_ticket_user_states.php';

    $migration->up();

    expect(DB::table('ticket_user_states')->where('ticket_id', $ticket->id)->where('user_id', $user->id)->value('last_seen_activity_id'))
        ->toBe($seenActivity->id);
});

test('backfills last seen to the max activity for closed tickets', function () {
    Event::fake();

    $user = User::factory()->create();
    (new TicketStatusSeeder)->run();
    $ticket = Ticket::factory()->closed()->create();

    TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $latestActivity = TicketActivity::factory()->create([
        'ticket_id' => $ticket->id,
        'created_at' => now()->addMinute(),
    ]);

    Schema::dropIfExists('ticket_user_states');
    Schema::dropIfExists('ticket_notifications');

    Schema::create('ticket_last_seen', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->index('user_id');
        $table->unique(['ticket_id', 'user_id']);
    });

    DB::table('ticket_last_seen')->insert([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(15),
        'updated_at' => now()->subMinutes(5),
    ]);

    $migration = require __DIR__.'/../../../database/migrations/2025_12_23_150556_rename_ticket_notifications_to_ticket_user_states.php';

    $migration->up();

    expect(DB::table('ticket_user_states')->where('ticket_id', $ticket->id)->where('user_id', $user->id)->value('last_seen_activity_id'))
        ->toBe($latestActivity->id);
});
