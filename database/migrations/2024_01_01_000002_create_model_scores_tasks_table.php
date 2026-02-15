<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $groupsTable = config('model-scores.tables.task_groups', 'model_scores_task_groups');

        Schema::create(config('model-scores.tables.tasks', 'model_scores_tasks'), function (Blueprint $table) use ($groupsTable) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained($groupsTable)->cascadeOnDelete();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('calculator');
            $table->string('type', 16)->default('static');
            $table->unsignedSmallInteger('max_points');
            $table->decimal('weight', 3, 2)->default(1.00);
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_checklist')->default(true);
            $table->string('route')->nullable();
            $table->string('profile', 32)->default('default');
            $table->unsignedSmallInteger('decay_days')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('profile');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('model-scores.tables.tasks', 'model_scores_tasks'));
    }
};
