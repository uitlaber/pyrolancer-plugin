<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;

class ClientProjects extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

    public $projects;

    public function componentDetails()
    {
        return [
            'name'        => 'Client Projects',
            'description' => 'Displays projects created by the client'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->projects = ProjectModel::make()
            ->with('status')
            ->with('project_type')
            ->orderBy('created_at', 'desc')
            ->applyOwner()
            ->get()
        ;
    }

}