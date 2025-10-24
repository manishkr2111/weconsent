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
        Schema::create('user_connection', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked', 'cancelled', 'expired'])->default('pending');
            $table->timestamps();
            $table->softDeletes();  // Enables soft deletes

            // Foreign key constraints
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint for preventing duplicate connections
            $table->unique(['sender_id', 'receiver_id']);
            $table->unique(['receiver_id', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_connection');
    }
};
