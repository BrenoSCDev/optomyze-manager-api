<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('doc_folders')->nullOnDelete();

            // Self-referential: a page can be a sub-page of another page
            $table->foreignId('parent_id')->nullable()->constrained('doc_pages')->cascadeOnDelete();

            $table->string('title')->default('Untitled');
            $table->string('icon', 10)->nullable();    // emoji icon
            $table->string('cover_url')->nullable();   // header cover image URL

            // Full document content stored as a block-editor JSON tree
            // (TipTap / BlockNote / ProseMirror compatible)
            $table->longText('content')->nullable();

            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_pages');
    }
};
