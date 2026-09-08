<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['district', 'ds_division', 'gn_division'] as $name) {
            $exists = Schema::hasColumn('officers', $name);
            Schema::table('officers', function (Blueprint $table) use ($name, $exists) {
                $column = $table->string($name, 150)->nullable();
                if ($exists) { $column->change(); }
            });
        }
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
