<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropLoghyHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('loghy_history');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('loghy_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('type');

            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();

            $table->timestamps();
        });
    }
}
