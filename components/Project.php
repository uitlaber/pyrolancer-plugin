<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Mail;
use Input;
use Redirect;
use Validator;
use Cms\Classes\Theme;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectMessage;
use Responsiv\Pyrolancer\Models\ProjectBid;
use ValidationException;
use ApplicationException;

class Project extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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
            'isPrimary' => [
                'title'       => 'Primary Project page',
                'description' => 'Use this page when generating a link to a project.',
                'type'        => 'checkbox',
                'default'     => false,
                'showExternalParam' => false
            ],
        ];
    }

    public function init()
    {
        if (($project = $this->project()) && $project->isOwner()) {
            $component = $this->addComponent('fileUploader', 'editFileUploader', ['deferredBinding' => false]);
            $component->bindModel('files', $project);
        }
    }

    public function onRun()
    {
        if ($this->property('isPrimary') && $project = $this->project()) {
            $this->page->meta_title = $this->page->meta_title
                ? str_replace('%s', $project->name, $this->page->meta_title)
                : $project->name;
        }
    }

    //
    // Object properties
    //

    public function project()
    {
        return $this->loadModel(new ProjectModel, function($query) {
            $query->with('messages.worker.logo');
        },
        function($project) {
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
        });
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

    public function onExtendExpiry()
    {
        if (!$project = $this->loadModel(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        if (!$project->isOwner()) {
            throw new ApplicationException('Action failed');
        }

        $project->markExtended();

        return Redirect::refresh();
    }

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

    public function onEditFiles()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $this->pageCycle();

        $this->page['project'] = $project;
    }

    public function onLoadBidAcceptForm()
    {
        $user = $this->lookupUser();
        $bid = $this->findProjectBid();

        if (!$bid->user) {
            throw new ApplicationException('Action failed');
        }

        $this->page['bid'] = $bid;
        $this->page['user'] = $user;
        $this->page['project'] = $bid->project;
    }

    public function onAcceptBid()
    {
        $user = $this->lookupUser();
        $bid = $this->findProjectBid();
        $project = $this->loadModelSecure(new ProjectModel);

        if (!$bid->user) {
            throw new ApplicationException('Action failed');
        }

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

        $params = [
            'site_name' => Theme::getActiveTheme()->site_name,
            'user' => $bid->user,
            'bid' => $bid,
            'project' => $project,
            'client' => $project->client
        ];
        Mail::sendTo($bid->user, 'responsiv.pyrolancer::mail.worker-bid-accepted', $params);

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

    public function onLoadResubmitForm()
    {
    }

    public function onResubmit()
    {
        if (!$project = $this->loadModelSecure(new ProjectModel)) {
            throw new ApplicationException('Action failed');
        }

        $reason = post('reason');

        $project->markSubmitted($reason);
        return Redirect::refresh();
    }

    //
    // Worker
    //

    public function onSubmitBid()
    {
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        if (!$user->is_worker) {
            throw new ApplicationException('Missing profile');
        }

        if (!$project->canBid()) {
            throw new ApplicationException('Action failed');
        }

        if ($project->hasBid()) {
            $this->page['bidUpdated'] = true;
        }
        else {
            $this->page['bidCreated'] = true;
        }

        $bid = ProjectBid::makeForProject($project);
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

        if (!$project->canBid()) {
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
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        if (!$bid = $project->hasChosenBid()) {
            throw new ApplicationException('Action failed');
        }

        $project->markDevelopment();

        return Redirect::refresh();
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

    protected function postMessage()
    {
        $user = $this->lookupUser();
        $project = $this->loadModel(new ProjectModel);

        $message = new ProjectMessage;
        $message->is_public = true;
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

    public function onPostMessage()
    {
        $this->postMessage();
    }

    public function onPostMessageReply()
    {
        $message = $this->postMessage();
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
            if ($values = post()) {
                $message->fill($values);
            }
            $message->save();
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
