<?php namespace Responsiv\Pyrolancer\Components;

use Mail;
use Flash;
use Redirect;
use Validator;
use Carbon\Carbon;
use Cms\Classes\Theme;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectMessage as ProjectMessageModel;
use Responsiv\Pyrolancer\Models\WorkerReview;
use ApplicationException;
use Exception;

class Collab extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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
    // General properties
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
            return $project->chosen_user;
        }

        return $project->user;
    }

    public function messages()
    {
        if (!$project = $this->project()) {
            return null;
        }

        return $project->private_messages()->paginate(20);
    }

    //
    // Update quote
    //

    public function onLoadUpdateQuoteForm()
    {
        if (
            (!$project = $this->project()) ||
            (!$bid = $project->chosen_bid) ||
            (!$otherUser = $this->otherUser()) ||
            !$bid->isOwner()
        ) {
            throw new ApplicationException('Action failed!');
        }

        $this->page['otherUser'] = $otherUser;
        $this->page['bid'] = $bid;
    }

    public function onUpdateQuote()
    {
        if (
            (!$project = $this->project()) ||
            (!$bid = $project->chosen_bid) ||
            (!$otherUser = $this->otherUser()) ||
            !$bid->isOwner()
        ) {
            throw new ApplicationException('Action failed!');
        }

        $bid->fill((array) post('Bid'));
        $bid->save();

        return Redirect::refresh();
    }

    //
    // Closing
    //

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

        /*
         * Send notification
         */
        $project->resetUrlComponent('collab');
        if ($otherUser = $this->otherUser()) {
            $params = [
                'site_name' => Theme::getActiveTheme()->site_name,
                'project' => $project,
                'user' => $otherUser,
                'otherUser' => $user,
                'reason' => $reason,
            ];
            Mail::sendTo($otherUser, 'responsiv.pyrolancer::mail.collab-terminated', $params);
        }

        return Redirect::refresh();
    }

    public function onComplete()
    {
        if (!$project = $this->project()) {
            throw new ApplicationException('Project not found!');
        }

        $user = $this->lookupUser();
        $project->markCompleted($user->id);

        /*
         * Send notification
         */
        $project->resetUrlComponent('collab');
        if ($otherUser = $this->otherUser()) {
            $params = [
                'site_name' => Theme::getActiveTheme()->site_name,
                'project' => $project,
                'user' => $otherUser,
                'otherUser' => $user,
            ];
            Mail::sendTo($otherUser, 'responsiv.pyrolancer::mail.collab-complete', $params);
        }

        return Redirect::refresh();
    }

    //
    // Reviews
    //

    public function review()
    {
        if (!$project = $this->project()) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, WorkerReview::getForProject($project));
    }

    /*
     * Review submitted by user
     */
    public function myReview()
    {
        return $this->otherReview(true);
    }

    /**
     * Review about the user
     */
    public function otherReview($isOwner = false)
    {
        if (!$project = $this->project()) {
            return null;
        }

        $review = $this->review();
        $forWorker = $isOwner ? $project->isOwner() : !$project->isOwner();

        if (
            ($forWorker && !$review->is_visible) ||
            (!$forWorker && !$review->client_is_visible)
        ) {
            return null;
        }

        $reviewObj = [];
        $reviewObj['breakdown'] = $review->breakdown;

        if ($forWorker) {
            $reviewObj['name'] = $project->user->name;
            $reviewObj['comment'] = $review->comment;
            $reviewObj['rating'] = $review->rating;
            $reviewObj['rating_at'] = $review->rating_at;
            $reviewObj['is_recommend'] = $review->is_recommend;
        }
        else {
            $reviewObj['name'] = $project->chosen_bid->worker->business_name;
            $reviewObj['comment'] = $review->client_comment;
            $reviewObj['rating'] = $review->client_rating;
            $reviewObj['rating_at'] = $review->client_rating_at;
            $reviewObj['is_recommend'] = true;
        }

        return $reviewObj;
    }

    public function canReview()
    {
        if (!$project = $this->project()) {
            return null;
        }

        if (!$this->otherUser()) {
            return false;
        }

        return $project->status->code != $project::STATUS_DEVELOPMENT;
    }

    public function canUpdateReview()
    {
        return $this->canReview() && Carbon::now()->lt($this->reviewLockedAt());
    }

    public function reviewLockedAt()
    {
        $date = Carbon::now();

        if ($project = $this->project()) {
            $date = $project->closed_at ?: $date;
        }

        return $date->addDays(14);
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

            $project->resetUrlComponent('collab');

            /*
             * Notify other user
             */
            if ($otherUser = $this->otherUser()) {
                $params = [
                    'site_name' => Theme::getActiveTheme()->site_name,
                    'project' => $project,
                    'user' => $otherUser,
                    'otherUser' => $user,
                    'collabMessage' => $message,
                ];
                Mail::sendTo($otherUser, 'responsiv.pyrolancer::mail.collab-message', $params);
            }

            return Redirect::refresh();
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    public function onSubmitReview()
    {
        if (
            (!$project = $this->project()) ||
            (!$review = $this->review())
        ) {
            throw new ApplicationException('Project not found!');
        }

        if (!$this->canReview()) {
            throw new ApplicationException('Action failed');
        }

        $isNewReview = $project->isOwner()
            ? !$review->is_visible
            : !$review->client_is_visible;

        if (!$isNewReview && !$this->canUpdateReview()) {
            throw new ApplicationException('Review time has expired');
        }

        if ($project->isOwner()) {
            // Review for worker
            $review->completeWorkerReview(post('Review'));
        }
        else {
            // Review for client
            $review->completeClientReview(post('Review'));
        }

        /*
         * Send notification
         */
        if ($isNewReview) {
            $user = $this->lookupUser();
            $project->resetUrlComponent('collab');
            $otherUser = $this->otherUser();
            $params = [
                'site_name' => Theme::getActiveTheme()->site_name,
                'project' => $project,
                'user' => $otherUser,
                'otherUser' => $user,
                'rating' => $project->isOwner() ? $review->rating : $review->client_rating,
                'content' => $project->isOwner() ? $review->comment : $review->client_comment,
            ];
            Mail::sendTo($otherUser, 'responsiv.pyrolancer::mail.collab-review', $params);
        }

        $this->page['project'] = $project;
        $this->page['myReview'] = $this->myReview();
        $this->page['otherUser'] = $this->otherUser();
        $this->page['otherReview'] = $this->otherReview();
        $this->page['canUpdateReview'] = $this->canUpdateReview();
        $this->page['reviewLockedAt'] = $this->reviewLockedAt();
    }

}
