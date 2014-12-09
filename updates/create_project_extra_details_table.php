<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectExtraDetailsTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_project_extra_details', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('project_id')->unsigned()->index()->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_project_extra_details');
    }

}
