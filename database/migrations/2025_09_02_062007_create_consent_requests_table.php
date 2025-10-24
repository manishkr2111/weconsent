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
        
        Schema::create('consent_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by'); 
            $table->unsignedBigInteger('sent_to');
            $table->enum('consent_type', ['chat','connection', 'intimate', 'date']);
            $table->string('date_type')->nullable();
            $table->string('intimacy_type')->nullable();
            $table->string('other_type_description')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'cancelled']); 
            $table->char('sent_otp', 6)->nullable();
            $table->char('accept_otp', 6)->nullable(); 
            $table->timestamp('sent_otp_verified_at')->nullable(); 
            $table->timestamp('accept_otp_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_by'); 
            $table->index('sent_to'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_requests');
    }
};
