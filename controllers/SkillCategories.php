<?php namespace Responsiv\Pyrolancer\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * SkillCategories Back-end Controller
 */
class SkillCategories extends Controller
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

        BackendMenu::setContext('Responsiv.Pyrolancer', 'pyrolancer', 'skillcategories');
    }
}