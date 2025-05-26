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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
        });
           Schema::table('user_profiles', function (Blueprint $table) {
        $table->string('location_id')->nullable()->change(); // allow comma-separated values
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('user_profiles', function (Blueprint $table) {
        $table->integer('location_id')->change(); // revert
        $table->foreign('location_id')->references('id')->on('locations'); // re-add foreign key if needed
    });
    }
};
