<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\Category;
use Responsiv\Pyrolancer\Classes\ProjectData;

class PostProject extends ComponentBase
{

    public $project;

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

    public function init()
    {
        $this->controller->bindEvent('responsiv.uploader.sendFile', function($uploader){
            traceLog($uploader->apiCode);

            return [
                ['url' => 'http://daftspunk.com']
            ];
        });
    }

    public function onRun()
    {
        $this->project = ProjectData::getProjectObject();
    }

    public function onStartProject()
    {
        ProjectData::reset();
        ProjectData::saveProjectData();
    }

    public function onGetCategorySkillMap()
    {
        $result = [];
        $result['categories'] = $this->makeCategoryTree();
        $result['skills'] = Skill::lists('name', 'id');
        $result['categorySkillMap'] = $this->makeCategorySkillMap();
        return $result;
    }

    protected function makeCategorySkillMap()
    {
        $idMap = Category::skills()->newPivotStatement()->get();
        $result = [];

        foreach ($idMap as $map) {
            if (!isset($result[$map->category_id]))
                $result[$map->category_id] = [];

            $result[$map->category_id][] = $map->skill_id;
        }

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