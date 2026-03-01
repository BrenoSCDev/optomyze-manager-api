<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy: nullable now, enforced when company system is implemented
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Client association: when set, this folder is shared with that client
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            // Self-referential for unlimited nesting
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('drive_folders')
                ->cascadeOnDelete();

            $table->string('name');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folders');
    }
};
