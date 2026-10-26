<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_variable_record_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_variable_record_id')->constrained(indexName: 'measurement_audit_record_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamp('created_at');
        });
        Schema::create('measurement_import_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_count');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_import_receipts');
        Schema::dropIfExists('physical_variable_record_audits');
    }
};
