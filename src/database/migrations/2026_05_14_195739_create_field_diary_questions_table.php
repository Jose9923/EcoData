<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_diary_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('field_diary_activity_id');

            $table->text('question_text');
            $table->string('question_type', 30)->default('textarea');

            $table->json('options')->nullable();

            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_required')->default(true);

            $table->timestamps();

            $table->index(['field_diary_activity_id', 'order'], 'field_q_activity_order_idx');

            $table->foreign('field_diary_activity_id', 'field_q_activity_fk')
                ->references('id')
                ->on('field_diary_activities')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_diary_questions');
    }
};