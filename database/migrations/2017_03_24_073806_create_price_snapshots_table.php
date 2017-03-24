<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePriceSnapshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('marketplace_listing_id')->unsigned();

            // Prices. (Always in INR).
            $table->bigInteger('selling_price');
            $table->bigInteger('cost_price');
            $table->bigInteger('min_price');
            $table->bigInteger('max_price');

            // In market place currency format.
            $table->float('marketplace_selling_price');
            $table->float('marketplace_cost_price');
            $table->float('marketplace_min_price');
            $table->float('marketplace_max_price');

            $table->timestamps();

            $table->foreign('marketplace_listing_id')
                  ->references('id')
                  ->on('marketplace_listings')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('price_snapshots');
    }
}
