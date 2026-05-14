<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('weather_station_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('physical_variable_id')
                ->nullable()
                ->constrained('physical_variables')
                ->nullOnDelete();

            $table->string('name');
            $table->string('code');

            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->string('measurement_unit')->nullable();
            $table->string('measurement_range')->nullable();
            $table->string('accuracy')->nullable();

            $table->date('installation_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();

            $table->string('status')->default('operativo');
            $table->text('observations')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['weather_station_id', 'code']);
            $table->index(['weather_station_id', 'is_active']);
            $table->index('physical_variable_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};