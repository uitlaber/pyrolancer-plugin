<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectMessage;
use Cms\Classes\CmsException;

class Project extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public $project;

    public function componentDetails()
    {
        return [
            'name'        => 'Project Component',
            'description' => 'No description provided yet...'
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

    public function onRun()
    {
        $this->project = $this->lookupModel(new ProjectModel, function($query) {
            // $query->with('quotes');
        });
    }

    public function onPostMessage()
    {
        if (!$user = Auth::getUser())
            throw new CmsException('You must be logged in');

        $project = $this->lookupModel(new ProjectModel);

        $message = new ProjectMessage;
        $message->user = $user;
        $message->project = $project;
        $message->content = post('content');

        if ($parentId = post('parent_id'))
            $message->parent_id = $parentId;

        $message->save();

        return $this->page['message'] = $message;
    }

    public function onPostMessageReply()
    {
        $message = $this->onPostMessage();
        return $this->page['message'] = $message->getParent();
    }

}