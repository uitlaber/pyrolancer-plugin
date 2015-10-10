<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectsTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_projects', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->boolean('is_visible')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->text('instructions')->nullable();
            $table->text('instructions_html')->nullable();
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('status_id')->unsigned()->index()->nullable();
            $table->integer('category_id')->unsigned()->index()->nullable();
            $table->integer('chosen_bid_id')->unsigned()->nullable();

            // Stats
            $table->integer('count_bids')->default(0);
            $table->integer('count_applicants')->default(0);
            $table->decimal('average_bid', 15, 2)->default(0);

            // Location
            $table->boolean('is_remote')->default(false);
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('country_id')->unsigned()->nullable()->index();
            $table->integer('state_id')->unsigned()->nullable()->index();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('fallback_location')->nullable();

            // Project options
            $table->integer('project_type_id')->unsigned()->index()->nullable();
            $table->integer('position_type_id')->unsigned()->index()->nullable();
            $table->integer('budget_type_id')->unsigned()->index()->nullable();
            $table->integer('budget_fixed_id')->unsigned()->index()->nullable();
            $table->integer('budget_hourly_id')->unsigned()->index()->nullable();
            $table->integer('budget_timeframe_id')->unsigned()->index()->nullable();

            $table->dateTime('chosen_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ahoy_pyrolancer_projects_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['project_id', 'skill_id'], 'project_skill');
        });

        Schema::create('ahoy_pyrolancer_projects_skill_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['project_id', 'category_id'], 'project_category');
        });

        Schema::create('ahoy_pyrolancer_projects_applicants', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['project_id', 'user_id'], 'project_applicant');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_projects');
        Schema::dropIfExists('ahoy_pyrolancer_projects_skills');
        Schema::dropIfExists('ahoy_pyrolancer_projects_skill_categories');
        Schema::dropIfExists('ahoy_pyrolancer_projects_applicants');
    }

}
