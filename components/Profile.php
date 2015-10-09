<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\WorkerReview;
use RainLab\User\Models\User as UserModel;

class Profile extends ComponentBase
{
    use \Ahoy\Traits\GeneralUtils;
    use \Ahoy\Traits\ComponentUtils;

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

    //
    // Object properties
    //

    public function user()
    {
        $id = $this->shortDecodeId($this->property('code'));
        return $this->lookupObject(__FUNCTION__, UserModel::find($id));
    }

    public function workerReviews()
    {
        $options = [
            'users' => $this->user()->id
        ];

        return $this->lookupObject(__FUNCTION__, WorkerReview::listFrontEnd($options));
    }

}