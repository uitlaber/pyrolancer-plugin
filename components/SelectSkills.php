<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;

class SelectSkills extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Skill selector',
            'description' => 'Allows freelancers to select their skills'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onGetCategorySkillMap()
    {
        $result = [];
        $result['categories'] = $this->makeCategoryTree();
        $result['skills'] = Skill::lists('name', 'id');
        $result['categorySkillMap'] = $this->makeCategorySkillMap();
        return $result;
    }

}