<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_diary_activities', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('weather_station_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('entry_type', 30)->default('observacion');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'field_act_school_active_idx');
            $table->index(['grade_id', 'course_id'], 'field_act_grade_course_idx');
            $table->index(['starts_at', 'ends_at'], 'field_act_dates_idx');

            $table->foreign('school_id', 'field_act_school_fk')
                ->references('id')
                ->on('schools')
                ->cascadeOnDelete();

            $table->foreign('grade_id', 'field_act_grade_fk')
                ->references('id')
                ->on('grades')
                ->nullOnDelete();

            $table->foreign('course_id', 'field_act_course_fk')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();

            $table->foreign('weather_station_id', 'field_act_station_fk')
                ->references('id')
                ->on('weather_stations')
                ->nullOnDelete();

            $table->foreign('created_by', 'field_act_creator_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_diary_activities');
    }
};