<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->string('designation', 150)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('mobile_phone', 30)->nullable();
            $table->json('dependents')->nullable();
        });
        Schema::table('documents', fn (Blueprint $table) => $table->string('issuing_authority')->nullable());
    }
    public function down(): void
    {
        Schema::table('officers', fn (Blueprint $table) => $table->dropColumn(['designation', 'contact_email', 'mobile_phone', 'dependents']));
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn('issuing_authority'));
    }
};
