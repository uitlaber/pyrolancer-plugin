<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\SkillCategory;

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

    //
    // Object properties
    //

    public function skillCategories()
    {
        return SkillCategory::isVisible()->get();
    }

    //
    // AJAX
    //

    public function onGetSkills()
    {
        $result = [];
        $result['skills'] = SkillModel::lists('name', 'id');
        return $result;
    }

}