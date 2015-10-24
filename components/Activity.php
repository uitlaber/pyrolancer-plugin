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

        $feed = UserEventLog::applyVisible()
            ->applyEagerLoads()
            ->orderBy('created_at', 'desc')
            ->paginate(10, $currentPage)
        ;

        return $feed;
    }

    // public function feed2()
    // {
    //     $feed = new DataFeed;

    //     $feed->add(
    //         'worker',
    //         WorkerModel::with('user')
    //             ->with('skills')
    //             ->with('logo'),
    //         'created_at'
    //     );

    //     $feed->add(
    //         'project',
    //         ProjectModel::applyVisible()
    //             ->with('user.client'),
    //         'created_at'
    //     );

    //     $feed->add(
    //         'portfolio',
    //         PortfolioModel::applyVisible()
    //             ->with('user.worker')
    //             ->with('items'),
    //         'created_at'
    //     );

    //     $results = $feed->limit(25)->get();

    //     return $results;
    // }

    public function recentWorkers()
    {
        return WorkerModel::with('user.avatar')->limit(5)->get();
    }

}