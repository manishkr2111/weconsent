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
        Schema::create('billing_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('subscription_id')->nullable(); // Stripe subscription ID
            $table->string('customer_id')->nullable();     // Stripe customer ID
            $table->string('price_id')->nullable();        // Stripe price ID
            $table->string('status')->nullable();          // subscription status
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            
            $table->string('payment_method_id')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->integer('card_exp_month')->nullable();
            $table->integer('card_exp_year')->nullable();

            // Billing contact details
            $table->string('billing_email')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_details');
    }
};
