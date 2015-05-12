<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\ComponentBase;
use RainLab\User\Models\State;
use RainLab\User\Models\Country;
use Ahoy\Pyrolancer\Models\Skill;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\WorkerReview;

class WorkerManage extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Manager Worker Profile',
            'description' => 'Allows workers to select their skills'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => 'Redirect',
                'description' => 'A page to redirect if the worker has no profile set up',
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
         * User must have a profile set up to view this page
         */
        $redirectAway = ($user = Auth::getUser()) && !$user->is_worker;
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
        $worker = $this->lookupObject(__FUNCTION__, WorkerModel::getFromUser());
        $worker->setUrl('worker', $this->controller);
        return $worker;
    }

    public function reviews()
    {
        $options = [
            'users' => $this->worker()->user_id
        ];

        return $this->lookupObject(__FUNCTION__, WorkerReview::listFrontEnd($options));
    }

    //
    // AJAX
    //

    public function onSaveProfile()
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
    }

    public function onChangeLocation()
    {
        $country = Country::isEnabled()->whereCode(post('country_code'))->first();
        if ($country) {
            $state = State::whereCode(post('state_code'))->first();
            $this->page['countryId'] = $country->id;
            $this->page['stateId'] = $state->id;
        }
        else {
            $this->page['countryId'] = -1;
            $this->page['stateId'] = -1;
        }
    }

    public function onPatch()
    {
        if (!$worker = $this->worker()) {
            throw new ApplicationException('You must be logged in!');
        }

        $data = $this->patchModel($worker, post('Worker'));
        $worker->save();

        $this->page['worker'] = $worker;
    }

    //
    // Skills
    //

    public function onGetSkillTree()
    {
        $result = [];
        $result['skills'] = Skill::lists('name', 'id');
        $result['skillTree'] = $this->makeSkillTree();
        return $result;
    }

    protected function makeSkillTree()
    {
        $tree = [];

        /*
         * Eager load skills
         */
        $skillCategory = new SkillCategory;
        $skillCategory->setTreeOrderBy('sort_order');
        $categories = $skillCategory->getAll();
        $categories->load('skills');

        /*
         * Make the tree
         */
        $buildResult = function($nodes) use (&$buildResult) {
            $result = [];

            foreach ($nodes as $node) {
                $item = [
                    'id' => $node->id,
                    'name' => $node->name
                ];

                $children = $node->getChildren();
                if ($children->count()) {
                    $item['children'] = $buildResult($children);
                }
                else if ($node->skills) {
                    $skills = [];
                    foreach ($node->skills as $skill) {
                        $skill = [
                            'id' => $skill->id,
                            'name' => $skill->name
                        ];
                        $skills[] = $skill;
                    }
                    $item['children'] = $skills;
                }

                $result[] = $item;
            }

            return $result;
        };

        return $buildResult($skillCategory->getAllRoot());
    }

}