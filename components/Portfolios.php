<?php namespace Ahoy\Pyrolancer\Components;

use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Portfolio as PortfolioModel;
use Cms\Classes\ComponentBase;

class Portfolios extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

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
        return $this->lookupObject(__FUNCTION__, function() {
            return WorkerModel::applyPortfolio()->listFrontEnd();
        });
    }

}