<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_folders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamps();
        });

        // Add prospect_folder_id to clients table if not exists
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'prospect_folder_id')) {
                $table->unsignedBigInteger('prospect_folder_id')->nullable()->after('id');
                $table->foreign('prospect_folder_id')->references('id')->on('prospect_folders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'prospect_folder_id')) {
                $table->dropForeign(['prospect_folder_id']);
                $table->dropColumn('prospect_folder_id');
            }
        });

        Schema::dropIfExists('prospect_folders');
    }
};
