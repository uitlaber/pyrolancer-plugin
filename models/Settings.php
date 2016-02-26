<?php namespace Ahoy\Pyrolancer\Models;

use Backend\Models\UserGroup;
use ActivRecord;

class Settings extends ActivRecord
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ahoy_pyrolancer_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->notify_admin_group = UserGroup::DEFAULT_CODE;
        $this->auto_approve_projects = false;
    }

    public function getNotifyAdminGroupOptions()
    {
        return UserGroup::lists('name', 'code');
    }
}