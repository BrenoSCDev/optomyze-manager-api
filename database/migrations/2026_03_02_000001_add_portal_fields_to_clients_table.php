<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('portal_slug')->unique()->nullable()->after('crm_active');
            $table->string('portal_key')->nullable()->after('portal_slug');  // hashed
            $table->boolean('portal_enabled')->default(false)->after('portal_key');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['portal_slug', 'portal_key', 'portal_enabled']);
        });
    }
};
