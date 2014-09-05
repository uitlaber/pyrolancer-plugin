<?php namespace Responsiv\Pyrolancer\Updates;

use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\Category;
use October\Rain\Database\Updates\Seeder;

class SeedAllTables extends Seeder
{

    public function run()
    {
        $skills = [
            'HTML',
            'CSS',
            'JavaScript',
            'PHP',
            'Perl',
            'Java',
            'Ruby',
            'Cold Fusion',
            'C Programming',
            'C++ Programming',
            'C# Programming',
            'Objective C',
            'Adobe Flash',
            'Photoshop',
            'Illustrator',
            'Logo Design',
            'Website Design',
            'Graphic Design',
            'System Admin',
            'Data Entry',
            'Copywriting',
            'Translation',
            'Proofreading',
            'SEO',
            'Photography',
            'Git',
            'Joomla',
            'Drupal',
            'WordPress',
            'OctoberCMS',
            'MODx',
            'Magento',
            'LemonStand',
            'Yii',
            'CakePHP',
            'CodeIgniter',
            'Laravel',
            'Ruby on Rails',
        ];

        foreach ($skills as $skill) {
            Skill::create(['name' => $skill]);
        }
    }

}
