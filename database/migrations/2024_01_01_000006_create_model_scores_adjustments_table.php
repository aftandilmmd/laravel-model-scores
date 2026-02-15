<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('model-scores.tables.adjustments', 'model_scores_adjustments'), function (Blueprint $table) {
            $table->id();
            $table->morphs('scoreable');
            $table->string('profile', 32)->default('default');
            $table->smallInteger('points');
            $table->string('type', 16)->default('manual');
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['scoreable_type', 'scoreable_id', 'profile'], 'model_scores_adjustments_scoreable_profile');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('model-scores.tables.adjustments', 'model_scores_adjustments'));
    }
};
