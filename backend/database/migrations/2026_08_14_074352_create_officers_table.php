<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nic_no', 20)->unique();
            $table->string('full_name_en', 150);
            $table->string('full_name_si', 150)->nullable();
            $table->string('full_name_ta', 150)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('medium', 20)->nullable();
            $table->string('address_line1', 150)->nullable();
            $table->string('address_line2', 150)->nullable();
            $table->string('address_line3', 150)->nullable();
            $table->date('first_appointment_date')->nullable();
            $table->string('current_grade', 30)->nullable();
            $table->foreignId('current_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('current_ds_division_id')->nullable()->constrained('ds_divisions')->nullOnDelete();
            $table->foreignId('current_gn_division_id')->nullable()->constrained('gn_divisions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officers');
    }
};
