<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Flash;
use Redirect;
use Ahoy\Pyrolancer\Classes\ProjectData;
use Ahoy\Pyrolancer\Models\ProjectStatusLog;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Client as ClientModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Settings as SettingsModel;
use Ahoy\Pyrolancer\Models\ProjectCategory;
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

    public function onRun()
    {
        if (!$this->property('editMode') && !get('edit')) {
            ProjectData::reset();
        }

        $this->project = $this->getProject();

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
        if (!$user = Auth::getUser()) {
            $user = $this->handleAuth();
        }

        if (!$project = ProjectData::submitProject($user)) {
            throw new ApplicationException('Unable to submit project, please contact support.');
        }

        /*
         * User is now a client
         */
        $user->is_client = true;
        $user->save();

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