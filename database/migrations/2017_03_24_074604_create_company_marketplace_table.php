<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyMarketplaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_marketplace', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('company_id');
            $table->integer('marketplace_id');

            $table->text('credentials');

            $table->timestamps();

            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            $table->foreign('marketplace_id')
                  ->references('id')
                  ->on('marketplaces')
                  ->onDelete('cascade');

            $table->index(['company_id', 'marketplace_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_marketplace');
    }
}
