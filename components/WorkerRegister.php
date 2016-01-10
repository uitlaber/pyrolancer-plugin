<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;

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
        $redirectPage = $this->property('redirect');
        if ($redirectAway && $redirectPage) {
            return Redirect::to($this->controller->pageUrl($redirectPage));
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

    public function recentProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return ProjectModel::applyVisible()->listFrontEnd([
                'perPage' => 4
            ]);
        });
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
        if (($id = post('id')) && ($category = SkillCategory::find($id))) {
            $worker = $this->worker();
            $worker->category_id = $category->id;
            $worker->forceSave();

            $this->page['category'] = $category;
            $this->page['step'] = 2;
        }
    }

    public function onReturnSkills()
    {
        $worker = $this->worker();
        if ($worker->category) {
            $this->page['category'] = $worker->category;
            $this->page['step'] = 2;
        }
        else {
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
        $worker->is_visible = true;
        $worker->save();

        $user = $this->lookupUser();
        $user->fill((array) post('User'));
        $user->country_id = post('country_id');
        $user->state_id = post('state_id');
        $user->save();

        $worker->completeProfile();
    }

}