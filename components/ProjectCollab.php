<?php namespace Ahoy\Pyrolancer\Components;

use Flash;
use Redirect;
use Validator;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectMessage as ProjectMessageModel;
use ApplicationException;
use Exception;

class ProjectCollab extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Project collaboration',
            'description' => 'Workspace for project collaboration'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'Slug param name',
                'description' => 'The URL route parameter used for looking up the project by its slug. A hard coded slug can also be used.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    //
    // Object properties
    //

    public function project()
    {
        return $this->loadModel(new ProjectModel, function($query) {
            $query->with('private_messages.user.avatar');
            $query->with('private_messages.client');
            $query->with('private_messages.worker');
        },
        function($project){
            if ($project->project_type->code == 'advert') {
                return false;
            }

            if (!$project->hasFinished()) {
                return false;
            }

            if (!$project->isOwner() && !$project->hasChosenBid()) {
                return false;
            }
        });
    }

    public function otherUser()
    {
        if (!$project = $this->project()) {
            return null;
        }

        if ($project->isOwner()) {
            return $project->chosen_bid->user;
        }

        return $project->user;
    }

    public function onSubmitMessage()
    {
        try {
            if (!$project = $this->project()) {
                throw new ApplicationException('Project not found!');
            }

            $user = $this->lookupUser();
            $sessionKey = post('_session_key', uniqid('message', true));

            $message = new ProjectMessageModel;
            $message->is_public = false;
            $message->user = $user;
            $message->project = $project;
            $message->content = post('content');

            $this->setAttachmentsOnModel($message, $sessionKey);

            $message->save(null, $sessionKey);

            Flash::success('The message has been posted successfully.');

            return Redirect::refresh();
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    public function onLoadTerminateForm()
    {
    }

    public function onTerminate()
    {
        if (!$project = $this->project()) {
            throw new ApplicationException('Project not found!');
        }

        $user = $this->lookupUser();

        $reason = post('reason');
        $rules = ['reason' => 'required'];
        $data = ['reason' => $reason];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $project->markTerminated($reason, $user->id);
        return Redirect::refresh();
    }

    public function onComplete()
    {
        if (!$project = $this->project()) {
            throw new ApplicationException('Project not found!');
        }

        $user = $this->lookupUser();
        $project->markCompleted($user->id);

        return Redirect::refresh();
    }
}
