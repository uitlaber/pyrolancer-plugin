<?php namespace Responsiv\Pyrolancer\Traits;

use RainLab\User\Models\State;
use RainLab\User\Models\Country;

/**
 * Converts country_code and state_code to their respective identifiers.
 */
trait LocationCode
{

    /**
     * Sets the "country" relation with the code specified, model lookup used.
     * @param string $code
     */
    public function setCountryCodeAttribute($code)
    {
        if (!$country = Country::whereCode($code)->first())
            return;

        $this->country = $country;
    }

    /**
     * Sets the "state" relation with the code specified, model lookup used.
     * @param string $code
     */
    public function setStateCodeAttribute($code)
    {
        if (!$state = State::whereCode($code)->first())
            return;

        $this->state = $state;
    }

    /**
     * Mutator for "country_code" attribute.
     * @return string
     */
    public function getCountryCodeAttribute()
    {
        return $this->country ? $this->country->code : null;
    }

    /**
     * Mutator for "state_code" attribute.
     * @return string
     */
    public function getStateCodeAttribute()
    {
        return $this->state ? $this->state->code : null;
    }

}