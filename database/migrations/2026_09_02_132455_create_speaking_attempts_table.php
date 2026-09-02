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
        Schema::create('speaking_attempts', function (Blueprint $table) {
            $table->id();
            // Nullable so auth remains optional (FR-7); attempt survives user deletion.
            $table->foreignId('user_id')->nullable()->nullOnDelete();
            $table->foreignId('question_id')->constrained('speaking_questions')->cascadeOnDelete();
            $table->text('answer_text');
            $table->enum('status', ['pending', 'evaluated', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('question_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_attempts');
    }
};
