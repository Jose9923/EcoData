<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_diary_submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('field_diary_activity_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();

            $table->string('status', 30)->default('borrador');

            $table->timestamp('submitted_at')->nullable();

            $table->text('teacher_feedback')->nullable();
            $table->decimal('score', 5, 2)->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->unique(['field_diary_activity_id', 'user_id'], 'field_sub_activity_user_unique');

            $table->index(['school_id', 'status'], 'field_sub_school_status_idx');
            $table->index(['grade_id', 'course_id'], 'field_sub_grade_course_idx');
            $table->index(['submitted_at'], 'field_sub_submitted_idx');

            $table->foreign('field_diary_activity_id', 'field_sub_activity_fk')
                ->references('id')
                ->on('field_diary_activities')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'field_sub_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('school_id', 'field_sub_school_fk')
                ->references('id')
                ->on('schools')
                ->cascadeOnDelete();

            $table->foreign('grade_id', 'field_sub_grade_fk')
                ->references('id')
                ->on('grades')
                ->nullOnDelete();

            $table->foreign('course_id', 'field_sub_course_fk')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();

            $table->foreign('reviewed_by', 'field_sub_reviewer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_diary_submissions');
    }
};