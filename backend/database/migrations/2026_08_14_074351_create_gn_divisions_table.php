<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gn_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ds_division_id')->constrained('ds_divisions')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name_en', 150);
            $table->string('name_si', 200)->nullable();
            $table->string('name_ta', 200)->nullable();
            $table->timestamps();

            $table->index(['ds_division_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gn_divisions');
    }
};
