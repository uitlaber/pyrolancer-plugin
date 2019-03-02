<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectStatusLog extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_project_status_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('project_id')->nullable();
            $table->integer('old_status_id')->unsigned()->index();
            $table->integer('new_status_id')->unsigned()->index();
            $table->text('data')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::drop('responsiv_pyrolancer_project_status_log');
    }

}
