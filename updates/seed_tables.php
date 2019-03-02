<?php namespace Responsiv\Pyrolancer\Updates;

use Backend\Models\UserGroup;
use October\Rain\Database\Updates\Seeder;

class SeedTables extends Seeder
{

    public function run()
    {
        $hasGroup = UserGroup::whereCode('managers')->count() > 0;

        if (!$hasGroup) {
            UserGroup::create([
                'name' => 'Managers',
                'code' => 'managers',
                'description' => 'Managers receive notifications about site activity.',
                'is_new_user_default' => true
            ]);
        }
    }

}
