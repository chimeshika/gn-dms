<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->string('district', 150)->nullable()->change();
            $table->string('ds_division', 150)->nullable()->change();
            $table->string('gn_division', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('officers', function (Blueprint $table) {
            $table->unsignedBigInteger('district')->nullable()->change();
            $table->unsignedBigInteger('ds_division')->nullable()->change();
            $table->unsignedBigInteger('gn_division')->nullable()->change();
        });
    }
};