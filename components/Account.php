<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use ApplicationException;

class Account extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Account',
            'description' => 'Account management for all users'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Returns the logged in user, if available
     */
    public function user()
    {
        return $this->lookupObject(__FUNCTION__, Auth::getUser());
    }

    public function onPatch()
    {
        $user = $this->lookupUser();
        $data = $this->patchModel($user);
        $user->save();

        $this->page['user'] = $user;

        /*
         * Password has changed, reauthenticate the user
         */
        if (array_get($data, 'password')) {
            Auth::login($user->reload(), true);
        }
    }

}