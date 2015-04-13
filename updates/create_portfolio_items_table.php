<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePortfolioItemsTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_portfolio_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('portfolio_id')->unsigned()->index()->nullable();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_portfolio_items');
    }

}
