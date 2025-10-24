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
        Schema::table('consent_requests', function (Blueprint $table) {
            $table->timestamp('event_date')->nullable()->after('status');
            $table->integer('event_duration')->nullable()->after('event_date');
            $table->json('location')->nullable()->after('event_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_requests', function (Blueprint $table) {
            $table->dropColumn('event_date');      // drop the date column
            $table->dropColumn('event_duration');
            $table->dropColumn('location');
        });
    }
};
