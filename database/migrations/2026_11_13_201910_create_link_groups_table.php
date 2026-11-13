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
        Schema::create('link_groups', function (Blueprint $table) {
            $table->id();
            // Restrict: hard-deleting a user must never cascade away groups and free their public slugs.
            $table->foreignId('user_id')->index()->constrained()->restrictOnDelete();
            $table->string('name');
            // Unique across all rows, including soft-deleted ones, so issued URLs are never reused.
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('theme')->default('default');
            $table->json('appearance')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_groups');
    }
};
