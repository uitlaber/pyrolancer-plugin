<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Attribute as AttributeModel;

class Jobs extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public $filterType;
    public $filterObject;

    public function componentDetails()
    {
        return [
            'name'        => 'Jobs',
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

    public function projects($options = null)
    {
        if ($options === null) {
            $options = $this->getFilterOptionsFromRequest();
        }

        return $this->lookupObject(__FUNCTION__, ProjectModel::listFrontEnd($options));
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

    public function activeFilters()
    {
        $selection = [
            'type' => null,
            'position' => null,
            'skills' => null,
            'skillCategories' => null,
        ];

        if ($requestSelection = $this->getFilterOptionsFromRequest()) {
            $selection = array_merge($selection, $requestSelection);
        }

        return $selection;
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

    public function onFilterJobs()
    {
        $options = post('Filter');
        $this->page['projects'] = $this->projects($options);
    }

    //
    // Filtering
    //

    protected function getFilterOptionsFromRequest()
    {
        if (!$this->filterType || !$this->filterObject) {
            return [];
        }

        $options = [];
        $options[$this->filterType] = (array) $this->filterObject->id;
        return $options;
    }

    protected function handleFilter()
    {
        $filterType = strtolower($this->param('filter'));
        $filterValue = $this->param('with');
        if (!$filterType || !$filterValue) {
            return;
        }

        $filterObject = null;
        switch ($filterType) {
            case 'skill':
                $filterObject = SkillModel::whereSlug($filterValue)->first();
                $filterType = 'skills';
                break;
            case 'position':
                $filterObject = AttributeModel::applyType(AttributeModel::POSITION_TYPE)
                    ->whereCode($filterValue)
                    ->first();
                $filterType = 'positions';
                break;
            case 'type':
                $filterObject = AttributeModel::applyType(AttributeModel::PROJECT_TYPE)
                    ->whereCode($filterValue)
                    ->first();
                $filterType = 'types';
                break;
        }

        if (!$filterObject) {
            return;
        }

        $this->filterType = $filterType;
        $this->filterObject = $filterObject;
    }

}