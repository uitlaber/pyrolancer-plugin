<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Request;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\Skill as SkillModel;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Vicinity as VicinityModel;
use Responsiv\Pyrolancer\Models\Attribute as AttributeModel;

class Jobs extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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
        $this->setActiveMenuItem();

        $this->page['paginationCurrentUrl'] = $this->paginationCurrentUrl();
    }

    public function projects($options = null)
    {
        if ($options === null) {
            $options = $this->getFilterOptionsFromRequest();
        }

        return $this->lookupObject(__FUNCTION__, function() use ($options) {
            return ProjectModel::applyVisible()->listFrontEnd($options);
        });
    }

    //
    // Helpers
    //

    public function makePageTitle($options)
    {
        if ($this->filterType && ($title = array_get($options, $this->filterType))) {
            return $title;
        }
        elseif ($this->filterObject && isset($this->filterObject->name)) {
            return str_replace('%s', $this->filterObject->name, array_get($options, 'filtered'));
        }

        return array_get($options, 'default');
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
            'types' => null,
            'position' => null,
            'skills' => null,
            'categories' => null,
            'countries' => null,
            'vicinity' => null,
            'sort' => null,
            'search' => null,
            'page' => null,
            'isRemote' => null
        ];

        if ($requestSelection = $this->getFilterOptionsFromRequest()) {
            $selection = array_merge($selection, $requestSelection);
        }

        return $selection;
    }

    public function paginationCurrentUrl()
    {
        $currentUrl = Request::url();
        $hasQuery = strpos($currentUrl, '?');
        if ($hasQuery !== false) {
            $currentUrl = substr($currentUrl, 0, $hasQuery);
        }

        $params = [];
        $params['page'] = '';

        return $currentUrl . '?' . http_build_query($params);
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
        $options['page'] = post('page', 1);
        $this->page['projects'] = $this->projects($options);
        $this->page['pageEventName'] = 'onFilterJobs';
        $this->page['pageFormElement'] = '#projectsBrowseForm';
        $this->page['updatePartialName'] = 'jobs/projects';
        $this->page['updateElement'] = '#partialJobsProjects';
        $this->page['onSuccess'] = "jobsAfterPaginate()";
    }

    public function onLoadSkillsPopup()
    {
        if (!$project = ProjectModel::find(post('id'))) {
            throw new ApplicationException('Action failed!');
        }

        $this->page['project'] = $project;
    }

    //
    // Filtering
    //

    protected function getFilterOptionsFromRequest()
    {
        $options = [];

        if ($searchQuery = input('search')) {
            $options['search'] = $searchQuery;
        }

        if ($pageNumber = input('page')) {
            $options['page'] = $pageNumber;
        }

        if ($this->filterType && $this->filterObject) {
            $options[$this->filterType] = (array) $this->filterObject->id;
        }

        if (
            $this->filterType == 'worker' &&
            !array_get($options, 'skills') &&
            ($user = Auth::getUser()) &&
            $user->is_worker
        ) {
            $options['skills'] = $user->worker->skills->lists('id');
        }

        if ($this->filterType == 'isRemote') {
            $options['isRemote'] = true;
        }

        return $options;
    }

    protected function handleFilter()
    {
        $filterType = strtolower($this->param('filter'));
        $filterValue = $this->param('with');

        // Special case
        if ($filterType == 'worker') {
            $this->filterType = 'worker';
            return;
        }
        elseif ($filterType == 'remote') {
            $this->filterType = 'isRemote';
            return;
        }

        if (!$filterType || !$filterValue) {
            return;
        }

        $filterObject = null;
        switch ($filterType) {
            case 'skill':
                $filterObject = SkillModel::whereSlug($filterValue)->first();
                $filterType = 'skills';
                break;
            case 'category':
                $filterObject = SkillCategory::whereSlug($filterValue)->first();
                $filterType = 'categories';
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
            case 'vicinity':
                $filterObject = VicinityModel::whereSlug($filterValue)->first();
                $filterType = 'vicinities';
                break;
        }

        if (!$filterObject) {
            return;
        }

        $this->filterType = $filterType;
        $this->filterObject = $filterObject;
    }

    protected function setActiveMenuItem()
    {
        if ($this->filterType == 'worker') {
            $this->page['activeMenuItem'] = 'worker-jobs';
        }
        else {
            $this->page['activeMenuItem'] = 'jobs';
        }
    }

}