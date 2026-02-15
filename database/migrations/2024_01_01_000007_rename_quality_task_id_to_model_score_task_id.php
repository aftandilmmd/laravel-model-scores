<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $scoresTable = config('model-scores.tables.scores', 'model_scores_scores');
        $eventsTable = config('model-scores.tables.score_events', 'model_scores_score_events');
        $tasksTable = config('model-scores.tables.tasks', 'model_scores_tasks');

        Schema::table($scoresTable, function (Blueprint $table) use ($scoresTable, $tasksTable) {
            $table->dropForeign([$scoresTable === 'model_scores_scores' ? 'quality_task_id' : 'quality_task_id']);
            $table->dropUnique('model_scores_scores_unique');
            $table->renameColumn('quality_task_id', 'model_score_task_id');
        });

        Schema::table($scoresTable, function (Blueprint $table) use ($tasksTable) {
            $table->foreign('model_score_task_id')->references('id')->on($tasksTable)->cascadeOnDelete();
            $table->unique(['scoreable_type', 'scoreable_id', 'model_score_task_id'], 'model_scores_scores_unique');
        });

        Schema::table($eventsTable, function (Blueprint $table) {
            $table->dropForeign(['quality_task_id']);
            $table->renameColumn('quality_task_id', 'model_score_task_id');
        });

        Schema::table($eventsTable, function (Blueprint $table) use ($tasksTable) {
            $table->foreign('model_score_task_id')->references('id')->on($tasksTable)->nullOnDelete();
        });
    }

    public function down(): void
    {
        $scoresTable = config('model-scores.tables.scores', 'model_scores_scores');
        $eventsTable = config('model-scores.tables.score_events', 'model_scores_score_events');
        $tasksTable = config('model-scores.tables.tasks', 'model_scores_tasks');

        Schema::table($scoresTable, function (Blueprint $table) {
            $table->dropForeign(['model_score_task_id']);
            $table->dropUnique('model_scores_scores_unique');
            $table->renameColumn('model_score_task_id', 'quality_task_id');
        });

        Schema::table($scoresTable, function (Blueprint $table) use ($tasksTable) {
            $table->foreign('quality_task_id')->references('id')->on($tasksTable)->cascadeOnDelete();
            $table->unique(['scoreable_type', 'scoreable_id', 'quality_task_id'], 'model_scores_scores_unique');
        });

        Schema::table($eventsTable, function (Blueprint $table) {
            $table->dropForeign(['model_score_task_id']);
            $table->renameColumn('model_score_task_id', 'quality_task_id');
        });

        Schema::table($eventsTable, function (Blueprint $table) use ($tasksTable) {
            $table->foreign('quality_task_id')->references('id')->on($tasksTable)->nullOnDelete();
        });
    }
};
