<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->index()->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('responsiv_pyrolancer_categories_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('skill_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['skill_id', 'category_id'], 'skill_cat');
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_categories');
        Schema::dropIfExists('responsiv_pyrolancer_categories_skills');
    }

}
