<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\ProjectOption;
use Ahoy\Pyrolancer\Models\ProjectCategory;
use Ahoy\Pyrolancer\Models\Skill as SkillModel;
use Ahoy\Pyrolancer\Models\SkillCategory;

class ProjectValues extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Project Values',
            'description' => 'Provides project categories and options'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function skillCategories()
    {
        return SkillCategory::isVisible()->get();
    }

    public function projectTypes()
    {
        return ProjectOption::forType(ProjectOption::PROJECT_TYPE)->get();
    }

    public function positionTypes()
    {
        return ProjectOption::forType(ProjectOption::POSITION_TYPE)->get();
    }

    public function budgetTypes()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_TYPE)->get();
    }

    public function budgetFixedOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_FIXED)->get();
    }

    public function budgetHourlyOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_HOURLY)->get();
    }

    public function budgetTimeframeOptions()
    {
        return ProjectOption::forType(ProjectOption::BUDGET_TIMEFRAME)->get();
    }

    public function bidTypeOptions()
    {
        return ProjectOption::forType(ProjectOption::BID_TYPE)->get();
    }

    //
    // AJAX
    //

    public function onGetSkills()
    {
        $result = [];
        $result['skills'] = SkillModel::lists('name', 'id');
        return $result;
    }

    public function onGetCategorySkillMap()
    {
        $result = [];
        $result['categories'] = $this->makeCategoryTree();
        $result['skills'] = SkillModel::lists('name', 'id');
        $result['categorySkillMap'] = $this->makeCategorySkillMap();
        return $result;
    }

    //
    // Internals
    //

    protected function makeCategorySkillMap()
    {
        $idMap = ProjectCategory::skills()->newPivotStatement()->get();
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

        $children = ProjectCategory::make()->getAllRoot();
        return $buildResult($children);
    }

}