<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tasksTable = config('model-scores.tables.tasks', 'model_scores_tasks');

        Schema::create(config('model-scores.tables.score_events', 'model_scores_score_events'), function (Blueprint $table) use ($tasksTable) {
            $table->id();
            $table->morphs('scoreable');
            $table->string('profile', 32)->default('default');
            $table->string('event_type', 32);
            $table->foreignId('quality_task_id')->nullable()->constrained($tasksTable)->nullOnDelete();
            $table->smallInteger('old_score')->nullable();
            $table->smallInteger('new_score')->nullable();
            $table->unsignedSmallInteger('old_total')->nullable();
            $table->unsignedSmallInteger('new_total')->nullable();
            $table->unsignedBigInteger('caused_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['scoreable_type', 'scoreable_id', 'profile'], 'model_scores_events_scoreable_profile');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('model-scores.tables.score_events', 'model_scores_score_events'));
    }
};
