<?php namespace Responsiv\Pyrolancer\Traits;

use Auth;

trait ModelUtils
{
    protected function lookupUser($user = null)
    {
        if (!$user)
            $user = Auth::getUser();

        if (!$user)
            return false;

        return $user;
    }

    public function isOwner($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        return $this->user_id == $user->id;
    }

    public function scopeApplyOwner($query, $user = null)
    {
        if (!$user = $this->lookupUser($user)) {
            return $query->whereRaw('1 = 2');
        }

        return $query->where('user_id', $user->id);
    }
}
