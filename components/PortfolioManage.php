<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;

class PortfolioManage extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Portfolio Manage Component',
            'description' => 'Management features for a worker portfolio'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    //
    // Object properties
    //

    public function worker()
    {
        return $this->lookupObject(__FUNCTION__, WorkerModel::getFromUser());
    }

    public function hasPortfolio()
    {
        return $this->worker()->portfolio()->count() > 0;
    }

    //
    // AJAX
    //

    public function onCreateItem()
    {
        
    }

    public function onUpdateItem()
    {

    }

    public function onLoadItemForm()
    {

    }

}