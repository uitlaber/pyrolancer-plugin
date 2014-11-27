<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Skill as SkillModel;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;

class WorkerRegister extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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

    public function getWorker()
    {
        return WorkerModel::getFromUser();
    }

    public function getCategories()
    {
        return SkillCategory::all();
    }

    public function onReturnCategory()
    {
        $this->page['categories'] = $this->getCategories();
        $this->page['step'] = 1;
    }

    public function onSelectCategory()
    {
        if ($id = post('id')) {
            $this->page['category'] = SkillCategory::find($id);
            $this->page['step'] = 2;
        }
    }

    public function onReturnSkills()
    {
        $this->onSelectCategory();
        $this->page['step'] = 2;

        if (!$this->page->category) {
            $this->onReturnCategory();
        }
    }

    public function onSelectSkills()
    {
        $user = $this->lookupUser();
        $worker = WorkerModel::getFromUser();
        $worker->skills = post('skills');
        $worker->save();
    }

    public function onCompleteProfile()
    {
        $user = $this->lookupUser();
        $user->country_id = post('country_id');
        $user->state_id = post('state_id');
        $user->is_worker = true;
        $user->save();

        $worker = WorkerModel::getFromUser();
        $worker->business_name = post('business_name');
        $worker->description = post('description');
        $worker->save();
    }

}