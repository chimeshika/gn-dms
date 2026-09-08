<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->string('spouse_name', 150)->nullable()->after('address_line3');
            $table->unsignedSmallInteger('dependants_count')->nullable()->after('spouse_name');
            $table->string('emergency_contact_name', 150)->nullable()->after('dependants_count');
            $table->string('emergency_contact_relationship', 100)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_relationship');
        });
    }

    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->dropColumn([
                'spouse_name', 'dependants_count', 'emergency_contact_name',
                'emergency_contact_relationship', 'emergency_contact_phone',
            ]);
        });
    }
};