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
        Schema::create('roster_week', function (Blueprint $table) {
            $table->id();
            
            // First define the columns
            $table->unsignedBigInteger('location_id');
            $table->date('week_start_date')->nullable();
            $table->date('week_end_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            
            // Then define the foreign keys
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('user_profiles')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roster_week');
    }
};
