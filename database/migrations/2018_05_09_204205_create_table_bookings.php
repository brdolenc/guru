<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('bookings', function (Blueprint $table) {
            $table->integer('id')->unique();
            $table->integer('client_id')->nullable()->default(0);
            $table->integer('project_id')->nullable()->default(0);
            $table->integer('resource_id')->nullable()->default(0);
            $table->binary('data');
            $table->enum('status', ['NEW', 'FINALIZED'])->default('NEW');
            $table->dateTime('status_updated_at')->nullable()->useCurrent();
            $table->enum('timer', ['ON', 'OFF'])->default('OFF');
            $table->dateTime('start_timer')->nullable()->useCurrent();
            $table->dateTime('end_timer')->nullable()->useCurrent();
            $table->integer('timer_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('bookings');
    }
}
