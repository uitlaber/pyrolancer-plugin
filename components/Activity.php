<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Database\DataFeed;
use Responsiv\Pyrolancer\Models\UserEventLog;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;

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

        $feed = UserEventLog::applyPublic()
            ->applyEagerLoads()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
        ;

        return $feed;
    }

    public function recentWorkers()
    {
        return WorkerModel::applyVisible()
            ->with('user.avatar')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
        ;
    }

}
