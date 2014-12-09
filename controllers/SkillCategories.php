<?php namespace Ahoy\Pyrolancer\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Ahoy\Pyrolancer\Models\SkillCategory;

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

        BackendMenu::setContext('Ahoy.Pyrolancer', 'pyrolancer', 'skills');
    }

    public function reorder()
    {
        $this->pageTitle = 'Reorder Categories';

        $toolbarConfig = $this->makeConfig();
        $toolbarConfig->buttons = '$/ahoy/pyrolancer/controllers/skillcategories/_reorder_toolbar.htm';

        $this->vars['toolbar'] = $this->makeWidget('Backend\Widgets\Toolbar', $toolbarConfig);
        $this->vars['records'] = SkillCategory::make()->setTreeOrderBy('sort_order')->getAllRoot();
    }

    public function reorder_onMove()
    {
        if (!$ids = post('record_ids')) return;
        if (!$orders = post('sort_orders')) return;

        $model = new SkillCategory;
        $model->setSortableOrder($ids, $orders);
    }

    public function listExtendModel($model)
    {
        return $model->setTreeOrderBy('sort_order');
    }
}