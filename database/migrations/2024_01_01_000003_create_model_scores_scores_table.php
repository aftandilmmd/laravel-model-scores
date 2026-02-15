<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tasksTable = config('model-scores.tables.tasks', 'model_scores_tasks');

        Schema::create(config('model-scores.tables.scores', 'model_scores_scores'), function (Blueprint $table) use ($tasksTable) {
            $table->id();
            $table->morphs('scoreable');
            $table->foreignId('quality_task_id')->constrained($tasksTable)->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['scoreable_type', 'scoreable_id', 'quality_task_id'], 'model_scores_scores_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('model-scores.tables.scores', 'model_scores_scores'));
    }
};
