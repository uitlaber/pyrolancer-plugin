<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Database\DataFeed;
use Ahoy\Pyrolancer\Models\UserEventLog;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;

class Activity extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Activity Component',
            'description' => 'Displays a feed of site activity.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function feed()
    {
        $currentPage = 1;

        $feed = UserEventLog::has('user')
            ->applyPublic()
            ->applyEagerLoads()
            ->orderBy('created_at', 'desc')
            ->limit(11)
            ->get()
        ;

        return $feed;
    }

    public function recentWorkers()
    {
        return WorkerModel::has('user.avatar')
            ->applyVisible()
            ->with('user.avatar')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->remember(60)
            ->get()
        ;
    }

}