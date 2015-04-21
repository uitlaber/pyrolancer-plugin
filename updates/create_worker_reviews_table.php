<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateWorkerReviewsTable extends Migration
{

    public function up()
    {
        Schema::create('ahoy_pyrolancer_worker_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->integer('project_id')->unsigned()->index()->nullable();

            $table->boolean('is_visible')->default(false);
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->text('breakdown')->nullable();

            $table->boolean('client_is_visible')->default(false);
            $table->integer('client_user_id')->unsigned()->index()->nullable();
            $table->integer('client_rating')->nullable();
            $table->text('client_comment')->nullable();

            $table->string('invite_name')->nullable();
            $table->string('invite_location')->nullable();
            $table->string('invite_hash')->nullable()->index();
            $table->string('invite_email')->nullable();
            $table->string('invite_subject')->nullable();
            $table->text('invite_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahoy_pyrolancer_worker_reviews');
    }

}
