<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectStatusLog extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_project_status_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('project_id')->nullable();
            $table->integer('old_status_id')->unsigned()->index();
            $table->integer('new_status_id')->unsigned()->index();
            $table->text('message_md')->nullable();
            $table->text('message_html')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::drop('ahoy_pyrolancer_project_status_log');
    }

}
