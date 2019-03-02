<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePortfolioItemsTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_portfolio_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('portfolio_id')->unsigned()->index()->nullable();
            $table->integer('type_id')->unsigned()->index()->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->text('sample')->nullable();
            $table->text('sample_html')->nullable();
            $table->string('link_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_portfolio_items');
    }

}
