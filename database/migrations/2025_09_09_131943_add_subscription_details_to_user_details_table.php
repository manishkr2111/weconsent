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
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('subscription_id')->nullable()->after('profile_image');          // Stripe subscription ID
            $table->string('stripe_customer_id')->nullable()->after('subscription_id');     // Stripe customer ID
            $table->string('stripe_price_id')->nullable()->after('stripe_customer_id');     // Stripe price/plan ID
            $table->date('subscription_start_date')->nullable()->after('stripe_price_id'); // Start date of subscription
            $table->date('subscription_end_date')->nullable()->after('subscription_start_date'); // End date of subscription
            $table->string('subscription_status')->nullable()->after('subscription_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            //
        });
    }
};
