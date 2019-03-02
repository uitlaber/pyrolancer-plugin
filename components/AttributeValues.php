<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Attribute;

class AttributeValues extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Attribute Values',
            'description' => 'Provides general attributes and options'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function projectTypes()
    {
        return Attribute::listType(Attribute::PROJECT_TYPE);
    }

    public function positionTypes()
    {
        return Attribute::listType(Attribute::POSITION_TYPE);
    }

    public function budgetTypes()
    {
        return Attribute::listType(Attribute::BUDGET_TYPE);
    }

    public function budgetFixedOptions()
    {
        return Attribute::listType(Attribute::BUDGET_FIXED);
    }

    public function budgetHourlyOptions()
    {
        return Attribute::listType(Attribute::BUDGET_HOURLY);
    }

    public function budgetTimeframeOptions()
    {
        return Attribute::listType(Attribute::BUDGET_TIMEFRAME);
    }

    public function bidTypes()
    {
        return Attribute::listType(Attribute::BID_TYPE);
    }

    public function workerBudgetOptions()
    {
        return Attribute::listType(Attribute::WORKER_BUDGET);
    }

    public function portfolioTypes()
    {
        return Attribute::listType(Attribute::PORTFOLIO_TYPE);
    }

}