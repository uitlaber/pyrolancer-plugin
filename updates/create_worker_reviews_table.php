<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateWorkerReviewsTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_worker_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('project_id')->unsigned()->index()->nullable();
            $table->boolean('is_visible')->default(false);
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('comment')->nullable();
            $table->text('breakdown')->nullable();
            $table->boolean('is_recommend')->default(true);
            $table->boolean('is_testimonial')->default(false);
            $table->boolean('client_is_visible')->default(false);
            $table->integer('client_user_id')->unsigned()->index()->nullable();
            $table->decimal('client_rating', 3, 2)->nullable();
            $table->text('client_comment')->nullable();
            $table->string('invite_name')->nullable();
            $table->string('invite_location')->nullable();
            $table->string('invite_hash')->nullable()->index();
            $table->string('invite_email')->nullable();
            $table->string('invite_subject')->nullable();
            $table->text('invite_message')->nullable();
            $table->dateTime('rating_at')->nullable();
            $table->dateTime('client_rating_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_worker_reviews');
    }

}
