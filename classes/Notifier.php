<?php namespace Ahoy\Pyrolancer\Classes;

use Mail;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;

/**
 * Notification class, for notifying workers about projects
 */
class Notifier
{
    /**
     * Sends an alert to workers about a specific project
     */
    public static function sendProjectAlert(ProjectModel $project)
    {
        $skills = $project->skills()->lists('id');

        $workers = WorkerModel::make()
            ->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('id', $skills);
            });

        if (!$project->is_remote) {
            $workers->applyArea($project->latitude, $project->longitude);
        }

        $workers = $workers->get();

        if (!count($workers)) {
            return false;
        }

        foreach ($workers as $worker) {
            $params = [
                'user' => $worker->user,
                'project' => $project
            ];

            Mail::sendTo($worker->user, 'ahoy.pyrolancer::mail.worker-alert', $params);
        }

    }

    /**
     * Send a digest of projects to a worker
     */
    public static function sendWorkerDigest(WorkerModel $worker, $fromDate)
    {
        $skills = $worker->skills()->lists('id');

        $projects = ProjectModel::make()
            ->where('is_visible', true)
            ->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('id', $skills);
            });

        if ($fromDate) {
            $projects->where('created_at', '>', $fromDate);
        }

        if ($worker->latitude && $worker->longitude) {
            $projects->where(function($q) {
                $q->applyArea($worker->latitude, $worker->longitude);
                $q->orWhere('is_remote', true);
            });
        }
        else {
            $projects->where('is_remote', true);
        }

        $projects = $projects->get();

        if (!count($projects)) {
            return false;
        }

        $params = [
            'user' => $worker->user,
            'projects' => $projects
        ];

        Mail::sendTo($worker->user, 'ahoy.pyrolancer::mail.worker-digest', $params);
    }

}