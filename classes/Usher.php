<?php namespace Responsiv\Pyrolancer\Classes;

use Queue;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\Vicinity as VicinityModel;

/**
 * Usher class, for guiding jobs in to the queue
 */
class Usher
{
    public static function queueUrgentProject(ProjectModel $project)
    {
        Queue::push(self::class.'@urgentProject', ['projectId' => $project->id]);
    }

    public function urgentProject($job, $data)
    {
        if (
            ($projectId = array_get($data, 'projectId')) &&
            ($project = ProjectModel::find($projectId))
        ) {
            Notifier::sendProjectAlert($project);
        }

        $job->delete();
    }

    public static function queueProjectVicinity(ProjectModel $project)
    {
        Queue::push(self::class.'@projectVicinity', ['projectId' => $project->id]);
    }

    public function projectVicinity($job, $data)
    {
        if (
            ($projectId = array_get($data, 'projectId')) &&
            ($project = ProjectModel::find($projectId))
        ) {
            VicinityModel::processProjectVicinity($project);
        }

        $job->delete();
    }

    public static function queueWorkerVicinity(WorkerModel $worker)
    {
        Queue::push(self::class.'@workerVicinity', ['workerId' => $worker->id]);
    }

    public function workerVicinity($job, $data)
    {
        if (
            ($workerId = array_get($data, 'workerId')) &&
            ($worker = WorkerModel::find($workerId))
        ) {
            VicinityModel::processWorkerVicinity($worker);
        }

        $job->delete();
    }
}
