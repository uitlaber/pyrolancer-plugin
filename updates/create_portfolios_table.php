<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePortfoliosTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_portfolios', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_portfolios');
    }

}
