<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFavoritesTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_favorites', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('hash', 40)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('responsiv_pyrolancer_favorites_workers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('favorite_id')->unsigned();
            $table->integer('worker_id')->unsigned();
            $table->primary(['favorite_id', 'worker_id'], 'favorite_worker');
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_favorites');
        Schema::dropIfExists('responsiv_pyrolancer_favorites_workers');
    }

}
