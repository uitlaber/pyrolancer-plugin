<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;

class Favorites extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Favorites Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function hasList()
    {
        return false;
    }

}