<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
use Ahoy\Pyrolancer\Models\UserEventLog;
use Cms\Classes\ComponentBase;

class Dashboard extends ComponentBase
{

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

        $feed = UserEventLog::applyOwner()
            ->applyEagerLoads()
            ->orderBy('created_at', 'desc')
            ->paginate(10, $currentPage)
        ;

        return $feed;
    }

}