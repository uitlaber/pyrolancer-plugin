<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;

class Portfolios extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Portfolios',
            'description' => 'Lists workers who have portfolios set up'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function portfolios()
    {
        return $this->lookupObject(__FUNCTION__, ProjectModel::listFrontEnd());
    }

}