<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Skill as SkillModel;

class WorkerRegister extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Worker Register Component',
            'description' => 'Register the worker'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function availableCategories()
    {
        return SkillCategory::all();
    }

    public function onReturnCategory()
    {
        $this->page['categories'] = $this->availableCategories();
        $this->page['step'] = 1;
    }

    public function onSelectCategory()
    {
        if ($id = post('id')) {
            $this->page['category'] = SkillCategory::find($id);
            $this->page['step'] = 2;
        }
    }

}