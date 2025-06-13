<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRosterAttendanceLogsTable extends Migration
{
    public function up()
    {
        Schema::create('roster_attendance_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id'); // From user_profile_models
            $table->unsignedBigInteger('roster_id')->nullable(); // From roster_models

            $table->enum('action_type', [
                'start',
                'break_start',
                'break_end',
                'end'
            ]);

            $table->timestamp('timestamp');
            $table->string('location')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps(); // This adds created_at and updated_at
        });

        // Foreign keys declared outside to avoid circular reference issues
        Schema::table('roster_attendance_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('user_profile_models')->onDelete('cascade');
            $table->foreign('roster_id')->references('id')->on('roster_models')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('roster_attendance_logs');
    }
}
