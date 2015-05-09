<?php namespace Ahoy\Pyrolancer\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserAddProfileFields extends Migration
{

    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->string('phone', 100)->nullable();
            $table->string('mobile', 100)->nullable();
            $table->string('street_addr')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('country_id')->unsigned()->nullable()->index();
            $table->integer('state_id')->unsigned()->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('users', function($table)
        {
            $table->dropColumn('phone');
            $table->dropColumn('mobile');
            $table->dropColumn('street_addr');
            $table->dropColumn('city');
            $table->dropColumn('zip');
            $table->dropColumn('country_id');
            $table->dropColumn('state_id');
        });
    }

}
