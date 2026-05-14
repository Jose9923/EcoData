<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_event_acknowledgements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('environmental_event_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->unique(['environmental_event_id', 'user_id'], 'env_event_user_ack_unique');
            $table->index(['user_id', 'acknowledged_at'], 'env_event_ack_user_date_idx');

            $table->foreign('environmental_event_id', 'env_event_ack_event_fk')
                ->references('id')
                ->on('environmental_events')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'env_event_ack_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('environmental_event_acknowledgements', function (Blueprint $table) {
            $table->dropForeign('env_event_ack_event_fk');
            $table->dropForeign('env_event_ack_user_fk');
        });

        Schema::dropIfExists('environmental_event_acknowledgements');
    }
};