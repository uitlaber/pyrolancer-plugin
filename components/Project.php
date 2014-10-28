<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;

class Project extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Project Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'idParam' => [
                'title'       => 'Slug param name',
                'description' => 'The URL route parameter used for looking up the project by its slug. A hard coded slug can also be used.',
                'default'     => ':slug',
                'type'        => 'string',
            ],
        ];
    }

    public function onRun()
    {
        $this->page['project'] = $this->lookupModel(new ProjectModel);
    }

}