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
        return Attribute::forType(Attribute::PROJECT_TYPE)->get();
    }

    public function positionTypes()
    {
        return Attribute::forType(Attribute::POSITION_TYPE)->get();
    }

    public function budgetTypes()
    {
        return Attribute::forType(Attribute::BUDGET_TYPE)->get();
    }

    public function budgetFixedOptions()
    {
        return Attribute::forType(Attribute::BUDGET_FIXED)->get();
    }

    public function budgetHourlyOptions()
    {
        return Attribute::forType(Attribute::BUDGET_HOURLY)->get();
    }

    public function budgetTimeframeOptions()
    {
        return Attribute::forType(Attribute::BUDGET_TIMEFRAME)->get();
    }

    public function bidTypeOptions()
    {
        return Attribute::forType(Attribute::BID_TYPE)->get();
    }
}