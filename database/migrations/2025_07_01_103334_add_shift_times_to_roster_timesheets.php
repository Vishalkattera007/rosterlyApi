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
         Schema::table('roster_timesheets', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->integer('break_minutes')->default(0)->after('end_time'); // in minutes
            $table->integer('shift_minutes')->default(0)->after('break_minutes'); // in minutes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roster_timesheets', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'break_minutes', 'shift_minutes']);
        });
    }
};
