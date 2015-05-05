<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use October\Rain\Database\DataFeed;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\Portfolio as PortfolioModel;

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
        $feed = new DataFeed;
        $feed->add('worker', new WorkerModel);
        $feed->add('project', ProjectModel::with('user'));
        $feed->add('portfolio', new PortfolioModel);
        return $feed->limit(25)->get();
    }

    public function recentWorkers()
    {
        return WorkerModel::all();
    }

}