<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateUserEventLogTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_user_event_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('type', 30)->nullable();
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('other_user_id')->unsigned()->index()->nullable();
            $table->string('related_id')->index()->nullable();
            $table->string('related_type')->index()->nullable();
            $table->index(['user_id', 'created_at'], 'event_user_created');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('ahoy_pyrolancer_user_event_log');
    }

}
