<?php namespace Ahoy\Pyrolancer\Controllers;

use Flash;
use Markdown;
use BackendMenu;
use Backend\Classes\Controller;

/**
 * Projects Back-end Controller
 */
class Projects extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Ahoy.Pyrolancer', 'pyrolancer', 'projects');
    }

    public function preview($recordId = null, $context = null)
    {
        $result = $this->asExtension('FormController')->preview($recordId, $context);
        if ($this->fatalError)
            return $result;

        $this->addcss('/plugins/ahoy/pyrolancer/assets/css/pyrolancer.css');
        $this->addJs('/plugins/ahoy/pyrolancer/assets/js/project-preview.js');
        return $result;
    }

    public function preview_onLoadRejectForm($recordId = null)
    {
        return $this->makePartial('reject_form');
    }

    public function preview_onApprove($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->markApproved();

        Flash::success('This project has been approved.');

        if ($redirect = $this->makeRedirect('preview', $model))
            return $redirect;
    }

    public function preview_onReject($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        if ($reason = trim(post('reason'))) {
            $model->markRejected($reason);
            Flash::success('This project has been rejected.');

            if ($redirect = $this->makeRedirect('preview', $model))
                return $redirect;
        }
        else {
            throw new ApplicationException('Please supply a reason.');
        }
    }

    public function preview_onRefreshConversationPreview()
    {
        $previewHtml = Markdown::parse(trim(post('reason')));

        return [
            'preview' => $previewHtml
        ];
    }
}