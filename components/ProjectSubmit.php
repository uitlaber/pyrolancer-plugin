<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Flash;
use Redirect;
use Ahoy\Pyrolancer\Classes\ProjectData;
use Ahoy\Pyrolancer\Models\ProjectStatusLog;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
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
            // ProjectData::reset();
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
        if (!$user = Auth::getUser())
            $user = $this->handleAuth();

        if (!$project = ProjectData::submitProject($user))
            throw new ApplicationException('Unable to submit project, please contact support.');

        $user->is_client = true;
        $user->save();

        ProjectStatusLog::updateProjectStatus($project, ProjectModel::STATUS_PENDING);

        Flash::success('Your project has been submitted successfully!');

        if ($redirect = post('redirect'))
            return Redirect::to($this->pageUrl($redirect, ['slug' => $project->slug]));
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

}