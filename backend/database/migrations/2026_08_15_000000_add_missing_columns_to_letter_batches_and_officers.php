<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── letter_batches: add document_type, ds/district FK, template ──
        Schema::table('letter_batches', function (Blueprint $table) {
            $table->string('document_type', 40)->nullable()->after('name');
            $table->foreignId('ds_division_id')->nullable()->after('document_type')->constrained('ds_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('ds_division_id')->constrained('districts')->nullOnDelete();
            $table->longText('content_template')->nullable()->after('letter_date');
        });

        // ── officers: add missing columns expected by model fills/casts ──
        Schema::table('officers', function (Blueprint $table) {
            $table->string('confirmation_status', 30)->nullable()->after('current_gn_division_id');
            $table->date('confirmation_date')->nullable()->after('confirmation_status');
            $table->date('appointment_date')->nullable()->after('confirmation_date');
            $table->string('service_status', 30)->nullable()->default('appointed')->after('appointment_date');
        });
    }

    public function down(): void
    {
        Schema::table('letter_batches', function (Blueprint $table) {
            $table->dropForeign(['ds_division_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn(['document_type', 'ds_division_id', 'district_id', 'content_template']);
        });

        Schema::table('officers', function (Blueprint $table) {
            $table->dropColumn(['confirmation_status', 'confirmation_date', 'appointment_date', 'service_status']);
        });
    }
};
