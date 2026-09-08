<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('designation');
            $table->string('officer_name');
            $table->string('digital_signature_path')->nullable();
            $table->unsignedBigInteger('ds_division_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};