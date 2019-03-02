<?php namespace Responsiv\Pyrolancer\Traits;

use Db;

/**
 * Proxies user attributes to this model
 */
trait UserProxyModel
{
    public function getCountryIdAttribute()
    {
        return $this->user->country_id;
    }

    public function getCountryAttribute()
    {
        return $this->user->country;
    }

    public function getStateIdAttribute()
    {
        return $this->user->state_id;
    }

    public function getStateAttribute()
    {
        return $this->user->state;
    }
}
