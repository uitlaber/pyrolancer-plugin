<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;

class ProjectCollab extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Project collaboration',
            'description' => 'Workspace for project collaboration'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'Slug param name',
                'description' => 'The URL route parameter used for looking up the project by its slug. A hard coded slug can also be used.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    //
    // Object properties
    //

    public function project()
    {
        $project = $this->loadModel(new ProjectModel, function($query) {
            $query->with('private_messages.worker.logo');
        });

        // if ($project->project_type->code == 'advert') {
        //     $project->load('applicants.avatar');
        //     $project->load('applicants.worker');
        // }
        // else {
        //     $project->load('bids.user.avatar');
        //     $project->load('bids.worker.logo');
        // }

        $project->private_messages->each(function($message) use ($project) {
            $message->setRelation('project', $project);
            if ($message->isProjectOwner()) {
                $message->setRelation('client', $project->client);
            }
        });

        return $project;
    }

}