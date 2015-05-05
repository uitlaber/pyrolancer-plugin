<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Attribute as AttributeModel;

class Projects extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public $filterType;
    public $filterObject;

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
        $this->handleFilter();
    }

    public function projects()
    {
        return $this->lookupObject(__FUNCTION__, ProjectModel::listFrontEnd());
    }

    //
    // Object properties
    //

    public function sortOrderOptions()
    {
        return ProjectModel::$allowedSortingOptions;
    }

    public function skillCategories()
    {
        return SkillCategory::applyVisible()->get();
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

    //
    // Filtering
    //

    protected function handleFilter()
    {
        $filterType = strtolower($this->param('filter'));
        $filterValue = $this->param('with');
        if (!$filterType || !$filterValue) return;

        $filterObject = null;
        switch ($filterType) {
            case 'skill':
                $filterObject = SkillModel::whereSlug($filterValue)->first();
                break;
            case 'position':
                $filterObject = AttributeModel::forType(AttributeModel::POSITION_TYPE)->whereCode($filterValue)->first();
                break;
            case 'type':
                $filterObject = AttributeModel::forType(AttributeModel::PROJECT_TYPE)->whereCode($filterValue)->first();
                break;
        }

        if (!$filterObject) return;

        $this->filterType = $filterType;
        $this->filterObject = $filterObject;
    }

}