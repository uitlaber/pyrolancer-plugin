<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\WorkerReview;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use RainLab\User\Models\User as UserModel;

class Profile extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\GeneralUtils;
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;
    use \Responsiv\Pyrolancer\Traits\ProfileContactComponent;

    public function componentDetails()
    {
        return [
            'name'        => 'Profile Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'code' => [
                'title'       => 'Code param name',
                'description' => 'The URL route parameter used for looking up the user by their short code.',
                'default'     => '{{ :code }}',
                'type'        => 'string',
            ],
            'isPrimaryWorker' => [
                'title'       => 'Primary Worker page',
                'description' => 'Link to this page when clicking on a worker.',
                'type'        => 'checkbox',
                'default'     => false,
                'showExternalParam' => false
            ],
            'isPrimaryClient' => [
                'title'       => 'Primary Client page',
                'description' => 'Link to this page when clicking on a client.',
                'type'        => 'checkbox',
                'default'     => false,
                'showExternalParam' => false
            ],
        ];
    }

    public function makePageTitle($options)
    {
        $title = array_get($options, 'default');

        if (($user = $this->user()) && ($user->client || $user->worker)) {
            $name = $user->worker ? $user->worker->business_name : $user->client->display_name;

            if ($this->property('isPrimaryWorker')) {
                $name = $user->worker ? $user->worker->business_name : null;
            }
            elseif ($this->property('isPrimaryClient')) {
                $name = $user->client ? $user->client->display_name : null;
            }

            if (strpos($title, ':name') !== false) {
                $title = strtr($title, [':name' => $name]);
            }
        }
        else {
            return 'Profile not found';
        }

        return $title;
    }

    public function onRun()
    {
        if (!$this->user()) {
            $this->setStatusCode(404);
        }
    }

    protected function getProfileContactUser()
    {
        return $this->user();
    }

    //
    // Object properties
    //

    public function user()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $id = $this->shortDecodeId($this->property('code'));
            return UserModel::find($id);
        });
    }

    public function allReviews()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (!$user = $this->user()) {
                return null;
            }

            $options = [
                'page' => input('page'),
            ];

            return WorkerReview::applyHybridUser($user)->listFrontEnd($options);
        });
    }

    public function workerReviews()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (!$user = $this->user()) {
                return null;
            }

            $options = [
                'page' => input('page'),
                'users' => $user->id,
                'visible' => true
            ];

            return WorkerReview::listFrontEnd($options);
        });
    }

    public function workerTestimonials()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (!$user = $this->user()) {
                return null;
            }

            $options = [
                'page' => input('page'),
                'users' => $user->id,
                'visible' => true
            ];

            return WorkerReview::applyTestimonial()->listFrontEnd($options);
        });
    }

    public function workerPortfolioItems()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (
                (!$user = $this->user()) ||
                !$user->is_worker ||
                !$user->worker ||
                !$user->worker->has_portfolio
            ) {
                return null;
            }

            return $user->worker->portfolio->items()->limit(4)->get();
        });
    }

    public function clientReviews()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (!$user = $this->user()) {
                return null;
            }

            $options = [
                'page' => input('page'),
                'clientUsers' => $user->id,
                'clientVisible' => true
            ];

            return WorkerReview::listFrontEnd($options);
        });
    }

    public function clientProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            if (!$user = $this->user()) {
                return null;
            }

            $options = [
                'users' => $user->id,
                'perPage' => 10
            ];

            return ProjectModel::applyVisible()->listFrontEnd($options);
        });
    }

}
