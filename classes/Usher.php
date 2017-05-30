<?php namespace Ahoy\Pyrolancer\Classes;

use Queue;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Vicinity as VicinityModel;

/**
 * Usher class, for guiding jobs in to the queue
 */
class Usher
{
    public static function queueUrgentProject(ProjectModel $project)
    {
        $projectId = $project->id;

        Queue::push(function($job) use ($projectId) {
            if ($project = ProjectModel::find($projectId)) {
                Notifier::sendProjectAlert($project);
            }

            $job->delete();
        });
    }

    public static function queueProjectVicinity(ProjectModel $project)
    {
        $projectId = $project->id;

        Queue::push(function($job) use ($projectId) {
            if ($project = ProjectModel::find($projectId)) {
                VicinityModel::processProjectVicinity($project);
            }

            $job->delete();
        });
    }

    public static function queueWorkerVicinity(WorkerModel $worker)
    {
        $workerId = $worker->id;

        Queue::push(function($job) use ($workerId) {
            if ($worker = WorkerModel::find($workerId)) {
                VicinityModel::processWorkerVicinity($worker);
            }

            $job->delete();
        });
    }
}
