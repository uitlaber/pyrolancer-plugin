<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\Category;

class PostProject extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Post Project Component',
            'description' => 'Used on the page where projects are created'
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
        return $result;
    }

    protected function makeCategoryTree()
    {
        $buildResult = function($nodes) use (&$buildResult) {
            $result = [];

            foreach ($nodes as $node) {
                $item = [
                    'id' => $node->id,
                    'name' => $node->name
                ];

                $children = $node->getChildren();
                if ($children->count())
                    $item['children'] = $buildResult($children);

                $result[] = $item;
            }

            return $result;
        };

        $children = Category::make()->getRootChildren();
        return $buildResult($children);
    }

}