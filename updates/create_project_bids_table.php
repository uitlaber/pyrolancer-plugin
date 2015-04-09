<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProjectBidsTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_project_bids', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('worker_id')->unsigned()->index()->nullable();
            $table->integer('project_id')->unsigned()->index()->nullable();
            $table->integer('status_id')->unsigned()->index()->nullable();
            $table->text('details')->nullable();
            $table->text('details_html')->nullable();
            $table->decimal('hourly_rate', 15, 2)->default(0);
            $table->integer('hourly_hours')->nullable();
            $table->decimal('fixed_rate', 15, 2)->default(0);
            $table->integer('deliver_days')->nullable();
            $table->boolean('is_nda_signed')->default(false);
            $table->index(['user_id', 'project_id'], 'user_project');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_project_bids');
    }

}
