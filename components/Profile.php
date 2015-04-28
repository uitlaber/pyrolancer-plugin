<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
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

}