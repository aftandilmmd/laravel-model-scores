<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('model-scores.tables.task_groups', 'model_scores_task_groups'), function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('model-scores.tables.task_groups', 'model_scores_task_groups'));
    }
};
