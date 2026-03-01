<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy: nullable now, enforced when company system is implemented
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Nullable: allows root-level files (no parent folder)
            $table->foreignId('folder_id')
                ->nullable()
                ->constrained('drive_folders')
                ->nullOnDelete();

            // Client association: when set, this file is visible to that client
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            // original_name: the filename shown to the user (e.g. "Q1 Report.pdf")
            $table->string('original_name');

            // stored_name: relative path on disk (e.g. "drive/2026/03/uuid.pdf")
            // Never exposed directly to clients
            $table->string('stored_name');

            // Which filesystem disk holds the file (local, s3, etc.)
            $table->string('disk')->default('local');

            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // bytes

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_files');
    }
};
