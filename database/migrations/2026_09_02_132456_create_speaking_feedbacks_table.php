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
        Schema::create('speaking_feedbacks', function (Blueprint $table) {
            $table->id();
            // Unique: enforces exactly one feedback per attempt at the DB level.
            $table->foreignId('attempt_id')->unique()->constrained('speaking_attempts')->cascadeOnDelete();
            $table->decimal('band_score', 2, 1);
            $table->json('strengths');
            $table->json('areas_to_improve');
            // Raw Gemini payload kept for debugging/audit.
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_feedbacks');
    }
};
