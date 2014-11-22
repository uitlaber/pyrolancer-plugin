<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectExtraDetail;
use ApplicationException;

/*
 * This component depends on the Project component
 */
class ProjectManage extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Project Management',
            'description' => 'Allows the client to manage their project'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onAddExtraDetails()
    {
        if (!$project = $this->lookupModelSecure(new ProjectModel))
            throw new ApplicationException('Action failed');

        $extra = new ProjectExtraDetail;
        $extra->description = post('description');
        $extra->project = $project;
        $extra->save();

        $this->page['project'] = $project;
    }

}