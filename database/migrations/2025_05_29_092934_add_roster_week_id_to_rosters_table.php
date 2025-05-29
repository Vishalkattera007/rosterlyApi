<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roster', function (Blueprint $table) {
             $table->unsignedBigInteger('rosterWeekId')->after('user_id')->nullable();
             $table->foreign('rosterWeekId')->references('id')->on('roster_week')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roster', function (Blueprint $table) {
            // Drop foreign key first, then the column
            $table->dropForeign(['rosterWeekId']);
            $table->dropColumn('rosterWeekId');
        });
    }
};
