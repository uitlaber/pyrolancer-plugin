<?php namespace Responsiv\Pyrolancer\Classes;

use Mail;
use Cms\Classes\Theme;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\Client as ClientModel;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;

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

        $workers = WorkerModel::applyVisible()
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
                'site_name' => Theme::getActiveTheme()->site_name,
                'user' => $worker->user,
                'project' => $project
            ];

            Mail::sendTo($worker->user, 'responsiv.pyrolancer::mail.worker-alert', $params);
        }

        return true;
    }

    /**
     * Send a digest of projects to a worker
     */
    public static function sendWorkerDigest(WorkerModel $worker, $fromDate)
    {
        $skills = $worker->skills()->lists('id');

        $projects = ProjectModel::with('client')
            ->applyActive()
            ->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('id', $skills);
            });

        if ($fromDate) {
            $projects->where('created_at', '>', $fromDate);
        }

        if ($worker->latitude && $worker->longitude) {
            $projects->where(function($q) use ($worker) {
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
            'site_name' => Theme::getActiveTheme()->site_name,
            'user' => $worker->user,
            'projects' => $projects
        ];

        Mail::sendTo($worker->user, 'responsiv.pyrolancer::mail.worker-digest', $params);
        return true;
    }

    /**
     * Send a digest of questions, bids and applicants to a client
     */
    public static function sendClientDigest(ClientModel $client, $fromDate)
    {
        $projects = ProjectModel::make()
            ->with('project_type')
            ->applyActive()
            ->where('user_id', $client->user_id)
            ->get();

        foreach ($projects as $project) {

            $messages = $project->messages()->where('user_id', '<>', $client->user_id);
            if ($fromDate) {
                $messages->where('responsiv_pyrolancer_project_messages.created_at', '>', $fromDate);
            }
            $messages = $messages->get();

            if ($project->project_type->code == 'advert') {

                $contacts = $project->applicants();
                if ($fromDate) {
                    $contacts->where('responsiv_pyrolancer_projects_applicants.created_at', '>', $fromDate);
                }
                $contacts = $contacts->get();

            }
            else {

                $contacts = $project->bids();
                if ($fromDate) {
                    $contacts->where('responsiv_pyrolancer_project_bids.created_at', '>', $fromDate);
                }
                $contacts = $contacts->get();

            }

            if (!count($messages) && !count($contacts)) {
                return false;
            }

            $params = [
                'site_name' => Theme::getActiveTheme()->site_name,
                'project' => $project,
                'projectType' => $project->project_type->code,
                'messages' => $messages,
                'contacts' => $contacts
            ];

            Mail::sendTo($client->user, 'responsiv.pyrolancer::mail.client-digest', $params);
            return true;
        }

    }

}
