<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_project_categories', function($table)
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

        Schema::create('responsiv_pyrolancer_project_categories_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('category_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['category_id', 'skill_id'], 'cat_skill');
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_project_categories');
        Schema::dropIfExists('responsiv_pyrolancer_project_categories_skills');
    }

}
