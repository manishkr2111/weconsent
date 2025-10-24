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
        Schema::create('recent_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Reference to users table
            $table->string('action'); // e.g., "created campaign", "deleted post"
            $table->text('details')->nullable(); // optional details about the action
            $table->string('type')->nullable(); // optional type/category of activity
            $table->timestamps();

            // Foreign key constraint (optional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recent_activities');
    }
};
