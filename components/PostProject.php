<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\ProjectCategory;
use Responsiv\Pyrolancer\Models\ProjectOption;
use Responsiv\Pyrolancer\Classes\ProjectData;

class PostProject extends ComponentBase
{

    public $project;

    public function componentDetails()
    {
        return [
            'name'        => 'Post Project',
            'description' => 'Used on the page where projects are created'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->controller->bindEvent('responsiv.uploader.sendFile', function($uploader){
            traceLog($uploader->apiCode);

            return [
                ['url' => 'http://daftspunk.com']
            ];
        });
    }

    public function onRun()
    {
        $this->project = ProjectData::getProjectObject();
    }

    //
    // AJAX
    //

    public function onStartProject()
    {
        ProjectData::startProject();
    }

    public function onPreviewProject()
    {
        ProjectData::previewProject();
    }

    public function onCompleteProject()
    {
        if (!$user = Auth::getUser())
            $user = $this->handleAuth();

        if (!$project = ProjectData::submitProject($user))
            throw new CmsException('Unable to submit project, please contact support.');

        Flash::success('Your project has been submitted successfully!');

        if ($redirect = post('redirect'))
            Redirect::to($this->pageUrl($redirect, ['slug' => $project->slug]));
    }

    public function onGetCategorySkillMap()
    {
        $result = [];
        $result['categories'] = $this->makeCategoryTree();
        $result['skills'] = Skill::lists('name', 'id');
        $result['categorySkillMap'] = $this->makeCategorySkillMap();
        return $result;
    }

    //
    // Lazy properties
    //

    public function projectTypes()
    {
        return ProjectOption::forType(ProjectOption::PROJECT_TYPE)->get();
    }

    public function positionTypes()
    {
        return ProjectOption::forType(ProjectOption::POSITION_TYPE)->get();
    }

    public function budgetTypes()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_TYPE)->get();
    }

    public function budgetFixedOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_FIXED)->get();
    }

    public function budgetHourlyOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_HOURLY)->get();
    }

    public function budgetTimeframeOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_TIMEFRAME)->get();
    }

    //
    // Authentication
    //

    protected function handleAuth()
    {
        $this->addComponent(
            'RainLab\User\Components\Account',
            'postProjectAccount',
            []
        );

        switch (post('auth_type', 'signin')) {
            case 'signin':
                $this->page->postProjectAccount->onSignin();
                break;
            case 'register':
                $this->page->postProjectAccount->onRegister();
                break;
        }

        return Auth::getUser();
    }

    //
    // Internals
    //

    protected function makeCategorySkillMap()
    {
        $idMap = ProjectCategory::skills()->newPivotStatement()->get();
        $result = [];

        foreach ($idMap as $map) {
            if (!isset($result[$map->category_id]))
                $result[$map->category_id] = [];

            $result[$map->category_id][] = $map->skill_id;
        }

        return $result;
    }

    protected function makeCategoryTree()
    {
        $buildResult = function($nodes) use (&$buildResult) {
            $result = [];

            foreach ($nodes as $node) {
                $item = [
                    'id' => $node->id,
                    'name' => $node->name
                ];

                $children = $node->getChildren();
                if ($children->count())
                    $item['children'] = $buildResult($children);

                $result[] = $item;
            }

            return $result;
        };

        $children = ProjectCategory::make()->getAllRoot();
        return $buildResult($children);
    }

}