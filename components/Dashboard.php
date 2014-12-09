<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Redirect;
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
        return [
            'redirectGuest' => [
                'title'       => 'Guest redirect',
                'description' => 'A page to redirect if the user is not logged in',
                'type'        => 'dropdown',
                'default'     => ''
            ],
            'redirectWorker' => [
                'title'       => 'Worker redirect',
                'description' => 'A page to redirect if the user is a worker',
                'type'        => 'dropdown',
                'default'     => ''
            ],
            'redirectClient' => [
                'title'       => 'Client redirect',
                'description' => 'A page to redirect if the user is a client',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return [''=>'- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        /*
         * Only users without workers or client profiles can view the dash
         */

        $redirectUrl = null;

        if (!$user = Auth::getUser()) {
            $redirectUrl = $this->property('redirectGuest');
        }
        elseif ($user->is_worker) {
            $redirectUrl = $this->property('redirectWorker');
        }
        elseif ($user->is_client) {
            $redirectUrl = $this->property('redirectClient');
        }

        if ($redirectUrl) {
            return Redirect::to($this->controller->pageUrl($redirectUrl));
        }
    }

}