<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Skill;

class WorkerSkills extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Skill selector',
            'description' => 'Allows workers to select their skills'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onGetSkillTree()
    {
        $result = [];
        $result['skills'] = Skill::lists('name', 'id');
        $result['skillTree'] = $this->makeSkillTree();
        return $result;
    }

    //
    // Internals
    //

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