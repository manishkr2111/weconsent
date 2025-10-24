<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_verification_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('applicant_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('country')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('review_status')->nullable();
            $table->string('review_answer')->nullable();
            $table->timestamp('create_date')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->json('id_docs')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('id_verification_details');
    }
};
