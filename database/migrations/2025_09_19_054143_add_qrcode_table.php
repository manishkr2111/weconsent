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
         Schema::create('qrcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // link to users table
            $table->text('qr_data'); // JSON or string data for QR code
            $table->string('type')->nullable(); // optional: e.g., "user_info"
            $table->unsignedInteger('generated_count')->default(0); // track how many times QR generated
            $table->timestamp('scanned_at')->nullable(); // track last scan
            $table->string('path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcode');
    }
};
