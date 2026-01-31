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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams');
            $table->integer('order');
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'true_false', 'descriptive']);
            $table->json('options')->nullable();
            $table->text('correct_answer');
            $table->integer('score');
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->string('category');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
