<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('manager_id')->constrained('managers')->onDelete('cascade');
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['assigned', 'started', 'completed', 'expired'])->default('assigned');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_managers');
    }
};
