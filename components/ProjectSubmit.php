<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Flash;
use Redirect;
use Responsiv\Pyrolancer\Classes\ProjectData;
use Responsiv\Pyrolancer\Models\ProjectStatusLog;
use Responsiv\Pyrolancer\Models\Skill as SkillModel;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Client as ClientModel;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\Settings as SettingsModel;
use Responsiv\Pyrolancer\Models\ProjectCategory;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use ApplicationException;

class ProjectSubmit extends ComponentBase
{

    public $project;

    public function componentDetails()
    {
        return [
            'name'        => 'Submit Project',
            'description' => 'Used on the page where projects are created'
        ];
    }

    public function defineProperties()
    {
        return [
            'editMode' => [
                'title'       => 'Edit mode',
                'description' => 'Check this for secondary pages where the request data is loaded from the session.',
                'type'        => 'checkbox',
                'default'     => 1
            ],
            'redirect' => [
                'title'       => 'Redirection page',
                'description' => 'If no request object is found and user is trying to edit.',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return [''=>'- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        if (!$this->property('editMode') && !get('edit')) {
            ProjectData::reset();
        }

        $this->project = $project = $this->getProject();

        if ($project) {
            $this->page['allowProjectFeatured'] = SettingsModel::get('allow_project_featured', true);
            $this->page['allowProjectPrivate'] = SettingsModel::get('allow_project_private', true);
            $this->page['allowProjectUrgent'] = SettingsModel::get('allow_project_urgent', true);
            $this->page['allowProjectSealed'] = (
                SettingsModel::get('allow_project_sealed', true) &&
                $project->project_type && $project->project_type->code == 'auction'
            );
        }

        /*
         * Redirect away when editing a request that does not exist
         */
        if (!ProjectData::exists() && $this->property('editMode')) {
            $redirectUrl = $this->pageUrl($this->property('redirect'));
            return Redirect::to($redirectUrl);
        }
    }

    public function getProject()
    {
        return ProjectData::getProjectObject();
    }

    public function getSessionKey()
    {
        return ProjectData::getSessionKey();
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
        ProjectData::completeProject();

        if (!$user = Auth::getUser()) {
            $user = $this->handleAuth();
        }

        if (!$project = ProjectData::submitProject($user)) {
            throw new ApplicationException('Unable to submit project, please contact support.');
        }

        /*
         * User is now a client
         */
        if (!$user->is_client) {
            $user->is_client = true;
            $user->save();
        }

        $client = ClientModel::getFromUser($user);
        $client->count_projects++;
        $client->save();

        /*
         * Set project status
         */
        if (SettingsModel::get('auto_approve_projects', false)) {
            $project->markApproved();
        }
        else {
            $project->markSubmitted();
        }

        Flash::success('Your project has been submitted successfully!');

        if ($redirect = post('redirect')) {
            return Redirect::to($this->pageUrl($redirect, ['slug' => $project->slug]));
        }
    }

    //
    // Authentication
    //

    protected function handleAuth()
    {
        $this->addComponent(
            'RainLab\User\Components\Account',
            'projectSubmitAccount',
            []
        );

        switch (post('auth_type', 'signin')) {
            case 'signin':
                $this->page->projectSubmitAccount->onSignin();
                break;
            case 'register':
                $this->page->projectSubmitAccount->onRegister();
                break;
        }

        if (!$user = Auth::getUser())
            throw new ApplicationException('Unable to authenticate, please contact support.');

        return $user;
    }

    //
    // Skills
    //

    public function onGetCategorySkillMap()
    {
        $result = [];
        $result['categories'] = $this->makeCategoryTree();
        $result['skills'] = SkillModel::lists('name', 'id');
        $result['categorySkillMap'] = $this->makeCategorySkillMap();
        return $result;
    }

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