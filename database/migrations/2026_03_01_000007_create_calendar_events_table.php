<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->boolean('is_all_day')->default(false);

            // Hex color string e.g. "#4F46E5" — lets frontend use any color
            $table->string('color', 7)->default('#4F46E5');

            // Simple recurrence — null means no recurrence
            $table->enum('recurrence', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('recurrence_ends_on')->nullable(); // null = recurs forever

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index('start_datetime');
        });

        Schema::create('calendar_event_user', function (Blueprint $table) {
            $table->foreignId('calendar_event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->primary(['calendar_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');
        Schema::dropIfExists('calendar_events');
    }
};
