<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\Page;
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
            return Redirect::to($redirectUrl);
        }
    }

    //
    // Object properties
    //

    public function worker()
    {
        return $this->lookupObject(__FUNCTION__, WorkerModel::getFromUser());
    }

    public function categories()
    {
        return $this->lookupObject(__FUNCTION__, SkillCategory::all());
    }

    //
    // AJAX
    //

    public function onReturnCategory()
    {
        $this->page['categories'] = $this->categories();
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
        $worker = $this->worker();
        $worker->skills = post('skills');
        $worker->forceSave();
    }

    public function onCompleteProfile()
    {
        $worker = $this->worker();
        $worker->fill((array) post('Worker'));
        $worker->resetSlug();
        $worker->save();

        $user = $this->lookupUser();
        $user->fill((array) post('User'));
        $user->country_id = post('country_id');
        $user->state_id = post('state_id');
        $user->save();

        $worker->completeProfile();
    }

}