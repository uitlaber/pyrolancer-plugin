<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Input;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectMessage;
use Responsiv\Pyrolancer\Models\ProjectBid;
use ApplicationException;

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

    //
    // Bidding
    //

    public function onPostBid()
    {
        $user = $this->lookupUser();
        $project = $this->lookupModel(new ProjectModel);

        $bid = new ProjectBid;
        $bid->user = $user;
        $bid->project = $project;
        $bid->details = post('details');
        $bid->save();

        $this->page['bid'] = $bid;
        $this->page['project'] = $project;
        $this->page['success'] = true;
    }


    //
    // Messaging
    //

    public function onPostMessage()
    {
        $user = $this->lookupUser();
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

    public function onUpdateMessage()
    {
        if (!$message = $this->lookupModelSecure(new ProjectMessage))
            throw new ApplicationException('Action failed');

        /*
         * Supported modes: edit, view, delete, save
         */
        $mode = post('mode', 'edit');
        if ($mode == 'save') {

            // if (__canPostToThis__)
            //     throw new ApplicationException('Action failed');

            $message->save(post());

        }
        elseif ($mode == 'delete') {
            $message->delete();
        }

        $this->page['mode'] = $mode;
        $this->page['message'] = $message;
    }

}