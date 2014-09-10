<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectsTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_projects', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('status_id')->unsigned()->index()->nullable();
            $table->integer('category_id')->unsigned()->index()->nullable();

            // Location
            $table->boolean('is_remote')->default(false);
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('country_id')->unsigned()->nullable()->index();
            $table->integer('state_id')->unsigned()->nullable()->index();

            // Project options
            $table->integer('project_type_id')->unsigned()->index()->nullable();
            $table->integer('position_type_id')->unsigned()->index()->nullable();
            $table->integer('budget_type_id')->unsigned()->index()->nullable();
            $table->integer('budget_fixed_id')->unsigned()->index()->nullable();
            $table->integer('budget_hourly_id')->unsigned()->index()->nullable();
            $table->integer('budget_timeframe_id')->unsigned()->index()->nullable();

            $table->timestamps();
        });

        Schema::create('responsiv_pyrolancer_projects_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['project_id', 'skill_id'], 'project_skill');
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_projects');
        Schema::dropIfExists('responsiv_pyrolancer_projects_skills');
    }

}
