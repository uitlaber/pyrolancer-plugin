<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSkillCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_skill_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->index()->unique();
            $table->string('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_skill_categories');
    }

}
