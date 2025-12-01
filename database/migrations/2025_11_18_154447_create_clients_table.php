<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Company Information
            $table->string('company_name');
            $table->string('legal_name')->nullable();
            $table->string('industry')->nullable();
            $table->integer('employees')->nullable();
            $table->string('tax_id')->nullable(); // CNPJ, VAT, EIN, etc.

            // Contact Person
            $table->string('contact_name')->nullable();
            $table->string('position')->nullable();

            // Contact Methods
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();

            // Online Presence
            $table->text('website')->nullable();

            // Social Media
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter_x')->nullable();
            $table->string('youtube')->nullable();
            $table->string('tiktok')->nullable();

            // Address
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();

            // Business Information
            $table->decimal('value', 12, 2)->nullable();
            $table->enum('status', ['lead', 'prospect', 'active', 'inactive', 'lost'])
                ->default('lead');
            $table->string('source')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // CRM
            $table->string('tags')->nullable(); // comma separated
            $table->text('notes')->nullable();

            // System
            $table->boolean('crm_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
