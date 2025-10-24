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
             $table->string('gender_identity', 50)->nullable()->after('gender');
             $table->string('gender_orientation', 50)->nullable()->after('gender_identity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('gender_identity');
            $table->dropColumn('gender_orientation');
        });
    }
};
