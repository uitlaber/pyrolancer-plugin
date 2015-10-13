<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectMessagesTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_project_messages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('project_id')->unsigned()->index()->nullable();
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->text('content')->nullable();
            $table->text('content_html')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_project_messages');
    }

}
