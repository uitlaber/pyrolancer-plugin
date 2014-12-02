<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;

class Projects extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Projects',
            'description' => 'View a collection of projects'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

}