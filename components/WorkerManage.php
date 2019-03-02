<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use RainLab\Location\Models\State;
use RainLab\Location\Models\Country;
use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\WorkerReview;
use ApplicationException;

class WorkerManage extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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

    public function reviews()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $options = [
                'users' => $this->worker()->user_id,
                'visible' => true
            ];

            return WorkerReview::listFrontEnd($options);
        });
    }

    public function categories()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return SkillCategory::all();
        });
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

    public function onSaveSkills()
    {
        $maxSkills = 20;

        $worker = $this->worker();
        $skillIds = post('skills', []);

        if (count($skillIds) > $maxSkills) {
            throw new ApplicationException(sprintf('You can only select a maximum of %s skills!', $maxSkills));
        }

        $worker->skills()->sync($skillIds);
    }

    public function onChangeLocation()
    {
        $country = Country::isEnabled()->whereCode(post('country_code'))->first();
        if ($country) {
            $state = State::where('country_id', $country->id)->whereCode(post('state_code'))->first();
            $this->page['countryId'] = $country->id;
            $this->page['stateId'] = $state ? $state->id : -1;
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

        $isLocation = strpos(post('propertyName'), 'street_addr') !== false;
        if ($isLocation) {
            $this->onPatchUser();
            $worker->fallback_location = $this->makeFallbackLocation();
        }

        $data = $this->patchModel($worker, post('Worker'));
        $worker->save();

        $this->page['worker'] = $worker;
    }

    public function onPatchUser()
    {
        $user = $this->lookupUser();

        $data = $this->patchModel($user, post());
        $user->save();

        $this->page['user'] = $user;
    }

    protected function makeFallbackLocation()
    {
        $fallbackLocation = null;

        /*
         * If the Location plugin can't find a state or country for the codes,
         * this will save a fallback value to use instead of showing an empty
         * location.
         */
        if (!post('Worker[fallback_location]')) {
            $fallbackLocation = '';
            $fallbackLocation .= post('state_code');
            $fallbackLocation .= strlen($fallbackLocation) ? ', ' : '';
            $fallbackLocation .= post('country_code');
        }

        return $fallbackLocation;
    }

    //
    // Skills
    //

    public function onGetSkillTree()
    {
        $result = [];
        $result['skills'] = Skill::lists('name', 'id');
        $result['skillTree'] = $this->makeSkillTree();
        $result['selectedSkills'] = $this->worker()->skills()->lists('name', 'id');
        return $result;
    }

    protected function makeSkillTree()
    {
        $tree = [];

        /*
         * Eager load skills
         */
        $categories = SkillCategory::orderBy('sort_order')->get();
        $categories->load('skills');
        $categories = $categories->toNested();

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

        return $buildResult($categories);
    }

}
