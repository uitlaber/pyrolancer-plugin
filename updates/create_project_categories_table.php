<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_project_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->index()->unique();
            $table->string('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        Schema::create('ahoy_pyrolancer_project_categories_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('category_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['category_id', 'skill_id'], 'cat_skill');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_project_categories');
        Schema::dropIfExists('ahoy_pyrolancer_project_categories_skills');
    }

}
