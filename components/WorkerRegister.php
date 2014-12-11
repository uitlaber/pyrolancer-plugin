<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;

class WorkerRegister extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Worker Register Component',
            'description' => 'Register the worker'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => 'Redirect',
                'description' => 'A page to redirect if the worker already has a profile',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return [''=>'- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        /*
         * Only non workers can register a new profile
         */
        $redirectAway =  (!$user = Auth::getUser()) || $user->is_worker;
        $redirectUrl = $this->controller->pageUrl($this->property('redirect'));
        if ($redirectAway && $redirectUrl) {
            return Redirect::intended($redirectUrl);
        }
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