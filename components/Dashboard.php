<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Ahoy\Pyrolancer\Models\UserEventLog;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Attribute as AttributeModel;
use Cms\Classes\ComponentBase;

class Dashboard extends ComponentBase
{

    use \Ahoy\Traits\ComponentUtils;

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

    public function projectsInDevelopment()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $user = $this->lookupUser();

            $statusIds = AttributeModel::applyType(AttributeModel::PROJECT_STATUS)
                ->whereIn('code', [
                    ProjectModel::STATUS_DEVELOPMENT,
                    ProjectModel::STATUS_COMPLETED,
                ])
                ->lists('id')
            ;

            return ProjectModel::make()
                ->whereIn('status_id', $statusIds)
                ->where(function($q) use ($user) {
                    $q->where('user_id', $user->id);
                    $q->orWhereHas('chosen_bid', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                })
                ->get()
            ;
        });
    }

}