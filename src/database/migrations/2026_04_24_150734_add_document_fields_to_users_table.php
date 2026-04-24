<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_type', 20)->nullable()->after('email');
            $table->string('document_number', 50)->nullable()->after('document_type')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->dropColumn(['document_type', 'document_number']);
        });
    }
};