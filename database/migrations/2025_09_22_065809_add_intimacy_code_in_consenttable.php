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
            $table->string('intimacy_code')->nullable()->after('accept_otp'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_requests', function (Blueprint $table) {
            $table->dropColumn('accept_otp');
        });
    }
};
