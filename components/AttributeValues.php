<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Attribute;

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
        return Attribute::applyType(Attribute::PROJECT_TYPE)->get();
    }

    public function positionTypes()
    {
        return Attribute::applyType(Attribute::POSITION_TYPE)->get();
    }

    public function budgetTypes()
    {
        return Attribute::applyType(Attribute::BUDGET_TYPE)->get();
    }

    public function budgetFixedOptions()
    {
        return Attribute::applyType(Attribute::BUDGET_FIXED)->get();
    }

    public function budgetHourlyOptions()
    {
        return Attribute::applyType(Attribute::BUDGET_HOURLY)->get();
    }

    public function budgetTimeframeOptions()
    {
        return Attribute::applyType(Attribute::BUDGET_TIMEFRAME)->get();
    }

    public function bidTypes()
    {
        return Attribute::applyType(Attribute::BID_TYPE)->get();
    }

    public function workerBudgetOptions()
    {
        return Attribute::applyType(Attribute::WORKER_BUDGET)->get();
    }
}