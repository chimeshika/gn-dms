<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('letter_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('my_ref_no')->nullable();
            $table->string('cabinet_app_no')->nullable();
            $table->date('cabinet_app_date')->nullable();
            $table->date('exam_date')->nullable();
            $table->date('probation_effective_date')->nullable();
            $table->date('training_complete_date')->nullable();
            $table->date('letter_date')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_batches');
    }
};