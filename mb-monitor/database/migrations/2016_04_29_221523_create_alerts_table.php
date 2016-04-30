<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlertsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('alerts', function (Blueprint $table) {
      $table->increments('id');
      $table->string('stat_id')->unique();
      $table->string('stat_name')->nullable();
      $table->text('description')->nullable();
      $table->string('kind')->default("data");
      $table->string('time_window')->default("5m");
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
    Schema::drop('alerts');
  }
}
