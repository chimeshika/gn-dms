<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_batch_id')->nullable()->constrained('letter_batches')->nullOnDelete();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->string('ref_no', 100)->nullable();
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->longText('cc_to')->nullable();
            $table->foreignId('signatory_id')->nullable()->constrained('signatories')->nullOnDelete();
            $table->string('pdf_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['officer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
