<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Input;
use Redirect;
use Validator;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectMessage;
use Ahoy\Pyrolancer\Models\ProjectBid;
use Ahoy\Pyrolancer\Models\ProjectExtraDetail;
use ValidationException;
use ApplicationException;

class Project extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

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

    //
    // Object properties
    //

    public function project()
    {
        $project = $this->loadModel(new ProjectModel, function($query) {
            $query->with('messages.worker.logo');
        });

        if ($project->project_type->code == 'advert') {
            $project->load('applicants.avatar');
            $project->load('applicants.worker');
        }
        else {
            $project->load('bids.user.avatar');
            $project->load('bids.worker.logo');
        }

        $project->messages->each(function($message) use ($project) {
            $message->setRelation('project', $project);
            if ($message->isProjectOwner()) {
                $message->setRelation('client', $project->client);
            }
        });

        $project->setRelation('messages', $project->messages->toNested());

        return $project;
    }

    //
    // Generic
    //

    public function onLoadRevisionHistoryForm()
    {
        $project = $this->loadModel(new ProjectModel);
        $this->page['project'] = $project;
        $this->page['revisionHistory'] = $project->revision_history;
    }

    //
    // Client
    //

    public function onEditDescription()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $project->description = post('description');
        $project->save();

        $this->page['project'] = $project;
    }

    public function onEditInstructions()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $project->instructions = post('instructions');
        $project->save();

        $this->page['project'] = $project;
    }

    public function onLoadBidAcceptForm()
    {
        $user = $this->lookupUser();
        $bid = $this->findProjectBid();

        $this->page['bid'] = $bid;
        $this->page['user'] = $user;
        $this->page['project'] = $bid->project;
    }

    public function onAcceptBid()
    {
        $user = $this->lookupUser();
        $bid = $this->findProjectBid();
        $project = $this->loadModelSecure(new ProjectModel);

        $rules = [
            'name' => 'required',
            'surname' => 'required',
            'phone' => 'required',
            'street_addr' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
        ];

        $data = array_only(post(), [
            'name',
            'surname',
            'phone',
            'mobile',
            'street_addr',
            'city',
            'zip',
            'country_id',
            'state_id'
        ]);

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $project->markAccepted($bid);
        $user->fill($data);
        $user->save();

        return Redirect::refresh();
    }

    public function onRetractOffer()
    {
        $project = $this->loadModelSecure(new ProjectModel);
        $project->markAccepted(null);

        return Redirect::refresh();
    }

    public function onToggleBid()
    {
        $bid = $this->findProjectBid();

        $bid->is_hidden = !$bid->is_hidden;
        $bid->save();

        $this->page['project'] = $bid->project;
    }

    public function onCancel()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $project->markCancelled();
        return Redirect::refresh();
    }

    public function onResubmit()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $project->markSubmitted();
        return Redirect::refresh();
    }

    //
    // Worker
    //

    public function onSubmitBid()
    {
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        if ($project->hasChosenBid()) {
            throw new ApplicationException('Action failed');
        }

        if (!$bid = $project->hasBid()) {
            $this->page['bidCreated'] = true;
            $bid = ProjectBid::makeForProject($project);
        }
        else {
            $this->page['bidUpdated'] = true;
        }

        $bid->fill((array) post('Bid'));
        $bid->save();

        $project->reloadRelations();
        $project->rebuildStats();
        $project->save();

        $this->page['bid'] = $bid;
        $this->page['bids'] = $project->bids;
        $this->page['project'] = $project;
    }

    public function onRemoveBid()
    {
        $project = $this->loadModel(new ProjectModel);

        if ($project->hasChosenBid()) {
            throw new ApplicationException('Action failed');
        }

        if ($bid = $project->hasBid()) {
            $bid->delete();
        }

        $project->rebuildStats();
        $project->save();

        return Redirect::refresh();
    }

    public function onLoadOfferDeclineForm()
    {
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        if (!$bid = $project->hasChosenBid()) {
            throw new ApplicationException('Action failed');
        }

        $this->page['bid'] = $bid;
        $this->page['user'] = $user;
        $this->page['project'] = $project;
    }

    public function onDeclineOffer()
    {
        $reason = post('reason');
        $rules = ['reason' => 'required'];
        $data = ['reason' => $reason];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        if (!$bid = $project->hasChosenBid()) {
            throw new ApplicationException('Action failed');
        }

        $project->markDeclined($reason);

        $bid->is_hidden = true;
        $bid->save();

        return Redirect::refresh();
    }

    public function onAcceptOffer()
    {

    }

    public function onAdvertApply()
    {
        $user = $this->lookupUser();

        if (!$project = $this->loadModel(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        if (!$project->hasApplicant($user)) {
            $project->applicants()->add($user);
        }

        $project->reloadRelations();
        $project->rebuildStats();
        $project->save();

        $this->page['project'] = $project;
    }

    //
    // Messaging
    //

    public function onPostMessage()
    {
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        $message = new ProjectMessage;
        $message->user = $user;
        $message->project = $project;
        $message->content = post('content');

        if ($parentId = post('parent_id')) {
            $message->parent_id = $parentId;
        }

        $message->save();

        $this->page['project'] = $project;
        $this->page['message'] = $message;

        return $message;
    }

    public function onPostMessageReply()
    {
        $message = $this->onPostMessage();
        $this->page['message'] = $message->parent ?: $message;

        return ['messageId' => $message->id];
    }

    public function onUpdateMessage()
    {
        if (!$message = $this->lookupModelSecure(new ProjectMessage)) {
            throw new ApplicationException('Action failed');
        }

        /*
         * Supported modes: edit, view, delete, save
         */
        $mode = post('mode', 'edit');
        if ($mode == 'save') {
            $message->save(post());
        }
        elseif ($mode == 'delete') {
            $message->delete();
        }

        $this->page['mode'] = $mode;
        $this->page['message'] = $message;
    }

    //
    // Helpers
    //

    protected function findProjectBid()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        if (!$bid = $this->lookupModel(new ProjectBid)) {
            throw new ApplicationException('Bid not found');
        }

        if ($bid->project_id != $project->id) {
            throw new ApplicationException('Permission denied');
        }

        $bid->setRelation('project', $project);

        return $bid;
    }

}