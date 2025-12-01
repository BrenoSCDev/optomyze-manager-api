<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Role & Status
            $table->enum('role', ['admin', 'agent'])->default('agent');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            // Profile Information
            $table->string('avatar')->nullable(); // URL or path to avatar image
            $table->string('title')->nullable(); // Job title (e.g., "Senior Account Manager")
            
            // Additional Contact
            $table->string('phone_secondary')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('BR');
            
            // Employment Details
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('role');
            $table->index('status');
            $table->index(['email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
