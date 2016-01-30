<?php namespace Ahoy\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;

class Worker extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Worker Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'Slug param name',
                'description' => 'The URL route parameter used for looking up the worker by the slug. A hard coded slug can also be used.',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
        ];
    }

    public function onRun()
    {
        if ($worker = $this->worker()) {
            $this->page->meta_title = $this->page->meta_title
                ? str_replace('%s', $worker->business_name, $this->page->meta_title)
                : $worker->business_name;
        }
    }

    //
    // Object properties
    //

    public function worker()
    {
        return $this->loadModel(new WorkerModel, function($query) {
            $query->with('portfolio.items.uploaded_file');
        });
    }


}