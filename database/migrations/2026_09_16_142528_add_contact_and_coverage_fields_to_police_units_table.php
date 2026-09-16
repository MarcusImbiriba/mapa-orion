<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_units', function (Blueprint $table) {
            $table->string('commander')->nullable();
            $table->string('deputy_commander')->nullable();
            $table->text('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('served_localities')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('police_units', function (Blueprint $table) {
            $table->dropColumn(['commander', 'deputy_commander', 'phone', 'email', 'served_localities']);
        });
    }
};
