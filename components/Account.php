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
        if (!$user = $this->user()) {
            throw new ApplicationException('You must be logged in!');
        }

        $attributes = array_map('trim', explode(',', post('propertyName')));
        $data = array_where(post(), function($key, $value) use ($attributes) {
            return in_array($key, $attributes);
        });

        $user->rules = array_intersect_key($user->rules, array_flip($attributes));
        $user->fill($data);
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