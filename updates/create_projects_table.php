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
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->text('instructions')->nullable();
            $table->text('instructions_html')->nullable();
            $table->integer('duration')->default(30);
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('status_id')->unsigned()->index()->nullable();
            $table->integer('category_id')->unsigned()->index()->nullable();
            $table->integer('chosen_bid_id')->unsigned()->nullable();
            $table->integer('chosen_user_id')->unsigned()->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_sealed')->default(false);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_hidden')->default(false);

            // Stats
            $table->integer('count_bids')->default(0);
            $table->integer('count_applicants')->default(0);
            $table->decimal('average_bid', 15, 2)->default(0);

            // Location
            $table->boolean('is_remote')->default(false);
            $table->string('address')->nullable();
            $table->string('vicinity')->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('vicinity_id')->unsigned()->index()->nullable();
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
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('responsiv_pyrolancer_projects_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['project_id', 'skill_id'], 'project_skill');
        });

        Schema::create('responsiv_pyrolancer_projects_skill_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('project_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['project_id', 'category_id'], 'project_category');
        });

        Schema::create('responsiv_pyrolancer_projects_applicants', function($table)
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
        Schema::dropIfExists('responsiv_pyrolancer_projects');
        Schema::dropIfExists('responsiv_pyrolancer_projects_skills');
        Schema::dropIfExists('responsiv_pyrolancer_projects_skill_categories');
        Schema::dropIfExists('responsiv_pyrolancer_projects_applicants');
    }

}
