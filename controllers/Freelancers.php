<?php namespace Responsiv\Pyrolancer\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Freelancers Back-end Controller
 */
class Freelancers extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Responsiv.Pyrolancer', 'pyrolancer', 'freelancers');
    }
}