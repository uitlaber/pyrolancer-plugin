<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;

class Projects extends ComponentBase
{
    public $projects;

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

    public function onRun()
    {
        $this->projects = ProjectModel::all();
    }

}