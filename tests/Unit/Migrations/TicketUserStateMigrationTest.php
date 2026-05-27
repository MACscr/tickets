<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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
