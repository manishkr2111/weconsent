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
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable(); // Path to the logo image
            $table->text('content')->nullable(); // Website content (can be HTML)
            $table->string('title')->nullable(); // Website title
            $table->string('meta_description')->nullable(); // Meta description
            $table->string('emails')->nullable();
            $table->string('contact_number')->nullable(); 
            $table->string('footer_text')->nullable(); // Footer text or content
            $table->timestamps(); // Created at and Updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
