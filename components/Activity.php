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

        $feed->add(
            'worker',
            WorkerModel::with('user')
                ->with('skills')
                ->with('logo'),
            'created_at'
        );

        $feed->add(
            'project',
            ProjectModel::applyVisible()
                ->with('user.client'),
            'created_at'
        );

        $feed->add(
            'portfolio',
            PortfolioModel::applyVisible()
                ->with('user.worker')
                ->with('items'),
            'created_at'
        );

        $results = $feed->limit(25)->get();

        $results->each(function($result){
            if ($result->tag_name == 'worker') {
                $result->setUrl('worker', $this->controller);
            }
            elseif ($result->tag_name == 'project') {
                $result->user->client->setUrl('client', $this->controller);
            }
        });

        return $results;
    }

    public function recentWorkers()
    {
        $results = WorkerModel::limit(5)->get();

        $results->each(function($result){
            $result->setUrl('worker', $this->controller);
        });

        return $results;
    }

}