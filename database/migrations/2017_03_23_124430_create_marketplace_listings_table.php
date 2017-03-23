<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketplaceListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('marketplace_id')->unsigned();
            $table->bigInteger('company_product_id')->unsigned();

            // Product identification on Marketplace.
            $table->string('uid')->nullable();
            $table->string('sku')->nullable();
            $table->string('url')->nullable();
            $table->string('ref_num')->nullable();

            // Various prices. (Always in INR).
            $table->bigInteger('price');
            $table->bigInteger('cost_price');
            $table->bigInteger('min_price');
            $table->bigInteger('max_price');

            // In market place currency format.
            $table->float('marketplace_price')->nullable();
            $table->string('marketplace_currency')->nullable();
            $table->timestamp('marketplace_price_updated_at')->nullable();

            $table->timestamps();

            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->foreign('company_product_id')->references('id')->on('company_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_listings');
    }
}
