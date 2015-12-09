<?php namespace Ahoy\Pyrolancer\Traits;

use Db;

/**
 * Proxies user attributes to this model
 */
trait UserProxyModel
{
    public function getCountryAttribute()
    {
        return $this->user->country;
    }

    public function getStateAttribute()
    {
        return $this->user->state;
    }
}
