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
        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->string('assessment_status')->default('not_started'); // not_started, incomplete, completed
            $table->string('exam_status')->default('not_started'); // not_started, in_progress, completed
            $table->boolean('can_view_results')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('managers');
    }
};
