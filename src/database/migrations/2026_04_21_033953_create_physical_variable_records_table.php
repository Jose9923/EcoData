<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_variable_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('grade_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('course_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->dateTime('recorded_at');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'recorded_at']);
            $table->index(['grade_id', 'course_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_variable_records');
    }
};