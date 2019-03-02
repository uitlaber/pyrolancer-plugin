<?php namespace Responsiv\Pyrolancer\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Skills Back-end Controller
 */
class Skills extends Controller
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

        BackendMenu::setContext('Responsiv.Pyrolancer', 'pyrolancer', 'skills');
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->is_visible) {
            return 'safe disabled';
        }
    }
}