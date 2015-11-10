<?php namespace Ahoy\Pyrolancer\Components;

use Ahoy\Pyrolancer\Models\SkillCategory;
use Cms\Classes\ComponentBase;

class Directory extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Directory',
            'description' => 'For displaying a directory of workers'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    // public function portfolios()
    // {
    //     return $this->lookupObject(__FUNCTION__, ProjectModel::listFrontEnd());
    // }

    public function skillCategories()
    {
        return SkillCategory::applyVisible()->get();
    }

}