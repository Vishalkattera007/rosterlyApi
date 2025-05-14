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
        Schema::create('unavailability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->enum('unavailType', ['Days', 'RecuDays'])->nullable();
            $table->string('day')->nullable();
            $table->string('fromDate')->nullable();
            $table->string('toDate')->nullable();
            $table->string('startTime')->nullable();
            $table->string('endTime')->nullable();
            $table->unsignedBigInteger('notifyTo')->nullable();
            $table->bigInteger('unavailStatus')->default(0);
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign keys
            $table->foreign('userId')->references('id')->on('user_profiles')->onDelete('cascade');
            $table->foreign('notifyTo')->references('id')->on('user_profiles')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unavailability');
    }
};
