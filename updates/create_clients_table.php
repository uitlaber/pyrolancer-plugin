<?php namespace Responsiv\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateClientsTable extends Migration
{

    public function up()
    {
        Schema::create('responsiv_pyrolancer_clients', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->string('display_name')->nullable();
            $table->integer('count_projects_active')->default(0);
            $table->integer('count_projects')->default(0);
            $table->integer('count_ratings')->default(0);
            $table->decimal('rating_overall', 3, 2)->default(0)->nullable();
            $table->dateTime('last_digest_at')->index()->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('responsiv_pyrolancer_clients');
    }

}
