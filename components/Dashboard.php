<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Redirect;
use Responsiv\Pyrolancer\Models\UserEventLog;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\Portfolio as PortfolioModel;
use Responsiv\Pyrolancer\Models\Attribute as AttributeModel;
use Cms\Classes\ComponentBase;

class Dashboard extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Dashboard',
            'description' => 'Handles the redirection of the dashboard'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {

    }

    public function feed()
    {
        $currentPage = 1;

        $feed = UserEventLog::applyPrivate()
            ->applyEagerLoads()
            ->orderBy('created_at', 'desc')
            ->paginate(10, $currentPage)
        ;

        return $feed;
    }

    public function clientProjects()
    {
        return $this->lookupObject(__FUNCTION__, ProjectModel::make()
            ->orderBy('created_at', 'desc')
            ->applyOwner()
            ->limit(3)
            ->get()
        );
    }

    public function workerProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();
            if (!$user->worker) {
                return null;
            }

            $skills = $user->worker->skills->lists('id');

            return ProjectModel::make()
                ->orderBy('created_at', 'desc')
                ->applyActive()
                ->whereHas('skills', function($q) use ($skills) {
                    $q->whereIn('id', $skills);
                })
                ->limit(3)
                ->get();
        });
    }

    public function hasProjectsInDevelopment()
    {
        return $this->projectsInDevelopment()->count() > 0;
    }

    public function projectsInDevelopment()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            return ProjectModel::make()
                ->applyStatus([
                    ProjectModel::STATUS_DEVELOPMENT,
                    ProjectModel::STATUS_TERMINATED,
                    ProjectModel::STATUS_COMPLETED,
                ])
                ->where(function($q) use ($user) {
                    $q->where('user_id', $user->id);
                    $q->orWhere('chosen_user_id', $user->id);
                })
                ->get()
            ;
        });
    }

    public function hasChosenProjects()
    {
        return $this->chosenProjects()->count() > 0;
    }

    public function chosenProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            return ProjectModel::make()
                ->applyStatus(ProjectModel::STATUS_WAIT)
                ->where('chosen_user_id', $user->id)
                ->get()
            ;
        });
    }

    public function hasPortfolio()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return ($portfolio = PortfolioModel::getFromWorker())
                ? $portfolio->is_visible
                : false;
        });
    }

    public function hasUnratedProjects()
    {
        return $this->unratedProjects()->count() > 0;
    }

    public function unratedProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            return ProjectModel::make()
                ->with('review')
                ->applyStatus([
                    ProjectModel::STATUS_TERMINATED,
                    ProjectModel::STATUS_COMPLETED,
                ])
                ->where(function($q) use ($user) {
                    $q->where('user_id', $user->id);
                    $q->orWhere('chosen_user_id', $user->id);
                })
                ->get()
                ->filter(function($project) {
                    return !$project->hasReview();
                })
            ;
        });
    }

    public function hasRejectedProjects()
    {
        return $this->rejectedProjects()->count() > 0;
    }

    public function rejectedProjects()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            return ProjectModel::make()
                ->applyStatus(ProjectModel::STATUS_REJECTED)
                ->where('user_id', $user->id)
                ->get()
            ;
        });
    }

}