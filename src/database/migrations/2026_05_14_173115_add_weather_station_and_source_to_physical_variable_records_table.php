<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physical_variable_records', function (Blueprint $table) {
            $table->foreignId('weather_station_id')
                ->nullable()
                ->after('course_id')
                ->constrained('weather_stations')
                ->nullOnDelete();

            $table->string('source_type', 30)
                ->default('manual')
                ->after('weather_station_id');

            $table->index(['weather_station_id', 'recorded_at'], 'phys_var_rec_station_date_idx');
            $table->index(['source_type', 'recorded_at'], 'phys_var_rec_source_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('physical_variable_records', function (Blueprint $table) {
            $table->dropIndex('phys_var_rec_station_date_idx');
            $table->dropIndex('phys_var_rec_source_date_idx');

            $table->dropConstrainedForeignId('weather_station_id');
            $table->dropColumn('source_type');
        });
    }
};