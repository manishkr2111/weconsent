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
        Schema::table('user_connection', function (Blueprint $table) {
            $table->string('consent_id')->nullable()->after('receiver_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_connection', function (Blueprint $table) {
            $table->dropColumn('consent_id');
        });
    }
};
