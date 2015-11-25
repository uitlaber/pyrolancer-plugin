<?php namespace Ahoy\Pyrolancer\Classes;

use Queue;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;

/**
 * Usher class, for guiding jobs in to the queue
 */
class Usher
{
    public static function queueUrgentProject(ProjectModel $project)
    {
        $projectId = $project->id;

        Queue::push(function($job) use ($projectId) {
            $project = ProjectModel::find($projectId);

            Notifier::sendProjectAlert($project);

            $job->delete();
        });
    }
}