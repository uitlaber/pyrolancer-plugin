<?php namespace Responsiv\Pyrolancer\Classes;

use Mail;
use Event;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\Client as ClientModel;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Carbon\Carbon;
use ApplicationException;

/**
 * Worker class, engaged by the automated worker
 */
class Worker
{

    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var bool There should be only one task performed per execution.
     */
    protected $isReady = true;

    /**
     * @var string Processing message
     */
    protected $logMessage = 'There are no outstanding activities to perform.';

    /*
     * Process all tasks
     */
    public function process()
    {
        $methods = [
            'processExpiredProjects',
            'processClientDigest',
            'processWorkerDigest'
        ];

        shuffle($methods);

        foreach ($methods as $method) {
            $this->isReady && $this->$method();
        }

        return $this->logMessage;
    }

    /**
     * Locates active projects that have passed their expired date.
     */
    public function processExpiredProjects()
    {
        $projects = ProjectModel::make()
            ->applyStatus(ProjectModel::STATUS_ACTIVE)
            ->where('expires_at', '<', Carbon::now())->get();

        if ($totalProjects = count($projects)) {
            foreach ($projects as $project) {
                $project->markExpired();
            }

            $this->logActivity(sprintf(
                'Marked %s projects(s) as expired.',
                $totalProjects
            ));
        }
    }

    /**
     * This will list all workers, sorted by the last digest date,
     * where the last digest date exceeds the specified digest frequency,
     * finds jobs that were submitted between now and last digest date,
     * matches the worker profile and emails them in chunks of 100.
     *
     * Default frequency: 1 day
     */
    public function processWorkerDigest()
    {
        $days = max(1, 1); // Must always be greater than 1
        $loop = 100;

        $now = Carbon::now();
        $start = Carbon::now()->subDays($days);

        $count = 0;
        for ($i = 0; $i < $loop; $i++) {
            $worker = WorkerModel::make()
                ->applyVisible()
                ->where(function($q) use ($start) {
                    $q->where('last_digest_at', '<', $start);
                    $q->orWhereNull('last_digest_at');
                })
                ->first()
            ;

            if ($worker) {
                Notifier::sendWorkerDigest($worker, $worker->last_digest_at);
                $count++;

                $worker->last_digest_at = $now;
                $worker->timestamps = false;
                $worker->forceSave();
            }
        }

        if ($count > 0) {
            $this->logActivity(sprintf(
                'Sent job digest to %s worker(s).',
                $count
            ));
        }
    }

    /**
     * This will list all clients, sorted by the last digest date,
     * Similar to worker digest and notifies them about questions,
     * bids and applicants to their projects.
     *
     * Default frequency: 1 hour
     */
    public function processClientDigest()
    {
        $hours = max(1, 1); // Must always be greater than 1
        $loop = 100;

        $now = Carbon::now();
        $start = Carbon::now()->subHours($hours);

        $count = 0;
        for ($i = 0; $i < $loop; $i++) {
            $client = ClientModel::make()
                ->where(function($q) use ($start) {
                    $q->where('last_digest_at', '<', $start);
                    $q->orWhereNull('last_digest_at');
                })
                ->first()
            ;

            if ($client) {
                Notifier::sendClientDigest($client, $client->last_digest_at);
                $count++;

                $client->last_digest_at = $now;
                $client->timestamps = false;
                $client->forceSave();
            }
        }

        if ($count > 0) {
            $this->logActivity(sprintf(
                'Sent project digest to %s clients(s).',
                $count
            ));
        }
    }

    /**
     * Called when activity has been performed.
     */
    protected function logActivity($message)
    {
        $this->logMessage = $message;
        $this->isReady = false;
    }

}