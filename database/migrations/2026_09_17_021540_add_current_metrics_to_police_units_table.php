<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_units', function (Blueprint $table) {
            $table->unsignedInteger('officers_count')->nullable();
            $table->unsignedInteger('enlisted_count')->nullable();
            $table->unsignedInteger('served_population')->nullable();
            $table->date('personnel_reference_date')->nullable();
            $table->unsignedSmallInteger('population_reference_year')->nullable();
            $table->string('population_source')->nullable();
            $table->boolean('metrics_are_demo')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('police_units', function (Blueprint $table) {
            $table->dropColumn(['officers_count', 'enlisted_count', 'served_population', 'personnel_reference_date', 'population_reference_year', 'population_source', 'metrics_are_demo']);
        });
    }
};
