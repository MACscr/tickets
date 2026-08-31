<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ticket_attachments', 'created_by')) {
            return;
        }

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('activity_id')->index();
        });
    }
};
