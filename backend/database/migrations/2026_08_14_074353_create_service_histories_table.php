<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('ref_no')->nullable();
            $table->text('description')->nullable();
            $table->date('effective_date');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_histories');
    }
};