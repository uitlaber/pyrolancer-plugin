<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFreelancersTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_workers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->string('business_name')->nullable();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('country_id')->unsigned()->nullable()->index();
            $table->integer('state_id')->unsigned()->nullable()->index();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();

            $table->integer('count_bids')->default(0);
            $table->dateTime('last_active_at')->index()->nullable();
            $table->timestamps();
        });

        Schema::create('ahoy_pyrolancer_workers_skills', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('worker_id')->unsigned();
            $table->integer('skill_id')->unsigned();
            $table->primary(['worker_id', 'skill_id'], 'worker_skill');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_workers');
        Schema::dropIfExists('ahoy_pyrolancer_workers_skills');
    }

}
