<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFreelancersTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_freelancers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_freelancers');
    }

}
