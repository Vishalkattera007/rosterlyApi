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
            $table->dropColumn(['total_work_time', 'total_break_time']);

            $table->integer('total_work_minutes')->default(0);
            $table->integer('total_break_minutes')->default(0);
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('roster_timesheets', function (Blueprint $table) {
            $table->dropColumn(['total_work_minutes', 'total_break_minutes']);

            $table->decimal('total_work_time', 8, 2)->default(0);
            $table->decimal('total_break_time', 8, 2)->default(0);
        });
    }
};
