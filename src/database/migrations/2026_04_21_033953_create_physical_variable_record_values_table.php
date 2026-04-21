<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_variable_record_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('physical_variable_record_id')
                ->constrained('physical_variable_records')
                ->cascadeOnDelete();

            $table->foreignId('physical_variable_id')
                ->constrained('physical_variables')
                ->restrictOnDelete();

            $table->decimal('value_numeric', 15, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();

            $table->timestamps();

            $table->unique(
                ['physical_variable_record_id', 'physical_variable_id'],
                'physical_record_variable_unique'
            );

            $table->index(['physical_variable_id', 'value_numeric']);
            $table->index(['physical_variable_id', 'value_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_variable_record_values');
    }
};