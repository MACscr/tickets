<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $renamedFromNotifications = false;

        if (Schema::hasTable('ticket_last_seen') && ! Schema::hasTable('ticket_user_states')) {
            Schema::rename('ticket_last_seen', 'ticket_user_states');
        }

        if (Schema::hasTable('ticket_notifications') && ! Schema::hasTable('ticket_user_states')) {
            Schema::rename('ticket_notifications', 'ticket_user_states');

            $renamedFromNotifications = true;
        }

        if (! Schema::hasTable('ticket_user_states')) {
            return;
        }

        $hasLastSeenActivityId = Schema::hasColumn('ticket_user_states', 'last_seen_activity_id');
        $hasLastNotifiedActivityId = Schema::hasColumn('ticket_user_states', 'last_notified_activity_id');

        Schema::table('ticket_user_states', function (Blueprint $table) use ($hasLastNotifiedActivityId, $hasLastSeenActivityId): void {
            if (! $hasLastSeenActivityId) {
                $table
                    ->foreignId('last_seen_activity_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('ticket_activities')
                    ->nullOnDelete();
            }

            if (! $hasLastNotifiedActivityId) {
                $table
                    ->foreignId('last_notified_activity_id')
                    ->nullable()
                    ->after('last_seen_activity_id')
                    ->constrained('ticket_activities')
                    ->nullOnDelete();
            }
        });

        if ($renamedFromNotifications && ! $hasLastNotifiedActivityId) {
            DB::table('ticket_user_states')
                ->whereNull('last_notified_activity_id')
                ->update([
                    'last_notified_activity_id' => DB::raw('(
                        select max(ticket_activities.id)
                        from ticket_activities
                        where ticket_activities.ticket_id = ticket_user_states.ticket_id
                            and ticket_activities.created_at <= ticket_user_states.updated_at
                    )'),
                ]);
        }

        if (! $hasLastSeenActivityId) {
            DB::table('ticket_user_states')
                ->whereNull('last_seen_activity_id')
                ->whereNotNull('last_notified_activity_id')
                ->update([
                    'last_seen_activity_id' => DB::raw('last_notified_activity_id'),
                ]);

            DB::table('ticket_user_states')
                ->whereExists(function ($query): void {
                    $query
                        ->selectRaw('1')
                        ->from('tickets')
                        ->whereColumn('tickets.id', 'ticket_user_states.ticket_id')
                        ->whereNotNull('tickets.closed_at');
                })
                ->update([
                    'last_seen_activity_id' => DB::raw('(
                        select max(ticket_activities.id)
                        from ticket_activities
                        where ticket_activities.ticket_id = ticket_user_states.ticket_id
                    )'),
                ]);
        }
    }
};
