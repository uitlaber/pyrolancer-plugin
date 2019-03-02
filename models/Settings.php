<?php namespace Responsiv\Pyrolancer\Models;

use Backend\Models\UserGroup;
use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'responsiv_pyrolancer_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->notify_admin_group = UserGroup::CODE_OWNERS;
        $this->auto_approve_projects = false;
        $this->allow_project_featured = true;
        $this->allow_project_private = true;
        $this->allow_project_urgent = true;
        $this->allow_project_sealed = true;
    }

    public function getNotifyAdminGroupOptions()
    {
        return UserGroup::lists('name', 'code');
    }
}
