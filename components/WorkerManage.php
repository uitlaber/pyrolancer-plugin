<?php namespace Ahoy\Pyrolancer\Components;

use Config;
use Cms\Classes\ComponentBase;
use RainLab\User\Models\State;
use RainLab\User\Models\Country;
use Ahoy\Pyrolancer\Models\Skill;
use Ahoy\Pyrolancer\Models\SkillCategory;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;

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
        return [];
    }

    //
    // Object properties
    //

    public function worker()
    {
        return $this->lookupObject(__FUNCTION__, WorkerModel::getFromUser());
    }

    //
    // AJAX
    //

    public function onSaveProfile()
    {
        $worker = $this->worker();
        $worker->fill((array) post('Worker'));
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

    //
    // Reviews
    //

    public function onLoadTestimonialForm()
    {
        $this->page['from_email'] = Config::get('mail.from.address');
    }

    public function onSubmitTestimonial()
    {

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