<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_diary_answers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('field_diary_submission_id');
            $table->unsignedBigInteger('field_diary_question_id');

            $table->longText('answer_text')->nullable();
            $table->string('answer_file_path')->nullable();

            $table->timestamps();

            $table->unique(['field_diary_submission_id', 'field_diary_question_id'], 'field_ans_sub_question_unique');

            $table->foreign('field_diary_submission_id', 'field_ans_submission_fk')
                ->references('id')
                ->on('field_diary_submissions')
                ->cascadeOnDelete();

            $table->foreign('field_diary_question_id', 'field_ans_question_fk')
                ->references('id')
                ->on('field_diary_questions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_diary_answers');
    }
};